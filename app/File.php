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
    const FILENAME_DELIMITERS = [' ', '_', '.', '-'];

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
     * @param  FormBuilder|null  $formBuilder
     * @param  null  $fileId
     * @param  int  $limit
     *
     * @return mixed
     */
    public static function findByCurrentUser(
        Request $request,
        FormBuilder $formBuilder = null,
        $fileId = null,
        $limit = 20
    ) {
        $q = self::where(
            function ($q) use ($request) {
                $q->where('session_id', $request->getSession()->getId());
                if ($request->user()) {
                    $q->orWhere('user_id', $request->user()->id);
                }
            })
            ->whereNull('deleted_at');

        if ($fileId) {
            $q->where('id', $fileId);
            $limit = 1;
        } else {
            $q->orderBy('created_at', 'desc');
        }
        $files = $q->take((int) $limit)->get();
        if ($formBuilder) {
            foreach (collect($files) as $file) {
                /** File $file */
                $file->form = $file->buildForm($formBuilder);
            }
        }

        return $files;
    }

    /**
     * Format bytes to kb, mb, gb, tb
     *
     * @param  integer  $precision
     *
     * @return integer
     */
    public function humanSize($precision = 1)
    {
        $size = $this->size ?? 0;
        if ($size > 0) {
            $size     = (int) $size;
            $base     = log($size) / log(1024);
            $suffixes = [' bytes', ' KB', ' MB', ' GB', ' TB'];

            return round(pow(1024, $base - floor($base)), $precision).$suffixes[(int) floor($base)];
        } else {
            return $size;
        }
    }

    /**
     * @param  FormBuilder  $formBuilder
     *
     * @return \Kris\LaravelFormBuilder\Form
     */
    public function buildForm(FormBuilder $formBuilder)
    {
        return $formBuilder->create(FileForm::class, [], [
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
            'download_count'       => 0,
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
        $extension      = pathinfo($this->name, PATHINFO_EXTENSION) ?? 'tmp';
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
     * @return bool|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download()
    {
        $storage = Storage::disk(self::STORAGE);
        if (
            $this->status & self::STATUS_WHOLE
            && $storage->exists($this->getRelativeOutputLocation())
        ) {
            // Use the original file name with a minor addition for clarity.
            $name = pathinfo($this->name, PATHINFO_FILENAME);

            // Try to re-use whatever delimiter the original file came with.
            foreach (self::FILENAME_DELIMITERS as $delim) {
                if (false !== stripos($name, $delim)) {
                    break;
                }
            }
            if ($this->mode & self::MODE_HASH) {
                $name .= $delim.'hashed';
            } elseif ($this->mode & self::MODE_HASH) {
                $name .= $delim.'scrubbed';
            }
            $name .= '.'.pathinfo($this->name, PATHINFO_EXTENSION);

            $this->download_count++;
            $this->save();

            return response()->download($this->output_location, $name);
        } else {
            return response()->isNotFound();
        }
    }

    /**
     * @return string
     */
    public function getRelativeOutputLocation()
    {
        $outputLocation = $this->output_location;
        $remove         = storage_path('app');
        if (substr($outputLocation, 0, strlen($remove)) === $remove) {
            $outputLocation = substr($outputLocation, strlen($remove) + 1);
        }

        return $outputLocation;
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

                $this->columns      = $fileImportAnalysis->getAnalysis()['columns'];
                $this->column_count = count($this->columns);
                $this->status       = self::STATUS_INPUT_NEEDED;
                $this->message      = '';
                $this->save();
            }

            if ($this->status & self::STATUS_READY) {
                $this->status  = self::STATUS_RUNNING;
                $this->message = '';
                $this->save();
                $fileImport = new FileImport($this);
                Excel::import($fileImport, $this->input_location, null, $this->type);

                Excel::store($fileImport->getExport(), $this->getRelativeOutputLocation(), null, $this->type, [
                    'visibility' => self::PRIVATE_STORAGE,
                ]);

                $this->status  = self::STATUS_WHOLE;
                $this->message = '';
                $this->save();
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

    /**
     * @param $values
     */
    public function saveInputSettings($values)
    {
        if ($this->id && $this->status & self::STATUS_INPUT_NEEDED) {
            $this->status         = self::STATUS_READY;
            $this->input_settings = (array) $values;
            $this->message        = '';
            unset($this->form);
            $this->save();
            ProcessFile::dispatch($this->id)->onQueue($this->mode);
        }
    }
}
