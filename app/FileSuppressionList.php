<?php

namespace App;

use App\Helpers\FileAnalysisHelper;
use App\Helpers\FileHashHelper;
use Carbon\Carbon;
use Exception;
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

    /** @var SuppressionList */
    private $list;

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
            $this->findSupportsNeeded()
                ->attachFile(FileSuppressionList::RELATIONSHIP_CHILD);

            throw new Exception(__('Scrub function does not yet exist.'));
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
            'created_at'          => Carbon::now('UTC'),
            'updated_at'          => Carbon::now('UTC'),
            'suppression_list_id' => $this->list->id,
            'relationship'        => $relationship,
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
                $this->columnSupports[$columnIndex] = $support;
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
        foreach ($this->discernSupportsNeeded() as $columnType => $columns) {
            $hashType = array_values($columns)[0];
            $support  = SuppressionListSupport::withoutTrashed()
                ->where('column_type', $columnType)
                ->where('hash_type', $hashType)
                ->where('status', SuppressionListSupport::STATUS_READY)
                ->first();

            if ($support) {
                $supportFound = true;
                foreach (array_keys($columns) as $columnIndex) {
                    $this->columnSupports[$columnIndex] = $support;
                }
            } else {
                $messages[$columnType.'_'.$hashType] = __('This suppression list does not currently support scrubbing $1 with $2.',
                    [$columnType, $hashType ?? __('plaintext')]);
            }
        }
        if (!$supportFound) {
            throw new Exception($messages);
        }

        return $this;
    }

    /**
     * Returns true if the row was scrubbed.
     *
     * @param $row
     *
     * @return bool
     */
    public function scrubRow(&$row)
    {
        $scrub = false;
        if ($this->list && $this->columnSupports) {
            /**
             * @var int $columnIndex
             * @var SuppressionListSupport $support
             */
            foreach ($this->columnSupports as $columnIndex => $support) {
                // Validate/sanitize/hash before attempting a scrub.
                $value = $row[$columnIndex];
                if ($this->getFileHashHelper()->sanitizeColumn($value, $columnIndex, 'input')) {
                    $scrub = (bool) $support->where('content', $value)->first();
                    if ($scrub) {
                        $row = [];
                        break;
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
            /**
             * @var int $columnIndex
             * @var SuppressionListSupport $support
             */
            foreach ($this->columnSupports as $columnIndex => $support) {
                // Validate/sanitize/hash before insertion.
                $value = $row[$columnIndex];
                if ($this->getFileHashHelper()->sanitizeColumn($value, $columnIndex, 'input')) {
                    $support->addContentToQueue($value, $rowIndex);
                    $valid = true;
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
        foreach ($this->columnSupports as $columnIndex => $support) {
            /** @var SuppressionListSupport $support */
            $support->finish();
        }

        return $this;
    }
}
