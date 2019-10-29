<?php

namespace App;

use App\Imports\GenericImport;
use App\Jobs\ProcessFile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class File extends Model
{
    const MIN_BYTES        = 10;

    const PRIVATE_STORAGE  = 'private';

    const STATUS_COMPLETE  = 16;

    const STATUS_DELAYED   = 2;

    CONST STATUS_EMPTY     = 32;

    CONST STATUS_ERROR     = 4;

    const STATUS_IMPORTING = 8;

    const STATUS_WAITING   = 1;

    const STORAGE          = 'local';

    const TYPE_HASH        = 1;

    const TYPE_LIST        = 2;

    const TYPE_SCRUB       = 4;

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
        $uploadedFile->move($realDir, $fileName);
        $this->location = $realDestination;
        $this->save();

        return $this;
    }

    /**
     * Process an imported file into hashes.
     */
    public function process()
    {
        if ($this->size < self::MIN_BYTES) {
            $this->status  = self::STATUS_EMPTY;
            $this->message = 'File was empty after upload.';

            return $this->save();
        }

        if ($this->status & self::STATUS_WAITING || $this->status & self::STATUS_DELAYED) {
            $this->status = self::STATUS_IMPORTING;
            $this->save();
            Excel::import(new GenericImport($this->type), $this->location, self::STORAGE);
        }

        return $this;
    }
}
