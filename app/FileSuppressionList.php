<?php

namespace App;

use App\Helpers\FileAnalysisHelper;
use App\Helpers\FileHashHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileSuppressionList extends Pivot
{
    use SoftDeletes;

    /** @var array */
    const COLUMN_TYPES = [
        FileAnalysisHelper::TYPE_EMAIL,
        FileAnalysisHelper::TYPE_PHONE,
    ];

    /** @var int Indicates a file that was SCRUBBED by the list. */
    const RELATIONSHIP_CHILD = 1;

    /** @var int Indicates a file that was used to CREATE the list. */
    const RELATIONSHIP_PARENT = 2;

    /** @var array */
    protected $guarded = [
        'id',
    ];

    /** @var File */
    private $file;

    /** @var SuppressionList to feed into */
    private $list;

    /** @var Collection|null SuppressionLists to scrub with */
    private $lists;

    /** @var FileHashHelper */
    private $fileHashHelper;

    /** @var array */
    private $columnSupports = [];

    /**
     * FileSuppressionList constructor.
     *
     * @param  array  $attributes
     * @param  File|null  $file
     * @param  SuppressionList|null  $list
     *
     * @throws Exception
     */
    public function __construct($attributes = [], File $file = null, SuppressionList $list = null)
    {
        $this->file = $file;
        $this->setList($list);

        parent::__construct($attributes);

        return $this;
    }

    /**
     * @param  SuppressionList|null  $list
     *
     * @throws Exception
     */
    private function setList(SuppressionList $list = null)
    {
        $this->list = $list;

        if (empty($this->file->id)) {
            // Standard construction, the list association should already exist.
            return;
        }

        if (
            empty($this->list->id)
            && empty($this->file->user_id)
            && $this->file->mode & (File::MODE_LIST_CREATE | File::MODE_LIST_APPEND | File::MODE_LIST_REPLACE)
        ) {
            throw new Exception(__('File not associated to a user.'));
        }

        // Create a new Suppression List.
        if (empty($this->list->id) && $this->file->mode & File::MODE_LIST_CREATE) {
            $this->createList()
                ->createSupportsNeeded()
                ->attachFile(FileSuppressionList::RELATIONSHIP_PARENT);
        }

        if ($this->file->mode & File::MODE_LIST_APPEND) {
            // @todo - Load and confirm existing list.
            throw new Exception(__('List append function does not yet exist.'));
        }

        if ($this->file->mode & File::MODE_LIST_REPLACE) {
            // @todo - Drop all tables associated with the list to start fresh.
            throw new Exception(__('List replace function does not yet exist.'));
        }

        // Scrub against an existing Suppression List.
        if ($this->file->mode & File::MODE_SCRUB) {
            $this->loadLists()
                ->findSupportsNeeded();
        }
    }

    /**
     * @param $relationship
     *
     * @return $this
     */
    private function attachFile($relationship)
    {
        $this->list->files()->attach($this->file->id, [
            'created_at'   => Carbon::now('UTC'),
            'updated_at'   => Carbon::now('UTC'),
            'relationship' => $relationship,
        ]);

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function createSupportsNeeded()
    {
        $supports = [];
        foreach ($this->discernSupportsNeeded() as $columnType => $columns) {
            $support = new SuppressionListSupport([
                'column_type'         => $columnType,
                'hash_type'           => array_values($columns)[0],
                'status'              => SuppressionListSupport::STATUS_BUILDING,
                'suppression_list_id' => $this->list->id,
            ]);
            foreach (array_keys($columns) as $columnIndex) {
                $this->columnSupports[$columnIndex] = (new Collection())->add($support);
            }
            $supports[] = $support;
        }
        if (!$supports) {
            throw new Exception(__('There was no Email or Phone column to build your suppression list from. Please make sure you indicate the file contents and try again.'));
        }
        $this->list->supports()->saveMany($supports);

        return $this;
    }

    /**
     * Suppression lists can contain email/phone/both at the moment.
     *
     * @return array
     * @throws Exception
     */
    private function discernSupportsNeeded()
    {
        // Suppression lists can contain email/phone/both at the moment.
        $supportsNeeded = [];
        foreach (self::COLUMN_TYPES as $columnType) {
            $columns = $this->file->getColumnsWithInputHashes($columnType);
            if ($columns) {
                if (count(array_unique($columns)) > 1) {
                    throw new Exception(__('Multiple hash types were used for an email or phone columns. This is not supported. Please only use one hash type, or use plain-text so that all hash types are supported.'));
                }
                $supportsNeeded[$columnType] = $columns;
            }
        }
        if (!$supportsNeeded) {
            throw new Exception(__('There was no Email or Phone column to build your suppression list from. Please make sure you indicate the file contents and try again.'));
        }

        return $supportsNeeded;
    }

    /**
     * @return $this
     */
    private function createList()
    {
        $this->list = new SuppressionList([], $this->file);
        $this->list->save();

        return $this;
    }

    /**
     * Evaluate if the suppression list in question supports the column/hash types in use here before beginning the
     * scrubbing process, while also building the column map.
     *
     * @return $this
     * @throws Exception
     */
    private function findSupportsNeeded()
    {
        $messages     = [];
        $supportFound = false;
        $listIds      = $this->lists->modelKeys();
        if ($listIds) {
            foreach ($this->discernSupportsNeeded() as $columnType => $columns) {
                $hashType = array_values($columns)[0];
                $supports = SuppressionListSupport::withoutTrashed()
                    ->whereIn('suppression_list_id', $listIds)
                    ->where('column_type', $columnType)
                    ->where('hash_type', $hashType)
                    ->where('status', SuppressionListSupport::STATUS_READY)
                    ->get();

                if ($supports) {
                    $supportFound = true;
                    foreach (array_keys($columns) as $columnIndex) {
                        $this->columnSupports[$columnIndex] = $supports;
                    }
                } else {
                    $messages[$columnType.'_'.$hashType] = __('This suppression list does not currently support scrubbing $1 with $2.',
                        [$columnType, $hashType ?? __('plaintext')]);
                }
            }
        }
        // Partial support is allowed, but if no support is found we'll throw an error.
        if (!$supportFound) {
            throw new Exception($messages);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function loadLists()
    {
        $this->lists = $this->file->lists()->withoutTrashed()->get();
        if (!$this->lists) {
            throw new Exception(__('Lists chosen for scrubbing are not available.'));
        }

        return $this;
    }

    /**
     * Returns true if the row was scrubbed.
     *
     * @param $row
     *
     * @return bool
     * @throws Exception
     */
    public function scrubRow(&$row)
    {
        $scrub = false;
        if ($this->lists && $this->columnSupports) {
            /**
             * @var int $columnIndex
             * @var SuppressionListSupport $support
             */
            foreach ($this->columnSupports as $columnIndex => $supports) {
                // Validate/sanitize/hash before attempting a scrub.
                $value = $row[$columnIndex];
                if ($this->getFileHashHelper()->sanitizeColumn($value, $columnIndex, 'output')) {
                    foreach ($supports as $support) {
                        if ($support->getContent()->where('content', $value)->exists()) {
                            $row   = [];
                            $scrub = true;
                            break;
                        }
                    }
                }
            }
        }

        return $scrub;
    }

    /**
     * @return FileHashHelper
     */
    private function getFileHashHelper()
    {
        if (!$this->fileHashHelper) {
            $this->fileHashHelper = new FileHashHelper($this->file);
        }

        return $this->fileHashHelper;
    }

    /**
     * @param $row
     * @param  int  $rowIndex
     *
     * @return bool
     * @throws Exception
     */
    public function appendRowToList($row, $rowIndex = 0)
    {
        $valid = false;
        if ($this->list && $this->columnSupports) {
            foreach ($this->columnSupports as $columnIndex => $supports) {
                /** @var SuppressionListSupport $support */
                foreach ($supports as $support) {
                    // Validate/sanitize/hash before insertion.
                    $value = $row[$columnIndex];
                    if ($this->getFileHashHelper()->sanitizeColumn($value, $columnIndex, 'input', true)) {
                        $support->addContentToQueue($value, $rowIndex);
                        $valid = true;
                    }
                }
            }
        }

        return $valid;
    }

    /**
     * @return $this
     */
    public function finish()
    {
        foreach ($this->columnSupports as $columnIndex => $supports) {
            /** @var SuppressionListSupport $support */
            foreach ($supports as $support) {
                $support->finish();
            }
        }

        return $this;
    }
}
