<?php

namespace App;

use App\Imports\FileImport;
use App\Imports\FileImportAnalysis;
use App\Jobs\ProcessFile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class File
 *
 * Files go through the following workflow:
 *  - Added: Freshly uploaded, waiting to be picked up
 *  - Analysis: Checking for size, column types and such with a short-run import for a preview.
 *  - Input: Awaiting user input to know what to do next.
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

    const PRIVATE_STORAGE     = 'private';

    const STATUS_ADDED        = 1;

    const STATUS_ANALYSIS     = 2;

    const STATUS_INPUT_NEEDED = 4;

    const STATUS_READY        = 8;

    const STATUS_RUNNING      = 16;

    const STATUS_STOPPED      = 32;

    const STATUS_WHOLE        = 64;

    const STORAGE             = 'local';

    const TYPE_HASH           = 1;

    const TYPE_LIST           = 2;

    const TYPE_SCRUB          = 4;

    protected $guarded = [
        'id',
    ];

    /**
     * @param $uploadedFile
     * @param $fileType
     * @param  Request  $request
     *
     * @return $this
     */
    public function createAndMove($uploadedFile, $fileType, Request $request)
    {
        if (!($uploadedFile instanceof UploadedFile)) {
            throw new Exception('Unable to parse file upload.');
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
            'type'                 => $fileType,
            'format'               => null,
            'columns'              => null,
            'column_count'         => 0,
            'size'                 => $uploadedFile->getSize(),
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

        ProcessFile::dispatch($file->id)
            ->onQueue($fileType);

        return $file;
    }

    /**
     * Moves the file from the temporary location into a persistent location shared between application nodes.
     * Updates the record with the new location.
     *
     * @param  UploadedFile  $uploadedFile
     *
     * @return $this
     */
    private function move(UploadedFile $uploadedFile)
    {
        $storage        = Storage::disk(self::STORAGE);
        $now            = Carbon::now('UTC');
        $date           = $now->format('Y-m-d');
        $time           = $now->format('H-i-s-v'); // Change timestamp format to control rate limit.
        $fileId         = $this->id ?? 0;
        $userId         = $this->user_id ?? $this->session_id;
        $fileType       = $this->file_type ?? 0;
        $directory      = self::PRIVATE_STORAGE.DIRECTORY_SEPARATOR.$date;
        $extension      = pathinfo($this->name)['extension'] ?? 'tmp';
        $inputFileName  = implode('-', [$date, $time, $fileType, $userId, $fileId]).'-input.'.$extension;
        $outputFileName = implode('-', [$date, $time, $fileType, $userId, $fileId]).'-output.'.$extension;
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
        if ($this->size < self::MIN_BYTES) {
            $this->status  = self::STATUS_STOPPED;
            $this->message = 'File was empty after upload.';
            $this->save();
        }

        if ($this->status & self::STATUS_ADDED) {
            $this->status  = self::STATUS_ANALYSIS;
            $this->message = 'File contents are under analysis.';
            $this->save();
            $fileImportAnalysis = new FileImportAnalysis($this);
            Excel::import($fileImportAnalysis, $this->input_location, self::STORAGE);

            $analysis = $fileImportAnalysis->getAnalysis();
        }

        if ($this->status & self::STATUS_READY) {
            $this->status  = self::STATUS_RUNNING;
            $this->message = 'File is being processed.';
            $this->save();
            $fileImport = new FileImport($this);
            Excel::import($fileImport, $this->input_location, self::STORAGE);
        }

        return $this;
    }
}
