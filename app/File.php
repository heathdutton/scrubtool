<?php

namespace App;

use App\Forms\FileForm;
use App\Jobs\FileAnalyze;
use App\Jobs\FileGetChecksums;
use App\Jobs\FileRun;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kris\LaravelFormBuilder\FormBuilder;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
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
    use SoftDeletes;

    const FILENAME_DELIMITERS = [' ', '_', '.', '-'];

    const MIN_BYTES           = 10;

    const MODE_HASH           = 1;

    const MODE_LIST_APPEND    = 2;

    const MODE_LIST_CREATE    = 4;

    const MODE_LIST_REPLACE   = 8;

    const MODE_SCRUB          = 16;

    const PRIVATE_STORAGE     = 'private';

    /** @var array */
    const STATS_DEFAULT       = [
        'rows_total'           => 0,
        'rows_imported'        => 0,
        'rows_scrubbed'        => 0,
        'rows_hashed'          => 0,
        'rows_invalid'         => 0,
        'rows_email_valid'     => 0,
        'rows_email_invalid'   => 0,
        'rows_email_duplicate' => 0,
        'rows_email_dnc'       => 0,
        'rows_phone_valid'     => 0,
        'rows_phone_invalid'   => 0,
        'rows_phone_duplicate' => 0,
        'rows_phone_dnc'       => 0,
    ];

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

    /** @var Storage */
    private $storage;

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
        $q = self::withoutTrashed()
            ->where(
                function ($q) use ($request) {
                    $q->where('session_id', $request->getSession()->getId());
                    if ($request->user()) {
                        $q->orWhere('user_id', $request->user()->id);
                    }
                });

        if ($fileId) {
            $q->where('id', $fileId);
            $limit = 1;
        } else {
            $q->orderBy('created_at', 'desc');
        }
        /** @var Collection $files */
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
     * @param $sessionId
     * @param $userId
     *
     * @return mixed
     */
    public static function addUserToFilesBySessionId($sessionId, $userId)
    {
        return self::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update(['user_id' => (int) $userId]);
    }

    /**
     * @param $uploadedFile
     * @param $mode
     * @param  Request  $request
     *
     * @return File
     * @throws Exception
     */
    public static function createAndMove($uploadedFile, $mode, Request $request)
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
        $file = self::create([
            'name'                 => $uploadedFile->getClientOriginalName() ?? 'na',
            'available_till'       => null,
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
            'crc32b'               => '',
            'md5'                  => '',
            'country'              => $request->header('CF-IPCountry', 'US'),
            'rows_total'           => 0,
            'rows_imported'        => 0,
            'rows_scrubbed'        => 0,
            'rows_hashed'          => 0,
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

        // If the file size is over 10mb, prioritize analysis over hash check to save time.
        if ($fileSize > 10000000) {
            FileAnalyze::dispatch($file->id);
            FileGetChecksums::dispatch($file->id);
        } else {
            FileGetChecksums::dispatch($file->id);
            FileAnalyze::dispatch($file->id);
        }

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
        if (!$this->getStorage()->exists($directory)) {
            $this->getStorage()->makeDirectory($directory);
        }
        $realDir                   = storage_path('app'.DIRECTORY_SEPARATOR.$directory);
        $realInputFileDestination  = $realDir.DIRECTORY_SEPARATOR.$inputFileName;
        $realOutputFileDestination = $realDir.DIRECTORY_SEPARATOR.$outputFileName;
        if (
            $this->getStorage()->exists($realInputFileDestination)
            || $this->getStorage()->exists($realOutputFileDestination)
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
     * @return \Illuminate\Contracts\Filesystem\Filesystem|Storage
     */
    private function getStorage()
    {
        if (!$this->storage) {
            $this->storage = Storage::disk(self::STORAGE);
        }

        return $this->storage;
    }

    /**
     * Get the maximum file size the system currently supports for upload.
     *
     * @return float
     */
    public static function getMaxUploadMb()
    {
        static $maxUploadMb = -1;
        if ($maxUploadMb < 0) {
            $postMaxSize = self::parseSizeToBytes(ini_get('post_max_size'));
            if ($postMaxSize > 0) {
                $maxUploadMb = $postMaxSize;
            }
            $uploadMax = self::parseSizeToBytes(ini_get('upload_max_filesize'));
            if ($uploadMax > 0 && $uploadMax < $maxUploadMb) {
                $maxUploadMb = $uploadMax;
            }
        }

        return round($maxUploadMb / 1048576, 2, PHP_ROUND_HALF_DOWN);
    }

    /**
     * @param $size
     *
     * @return float
     */
    private static function parseSizeToBytes($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return floor($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return floor($size);
        }
    }

    /**
     * @return mixed|null
     */
    public function getValidatedInputLocation()
    {
        $input = $this->getRelativeLocation($this->input_location);
        if ($this->getStorage()->exists($input)) {
            return $this->input_location;
        }

        return null;
    }

    /**
     * @param  string  $location
     *
     * @return false|string
     */
    public function getRelativeLocation($location = '')
    {
        $remove = storage_path('app');
        if (substr($location, 0, strlen($remove)) === $remove) {
            $location = substr($location, strlen($remove) + 1);
        }

        return $location;
    }

    /**
     * Deletes input and output files, then soft deletes the model.
     *
     * @return bool|mixed|null
     */
    public function delete()
    {
        $input = $this->getRelativeLocation($this->input_location);
        if ($this->getStorage()->exists($input)) {
            $this->getStorage()->delete($input);
        }
        $output = $this->getRelativeLocation($this->output_location);
        if ($this->getStorage()->exists($output)) {
            $this->getStorage()->delete($output);
        }

        return $this->performDeleteOnModel();
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
     * @return bool|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download()
    {
        if ($this->status & self::STATUS_WHOLE) {
            // Use the original file name with a minor addition for clarity.
            $name = pathinfo($this->name, PATHINFO_FILENAME);

            // Try to re-use whatever delimiter the original file came with.
            foreach (self::FILENAME_DELIMITERS as $delim) {
                if (false !== stripos($name, $delim)) {
                    break;
                }
            }
            if ($this->mode & self::MODE_HASH) {
                $name     .= $delim.'hashed';
                $location = $this->output_location;
            } elseif ($this->mode & self::MODE_SCRUB) {
                $name     .= $delim.'scrubbed';
                $location = $this->output_location;
            } else {
                // For all other types, download the original file since there is no output.
                $location = $this->input_location;
            }
            $name .= '.'.pathinfo($this->name, PATHINFO_EXTENSION);

            if ($this->getStorage()->exists($this->getRelativeLocation($location))) {
                $this->download_count++;
                $this->save();

                return response()->download($location, $name);
            }
        }

        return response()->isNotFound();
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
     *
     * @throws Exception
     */
    public function saveInputSettings($values)
    {
        if ($this->id && $this->status & self::STATUS_INPUT_NEEDED) {
            $this->status         = self::STATUS_READY;
            $this->input_settings = (array) $values;
            $this->message        = '';
            $this->mode           = !empty($values['mode']) ? intval($values['mode']) : $this->mode;

            // @todo - Validate and associate lists via pivot.
            if ($this->mode & File::MODE_SCRUB) {
                $listIds = [];
                foreach ($this->input_settings as $key => $value) {
                    if ($value) {
                        $listIds[] = (int) $value;
                    }
                }
                $listIds = array_unique($listIds);
                $user    = $this->user()->withoutTrashed()->getRelated()->first();
                $q       = SuppressionList::withoutTrashed()
                    ->whereIn('id', $listIds)
                    ->where(function ($q) use ($user) {
                        // Dissalow private list usage, unless the file of origin also belongs to the same user.
                        $q->where('private', '!=', 1);
                        if ($user) {
                            $q->orWhere('user_id', $user->id);
                        }
                    });
                $lists   = $q->get();
                if (!$lists) {
                    throw new Exception(__('No appropriate lists were found for scrubbing with.'));
                }
                /** @var FileSuppressionList $list */
                foreach ($lists as $list) {
                    $this->lists()->attach($list->id, [
                        'created_at'   => Carbon::now('UTC'),
                        'updated_at'   => Carbon::now('UTC'),
                        'relationship' => FileSuppressionList::RELATIONSHIP_CHILD,
                    ]);
                }
            }

            unset($this->form);
            $this->save();

            FileRun::dispatch($this->id);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function lists()
    {
        return $this->belongsToMany(SuppressionList::class)
            ->using(FileSuppressionList::class);
    }

    /**
     * @return array
     */
    public function getColumnsWithInputHashes($mode)
    {
        $colTypePre = 'column_type_';
        $colHashPre = 'column_hash_input_';
        $columns    = [];
        foreach ($this->columns as $key => $column) {
            if (
                // Column has been configured by the user.
                isset($this->input_settings[$colTypePre.$key])
                // The user has confirmed the type as requested.
                && ($mode & $this->input_settings[$colTypePre.$key])
            ) {
                $columns[] = $this->input_settings[$colHashPre.$key] ?? null;
            }
        }

        return $columns;
    }
}
