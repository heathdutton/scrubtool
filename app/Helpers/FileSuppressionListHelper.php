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

    /** @var HashHelper */
    protected $hashHelper;

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
     * @param  SuppressionList  $list
     *
     * @return $this
     * @throws Exception
     */
    private function setList(SuppressionList $list)
    {
        $this->list = $list;
        if (!$this->list) {
            if ($this->file->mode & File::MODE_LIST_CREATE) {
                if (!$this->file->user_id) {
                    throw new Exception(__('The file must be associated with a logged in user in order to be associated with a suppression list. Please log in and try again.'));
                }
                $this->list          = new SuppressionList();
                $this->list->user_id = $this->file->user_id;
                $this->list->name    = $this->choseListNameFromFileName($this->file->name);

                // @todo - Making a suppression list global is an admin feature only, but could exist in this UI.
                $this->list->global = 0;

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
                            $columnSupports[$columnIndex] = $support;
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
            if ($this->file->mode & File::MODE_LIST_APPEND || $this->file->mode & File::MODE_LIST_REPLACE) {
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
     * @param $fileName
     *
     * @return string|string[]|null
     */
    private function choseListNameFromFileName($fileName)
    {
        $fileName = trim($fileName);
        $fileName = preg_replace('/[^a-z0-9 ]/i', '', $fileName);
        $fileName = preg_replace('/\s+/', ' ', $fileName);
        $fileName = ucwords($fileName);

        return $fileName;
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
        $result = false;
        if ($this->list && $this->columnSupports) {
            foreach ($this->columnSupports as $columnIndex => $support) {
                if (!empty($row[$columnIndex])) {
                    $support->addContentToQueue($row[$columnIndex], $rowIndex);
                }
            }
        }

        return $result;
    }

    /**
     * @return $this
     */
    public function persistQueues()
    {
        foreach ($this->columnSupports as $columnIndex => $support) {
            $support->persistQueue();
        }

        return $this;
    }

    /**
     * @param $row
     *
     * @return bool
     * @deprecated
     *
     */
    private function importRow(&$row)
    {
        $result = false;
        foreach ($row as $rowIndex => &$value) {
            if (
                !empty($value)
                && isset($this->file->input_settings['column_hash_output_'.$rowIndex])
            ) {
                $algo = $this->file->input_settings['column_hash_output_'.$rowIndex] ?? null;
                if ($algo) {
                    $type = $this->file->input_settings['column_type_'.$rowIndex] ?? FileAnalysisHelper::TYPE_UNKNOWN;
                    if ($type & FileAnalysisHelper::TYPE_PHONE) {
                        // Convert phone number to E.164 without + for deterministic hashing.
                        $countryCode = $this->file->input_settings['country'] ?? $this->file->country ?? 'US';
                        $value       = FileAnalysisHelper::getPhone($value, $countryCode, true);
                        $value       = preg_replace("/[^0-9]/", '', $value);
                    }
                    if ($type & FileAnalysisHelper::TYPE_EMAIL) {
                        $value = strtolower(trim($value));
                    }
                    $this->getHashHelper()->hash($value, $algo);
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * @return HashHelper
     */
    private function getHashHelper()
    {
        if (!$this->hashHelper) {
            $this->hashHelper = new HashHelper();
        }

        return $this->hashHelper;
    }
}
