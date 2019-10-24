<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    const PRIVATE_STORAGE = 'private';

    const TYPE_HASH       = 1;

    const TYPE_LIST       = 2;

    const TYPE_SCRUB      = 4;

    protected $guarded = [
        'id',
    ];

    /**
     * Moves the file from the temporary location into a persistent location shared between application nodes.
     *
     * @param  UploadedFile  $uploadedFile
     *
     * @return bool
     */
    public function move(UploadedFile $uploadedFile)
    {
        $storage   = Storage::disk('local');
        $now       = Carbon::now('UTC');
        $date      = $now->format('Y-m-d');
        $time      = $now->format('H-i-s-v'); // Change timestamp format to control rate limit.
        $fileId    = $this->id ?? 0;
        $userId    = $this->user_id ?? $this->session_id;
        $fileType  = $this->file_type ?? 0;
        $directory = self::PRIVATE_STORAGE.DIRECTORY_SEPARATOR.$date;
        $extension = pathinfo($this->name)['extension'] ?? 'tmp';
        $fileName  = $date.'-'.$time.'-'.$fileType.'-'.$userId.'-'.$fileId.$this->name.'.'.$extension;
        if (!$storage->exists($directory)) {
            $storage->makeDirectory($directory);
        }
        $realDir         = storage_path('app'.DIRECTORY_SEPARATOR.$directory);
        $realDestination = $realDir.DIRECTORY_SEPARATOR.$fileName;
        if ($storage->exists($realDestination)) {
            // More than one file by type, user and time. Likely DoS attack.
            throw new Exception('Too many files are being uploaded by the same user at once.');
        }
        $uploadedFile->move($realDir, $fileName);
        $this->location = $realDestination;

        return $this->save();
    }
}
