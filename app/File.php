<?php

namespace App;

use App\Forms\FileForm;
use App\Imports\FileImport;
use App\Imports\FileImportAnalysis;
use App\Jobs\ProcessFile;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kris\LaravelFormBuilder\FormBuilder;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Helpers\FileTypeDetector;

/**
 * Class File
 *
 * Files go into the system in 3 modes:
 *  - Hash: Just hash email/phone based on input provided.
 *  - List: Generate/append/replace a suppression list.
 *  - Scrub: Scrub records in the file against one or more suppression lists.
 *
 * Files go through the following workflow:
 *  - Added: Freshly uploaded, waiting to be picked up
 *  - Analysis: Checking for size, column types and such with a short-run import for a preview.
 *  - Input Needed: Awaiting user input to know what to do next.
 *  - Ready: Ready to begin import.
 *  - Running: Running the import operation based on options provided.
 *  - Stopped: Cancelled run due to error or user input.
 *  - Whole: The file was completed.
 *
 * @package App
 */
class File extends Model
{
    const MIN_BYTES           = 10;

    const MODE_HASH           = 1;

    const MODE_LIST_APPEND    = 2;

    const MODE_LIST_CREATE    = 4;

    const MODE_LIST_REPLACE   = 8;

    const MODE_SCRUB          = 16;

    const PRIVATE_STORAGE     = 'private';

    const STATUS_ADDED        = 1;

    const STATUS_ANALYSIS     = 2;

    const STATUS_INPUT_NEEDED = 4;

    const STATUS_READY        = 8;

    const STATUS_RUNNING      = 16;

    const STATUS_STOPPED      = 32;

    const STATUS_WHOLE        = 64;

    /** @var array Attributes we do not wish to expose to the user. */
    const STAT_PROPERTY_BLACKLIST = ['input_location', 'output_location', 'user_id', 'session_id'];

