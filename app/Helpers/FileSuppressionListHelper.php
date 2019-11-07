<?php

namespace App\Helpers;

use App\File;
use App\SuppressionList;
use App\SuppressionListSupport;
use Exception;

class FileSuppressionListHelper
{
    /** @var array */
    const COLUMN_TYPES = [
        FileAnalysisHelper::TYPE_EMAIL,
        FileAnalysisHelper::TYPE_PHONE,
    ];

    /** @var File */
    protected $file;

    /** @var SuppressionList */
    protected $list;

    /** @var FileHashHelper */
    protected $fileHashHelper;

    /** @var array */
    private $columnSupports = [];

    /**
     * FileSuppressionListHelper constructor.
     *
     * @param  File  $file
     * @param  SuppressionList|null  $list
     *
     * @throws Exception
     */
    public function __construct(File $file, SuppressionList $list = null)
    {
        $this->file = $file;

        return $this->setList($list);
    }

    /**
     * @param  SuppressionList|null  $list
     *
     * @return $this
     * @throws Exception
     */
    private function setList(SuppressionList $list = null)
    {
        $this->list = $list;
        if (!$this->list) {
            if ($this->file->mode & File::MODE_LIST_CREATE) {
                if (!$this->file->user_id) {
                    throw new Exception(__('The file must be associated with a logged in user in order to be associated with a suppression list. Please log in and try again.'));
                }

                $this->list = new SuppressionList([], $this->file);

                // Suppression lists can contain email/phone/both at the moment.
                $supports             = [];
                $this->columnSupports = [];
                foreach (self::COLUMN_TYPES as $type) {
                    $columns = $this->file->getColumnsWithInputHashes($type);
                    if ($columns) {
                        if (count(array_unique($columns)) > 1) {
                            throw new Exception(__('Multiple hash types were used for an email or phone columns. This is not supported. Please only use one hash type, or use plain-text so that all hash types are supported.'));
                        }
                        // Should only have one hash type for this column type.
                        $support = new SuppressionListSupport([
                            'column_type' => $type,
                            'hash_type'   => array_values($columns)[0],
                            'status'      => SuppressionListSupport::STATUS_BUILDING,
                        ]);
                        foreach (array_keys($columns) as $columnIndex) {
                            $this->columnSupports[$columnIndex] = $support;
                        }
                        $supports[] = $support;
                    }
                }
                if (!$supports) {
                    throw new Exception(__('There was no Email or Phone column to build your suppression list from. Please make sure you indicate the file contents and try again.'));
                }
                $this->list->files()->attach($this->file->id);
                $this->list->save();
                $this->list->supports()->saveMany($supports);
            }
            if ($this->file->mode & (File::MODE_LIST_APPEND | File::MODE_LIST_REPLACE)) {
                // @todo - Load and confirm existing list.
                throw new Exception(__('List append function does not yet exist.'));
            }
            if ($this->file->mode & File::MODE_LIST_REPLACE) {
                // @todo - Drop all tables associated with the list to start fresh.
                throw new Exception(__('List replace function does not yet exist.'));
            }
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
            $support->persistQueue();
            $support->finish();
        }

        return $this;
    }
}
