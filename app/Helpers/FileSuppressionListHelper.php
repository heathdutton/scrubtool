<?php

namespace App\Helpers;

use App\File;
use App\FileSuppressionList;
use App\SuppressionList;
use App\SuppressionListSupport;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class FileSuppressionListHelper
{
    /** @var array */
    const COLUMN_TYPES = [
        FileAnalysisHelper::TYPE_EMAIL,
        FileAnalysisHelper::TYPE_PHONE,
    ];

    /** @var File */
    private $file;

    /** @var SuppressionList */
    private $destinationSuppressionList;

    /** @var array */
    private $scrubSuppressionLists = [];

    /** @var FileHashHelper */
    private $fileHashHelper;

    /** @var array */
    private $columnSupports = [];

    /**
     * FileSuppressionListHelper constructor.
     *
     * @param  File  $file
     *
     * @throws Exception
     */
    public function __construct(File $file)
    {
        $this->file = $file;

        if (
            empty($this->file->user_id)
            && $this->file->mode & (File::MODE_LIST_CREATE | File::MODE_LIST_APPEND | File::MODE_LIST_REPLACE)
        ) {
            throw new Exception(__('File not associated to a user.'));
        }

        if ($this->file->mode & File::MODE_LIST_CREATE) {
            $this->destinationSupports();
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
            $this->scrubSupports();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function destinationSupports()
    {
        /** @var SuppressionList $list */
        $list = $this->file->suppressionLists
            ->where('pivot.relationship', FileSuppressionList::REL_FILE_TO_LIST)
            ->first();
        foreach ($this->discernSupportsNeeded() as $columnType => $columns) {

            $support = $list->suppressionListSupports()
                ->firstOrCreate([
                    'column_type' => $columnType,
                    'hash_type'   => array_values($columns)[0],
                ], [
                    'status' => SuppressionListSupport::STATUS_BUILDING,
                ]);

            foreach (array_keys($columns) as $columnIndex) {
                $this->columnSupports[$columnIndex] = (new Collection)->add($support);
            }
        }
        if (!$this->columnSupports) {
            throw new Exception(__('There was no Email or Phone column to build your suppression list from. Please make sure you indicate the file contents and try again.'));
        }

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
     * Evaluate if the suppression list in question supports the column/hash types in use here before beginning the
     * scrubbing process, while also building the column map.
     *
     * @return $this
     * @throws Exception
     */
    private function scrubSupports()
    {
        $messages     = [];
        $supportFound = false;
        if ($this->file) {
            $listIds = $this->scrubSuppressionLists()->pluck('id');
            if ($listIds) {
                foreach ($this->discernSupportsNeeded() as $columnType => $columns) {
                    $hashType = array_values($columns)[0];
                    $supports = SuppressionListSupport::query()
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
                        $messages[$columnType.'_'.$hashType] = __('Unable to scrub $1 with $2.',
                            [$columnType, $hashType ?? __('plaintext')]);
                    }
                }
            }
        }
        // Partial support is allowed, but if no support is found we'll throw an error.
        if (!$supportFound) {
            throw new Exception(__('Selected suppression list/s cannot support your file.').' '.implode(' ',
                    $messages));
        }

        return $this;
    }

    /**
     * @return Collection
     */
    private function scrubSuppressionLists()
    {
        if (!$this->scrubSuppressionLists) {
            $this->scrubSuppressionLists = $this->file->suppressionLists
                ->where('pivot.relationship', FileSuppressionList::REL_LIST_USED_TO_SCRUB);
        }

        return $this->scrubSuppressionLists;
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
        if ($this->columnSupports) {
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
        if ($this->columnSupports) {
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
     * @return int|mixed
     * @throws Exception
     */
    public function finish()
    {
        $persisted = 0;
        foreach ($this->columnSupports as $columnIndex => $supports) {
            /** @var SuppressionListSupport $support */
            foreach ($supports as $support) {
                $persisted = max($persisted, $support->finish());
            }
        }

        return $persisted;
    }

    /**
     * @return SuppressionList
     */
    private function destinationSuppressionList()
    {
        if (!$this->destinationSuppressionList) {
            $this->destinationSuppressionList = $this->file->suppressionLists
                ->where('pivot.relationship', FileSuppressionList::REL_FILE_TO_LIST)
                ->first();
        }

        return $this->destinationSuppressionList;
    }

}