    const STORAGE                 = 'local';

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'input_settings' => 'array',
        'columns'        => 'array',
    ];

    /**
     * Combine both session (no auth) files uploaded and user files (logged in).
     *
     * @param  Request  $request
     *
     * @return mixed
     */
    public static function findByCurrentUser(Request $request)
    {
        $qb = self::where('session_id', $request->getSession()->getId());
        if ($request->user()) {
            $qb = self::orWhere('user_id', $request->user()->id);
        }

        return $qb->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    /**
     * @param  FormBuilder  $formBuilder
     *
     * @return \Kris\LaravelFormBuilder\Form
     */
    public function buildForm(FormBuilder $formBuilder)
    {
        return $formBuilder->create(FileForm::class, [
            'method' => 'POST',
            'url'    => route('file'),
        ], [
            'file' => $this,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function users()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param $uploadedFile
     * @param $mode
     * @param  Request  $request
     *
     * @return File
     * @throws Exception
     */
    public function createAndMove($uploadedFile, $mode, Request $request)
    {
        if (!($uploadedFile instanceof UploadedFile)) {
            throw new Exception('Unable to parse file upload.');
        }

        $fileSize = $uploadedFile->getSize();
        if ($fileSize < self::MIN_BYTES) {
            throw new Exception('File was empty.');
        }

        try {
            $fileType = FileTypeDetector::detect($uploadedFile);
        } catch (NoTypeDetectedException $exception) {
            throw new Exception('Could not discern the file type. Please make sure to use the proper file extensions.');
        }

        /** @var File $file */
        $file = $this::create([
            'name'                 => $uploadedFile->getClientOriginalName() ?? 'na',
            'input_location'       => $uploadedFile->getRealPath(),
            'output_location'      => null,
            'user_id'              => $request->user() ? $request->user()->id : null,
            'input_settings'       => null,
            'ip_address'           => $request->getClientIp(),
            'session_id'           => $request->getSession()->getId(),
            'status'               => self::STATUS_ADDED,
            'mode'                 => File::MODE_HASH,
            'type'                 => $fileType,
            'columns'              => null,
            'column_count'         => 0,
            'size'                 => $fileSize,
            'message'              => null,
            'crc32b'               => hash_file('crc32b', $uploadedFile->getRealPath()),
            'md5'                  => hash_file('md5', $uploadedFile->getRealPath()),
            'rows_total'           => 0,
            'rows_processed'       => 0,
            'rows_scrubbed'        => 0,
            'rows_invalid'         => 0,
            'rows_email_valid'     => 0,
            'rows_email_invalid'   => 0,
            'rows_email_duplicate' => 0,
            'rows_email_dnc'       => 0,
            'rows_phone_valid'     => 0,
            'rows_phone_invalid'   => 0,
            'rows_phone_duplicate' => 0,
            'rows_phone_dnc'       => 0,
        ]);
        $file->move($uploadedFile);

        ProcessFile::dispatch($file->id)->onQueue($mode);

        return $file;
    }

    /**
     * Moves the file from the temporary location into a persistent location shared between application nodes.
     * Updates the record with the new location.
     *
     * @param  UploadedFile  $uploadedFile
     *
     * @return $this
     * @throws Exception
     */
    private function move(UploadedFile $uploadedFile)
    {
        $storage        = Storage::disk(self::STORAGE);
        $now            = Carbon::now('UTC');
        $date           = $now->format('Y-m-d');
        $time           = $now->format('H-i-s-v'); // Change timestamp format to control rate limit.
        $fileId         = $this->id ?? 0;
        $userId         = $this->user_id ?? $this->session_id;
        $mode           = $this->mode ?? 0;
        $directory      = self::PRIVATE_STORAGE.DIRECTORY_SEPARATOR.$date;
        $extension      = pathinfo($this->name)['extension'] ?? 'tmp';
        $inputFileName  = implode('-', [$date, $time, $mode, $userId, $fileId]).'-input.'.$extension;
        $outputFileName = implode('-', [$date, $time, $mode, $userId, $fileId]).'-output.'.$extension;
        if (!$storage->exists($directory)) {
            $storage->makeDirectory($directory);
        }
        $realDir                   = storage_path('app'.DIRECTORY_SEPARATOR.$directory);
        $realInputFileDestination  = $realDir.DIRECTORY_SEPARATOR.$inputFileName;
        $realOutputFileDestination = $realDir.DIRECTORY_SEPARATOR.$outputFileName;
        if (
            $storage->exists($realInputFileDestination)
            || $storage->exists($realOutputFileDestination)
        ) {
            // More than one file by type, user and time. Likely DoS attack.
            throw new Exception('Too many files are being uploaded by the same user at once.');
        }

        // Move the file and save the new location.
        $uploadedFile->move($realDir, $inputFileName);
        $this->input_location  = $realInputFileDestination;
        $this->output_location = $realOutputFileDestination;

        $this->save();

        return $this;
    }

    /**
     * Process an imported file into hashes.
     */
    public function process()
    {
        try {
            if ($this->status & self::STATUS_ADDED) {
                $this->status  = self::STATUS_ANALYSIS;
                $this->message = '';
                $this->save();

                $fileImportAnalysis = new FileImportAnalysis($this);
                Excel::import($fileImportAnalysis, $this->input_location, null, $this->type);

                $this->columns = $fileImportAnalysis->getAnalysis()['columns'];
                $this->status  = self::STATUS_INPUT_NEEDED;
                $this->message = '';
                $this->save();
                // ProcessFile::dispatch($this->id)->onQueue($this->mode);
            }

            if ($this->status & self::STATUS_READY) {
                $this->status  = self::STATUS_RUNNING;
                $this->message = '';
                $this->save();
                $fileImport = new FileImport($this);
                Excel::import($fileImport, $this->input_location);
            }

        } catch (Exception $e) {
            $this->status  = self::STATUS_STOPPED;
            $this->message = 'An error was encountered while trying to import. '.$e->getMessage();
            $this->save();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributesForOutput()
    {
        return array_diff_key($this->getAttributes(), array_flip(self::STAT_PROPERTY_BLACKLIST));
    }
}
