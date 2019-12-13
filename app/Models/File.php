<?php

namespace App\Models;

use App\Forms\FileForm;
use App\Jobs\FileAnalyze;
use App\Jobs\FileGetChecksums;
use App\Jobs\FileRun;
use Carbon\Carbon;
use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Kris\LaravelFormBuilder\Form;
use Kris\LaravelFormBuilder\FormBuilder;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use Maatwebsite\Excel\Helpers\FileTypeDetector;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class File
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
class File extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait, Notifiable;

    const DATE_FORMAT         = 'Y-m-d H:i:s.u';

    const FILENAME_DELIMITERS = [' ', '_', '.', '-'];

    /** @var int Seconds allowed for the file move action to be complete. */
    const FILE_MOVE_DELAY = 30;

    /** @var int Files are treated differently when they are over this size to speed up analysis. */
    const LARGE_FILE_BYTES = 10000000;

    /** @var int Chunk to load for file analysis */
    const LARGE_FILE_CHUNK  = 1000000;

    const MIN_BYTES         = 10;

    const MODE_HASH         = 1;

    const MODE_LIST_APPEND  = 2;

    const MODE_LIST_CREATE  = 4;

    const MODE_LIST_REPLACE = 8;

    const MODE_SCRUB        = 16;

    const PRIVATE_STORAGE   = 'private';

    /** @var array */
    const STATS_DEFAULT       = [
        'rows_total'     => 0,
        'rows_processed' => 0,
        'rows_persisted' => 0,
        'rows_filled'    => 0,
        'rows_imported'  => 0,
        'rows_scrubbed'  => 0,
        'rows_hashed'    => 0,
        'rows_invalid'   => 0,
    ];

    const STATUS_ADDED        = 1;

    const STATUS_ANALYSIS     = 2;

    const STATUS_INPUT_NEEDED = 4;

    const STATUS_READY        = 8;

    const STATUS_RUNNING      = 16;

    const STATUS_STOPPED      = 32;

    const STATUS_WHOLE        = 64;

    const STORAGE             = 'local';

    protected $dateFormat = self::DATE_FORMAT;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'input_settings' => 'array',
        'columns'        => 'array',
        'sheets'         => 'array',
    ];

    protected $auditInclude = [
        'name',
        'input_settings',
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
        $q = self::query()
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

        // Append forms if relevant and able for input or validation.
        if ($formBuilder) {
            foreach (collect($files) as $file) {
                if ($file->status & File::STATUS_INPUT_NEEDED) {
                    /** File $file */
                    $file->form = $file->buildForm($formBuilder);
                }
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
        $file = self::create(self::STATS_DEFAULT + [
                'name'            => $uploadedFile->getClientOriginalName() ?? 'na',
                'run_started'     => null,
                'run_completed'   => null,
                'available_till'  => null,
                'input_location'  => $uploadedFile->getRealPath(),
                'output_location' => null,
                'user_id'         => $request->user() ? $request->user()->id : null,
                'input_settings'  => null,
                'session_id'      => $request->getSession()->getId(),
                'status'          => self::STATUS_ADDED,
                'mode'            => File::MODE_SCRUB,
                'type'            => $fileType,
                'columns'         => null,
                'column_count'    => 0,
                'size'            => $fileSize,
                'message'         => null,
                'crc32b'          => null,
                'md5'             => null,
                'country'         => $request->header('CF-IPCountry', 'US'),
            ]);
        $file->move($uploadedFile);

        // If the file is large, prioritize analysis over hash check to save time.
        if ($fileSize > self::LARGE_FILE_BYTES) {
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
     * @return Filesystem|Storage
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
     * Permit microtime from MySQL
     *
     * @return Builder
     */
    public function newQuery()
    {
        $query = parent::newQuery();

        if ($this->usesTimestamps()) {
            $table   = $this->getTable();
            $columns = ['*'];
            foreach ($this->getDates() as $dateColumn) {
                $columns[] = DB::raw("CONCAT($table.$dateColumn) AS $dateColumn");
            }
            $query->addSelect($columns);
        }

        return $query;
    }

    public function getDates()
    {
        $dates   = parent::getDates();
        $dates[] = 'available_till';
        $dates[] = 'run_started';
        $dates[] = 'run_completed';

        return $dates;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getValidatedInputLocation()
    {
        if (!$this->input_location) {
            throw new Exception(__('Input file could not be found.'));
        }

        $input = $this->getRelativeLocation($this->input_location);

        $startTime = microtime(true);
        while (!$this->getStorage()->exists($input)) {
            // 100ms
            usleep(100000);
            if ((microtime(true) - $startTime) <= self::FILE_MOVE_DELAY) {
                break;
            }
        }

        if (!$this->getStorage()->exists($input)) {
            throw new Exception(__('File move process taking longer than allowed time. File may be too large to handle.'));
        }

        return $this->input_location;
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
     * @return Form
     */
    public function buildForm(FormBuilder $formBuilder)
    {
        return $formBuilder->create(FileForm::class, [], [
            'file' => $this,
        ]);
    }

    /**
     * @return bool|BinaryFileResponse
     * @throws Exception
     */
    public function download()
    {
        if (
            $this->status & self::STATUS_WHOLE
            && Carbon::now() < new Carbon($this->available_til ?? '', 'UTC')
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
                $this->downloads()->create();

                return response()->download($location, $name);
            }
        }

        return response()->isNotFound();
    }

    /**
     * @return HasMany
     */
    public function downloads()
    {
        return $this->hasMany(FileDownload::class);
    }

    /**
     * @return HasMany
     */
    public function downloadLinks()
    {
        return $this->hasMany(FileDownloadLink::class);
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
            $this->email          = !empty($values['email']) ? filter_var($values['email'],
                FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE) : ($this->user->email ?? null);
            if ($this->email) {
                session()->put('email', $this->email);
            }

            if ($this->mode & File::MODE_SCRUB) {
                if (empty($this->user->id)) {
                    throw new Exception(__('You must be logged in to scrub.'));
                }
                $listIds = $this->getInputLists('suppression_list_use_');
                if ($listIds) {
                    $user = $this->user;
                    $q    = SuppressionList::query()
                        ->whereIn('id', $listIds)
                        ->where(function ($q) use ($user) {
                            // Disallow private list usage, unless the file of origin also belongs to the same user.
                            $q->where('private', 0);
                            if ($user) {
                                $q->orWhere('user_id', $user->id);
                                $q->orWhere('global', 1);
                            }
                        });

                    // @todo - Add validation for tokens to query here if applicable.

                    $lists = $q->get();
                    if (!$lists->count()) {
                        throw new Exception(__('No appropriate lists were found for scrubbing with.'));
                    }

                    // @todo - If the count of the lists we can scrub with doesn't match the input we should let the user know.

                } else {
                    throw new Exception(__('You must select a list to scrub with.'));
                }
                /** @var FileSuppressionList $list */
                foreach ($lists as $list) {
                    $this->suppressionLists()->attach($list->id, [
                        'created_at'   => Carbon::now('UTC'),
                        'updated_at'   => Carbon::now('UTC'),
                        'relationship' => FileSuppressionList::REL_LIST_USED_TO_SCRUB,
                    ]);
                }
            }
            if ($this->mode & File::MODE_LIST_CREATE) {
                if (empty($this->user->id)) {
                    throw new Exception(__('You must be logged in to create a list.'));
                }
                $this->suppressionLists()->create([
                    'name'    => $this->listNameFromFileName($this->name),
                    'user_id' => $this->user->id,
                ], [
                    'created_at'   => Carbon::now('UTC'),
                    'updated_at'   => Carbon::now('UTC'),
                    'relationship' => FileSuppressionList::REL_FILE_INTO_LIST,
                ]);
            }
            if ($this->mode & File::MODE_LIST_APPEND) {
                if (empty($this->user->id)) {
                    throw new Exception(__('You must be logged in to append a list.'));
                }
                if ($suppressionListIds = $this->getInputLists('suppression_list_append')) {
                    if ($suppressionLists = $this->user->suppressionLists->whereIn('id', $suppressionListIds)) {
                        $ids = [];
                        foreach ($suppressionLists as $suppressionList) {
                            $ids[$suppressionList->id] = [
                                'created_at'   => Carbon::now('UTC'),
                                'updated_at'   => Carbon::now('UTC'),
                                'relationship' => FileSuppressionList::REL_FILE_INTO_LIST,
                            ];
                        }
                        $this->suppressionLists()->sync($ids);
                    }
                } else {
                    throw new Exception(__('You must select a list to append.'));
                }
            }
            if ($this->mode & File::MODE_LIST_REPLACE) {
                if (empty($this->user->id)) {
                    throw new Exception(__('You must be logged in to replace a list.'));
                }
                if ($suppressionListIds = $this->getInputLists('suppression_list_replace')) {
                    if ($suppressionLists = $this->user->suppressionLists->whereIn('id', $suppressionListIds)) {
                        if (count($suppressionLists) > 1) {
                            throw new Exception(__('You may not replace more than one suppression list at a time.'));
                        }
                        $ids = [];
                        foreach ($suppressionLists as $suppressionList) {
                            $ids[$suppressionList->id] = [
                                'created_at'   => Carbon::now('UTC'),
                                'updated_at'   => Carbon::now('UTC'),
                                'relationship' => FileSuppressionList::REL_FILE_REPLACE_LIST,
                            ];
                        }
                        $this->suppressionLists()->sync($ids);
                    }
                } else {
                    throw new Exception(__('You must select a list to replace.'));
                }
            }
            unset($this->form);
            $this->save();

            FileRun::dispatch($this->id);
        }
    }

    /**
     * @param $prefix
     *
     * @return array
     */
    private function getInputLists($prefix)
    {
        $listIds = [];
        if ($this->input_settings) {
            foreach ($this->input_settings as $key => $value) {
                if ($value && 0 === strpos($key, $prefix)) {
                    $listIds[] = (int) $value;
                }
            }
        }

        return array_unique($listIds);
    }

    /**
     * @return BelongsToMany
     */
    public function suppressionLists()
    {
        return $this->belongsToMany(SuppressionList::class)
            ->using(FileSuppressionList::class)
            ->withPivot(['relationship']);
    }

    /**
     * @param $fileName
     *
     * @return array|false|Translator|string|string[]|null
     */
    private function listNameFromFileName($fileName)
    {
        $fileName = trim($fileName);
        $fileName = substr($fileName, 0, strrpos($fileName, '.'));
        $fileName = preg_replace('/[^a-z0-9\-\.]/i', ' ', $fileName);
        $fileName = preg_replace('/\s+/', ' ', $fileName);
        $fileName = ucwords($fileName);
        if (empty($fileName)) {
            $fileName = __('Untitled');
        }

        return $fileName;
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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

    /**
     * @param $stat
     * @param  bool  $formatted
     *
     * @return int|string
     */
    public function stat($stat, $formatted = true)
    {
        if (in_array($stat, array_keys(self::STATS_DEFAULT)) && isset($this->{$stat})) {
            if ($formatted) {
                return number_format($this->{$stat});
            } else {
                return (int) $this->{$stat};
            }
        }

        return 0;
    }

    /**
     * To be used to discern if an alternative treatment is necessary
     *
     * @return bool
     */
    public function isLargeCsv()
    {
        return $this->size > File::LARGE_FILE_BYTES
            && in_array($this->type, [Excel::CSV, Excel::TSV, Excel::HTML]);
    }

    /**
     * @param  bool  $animated
     *
     * @return int|mixed
     */
    public function progress($animated = false)
    {
        $total = $this->rows_total ?? 0;
        if (!$total) {
            return 100;
        }
        $percentage = min(100, max(0, floor(100 / $total * $this->rows_processed)));
        if (!$percentage && $animated) {
            return 100;
        }

        return $percentage;
    }

    /**
     * Permit microtime from MySQL
     *
     * @param  mixed  $value
     *
     * @return \Illuminate\Support\Carbon
     * @throws Exception
     */
    protected function asDateTime($value)
    {
        try {
            return parent::asDateTime($value);
        } catch (InvalidArgumentException $e) {
            return parent::asDateTime(new DateTimeImmutable($value));
        }
    }
}
