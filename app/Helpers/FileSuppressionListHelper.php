<?php

namespace App\Helpers;

use App\Models\File;
use App\Models\FileSuppressionList;
use App\Models\SuppressionList;
use App\Models\SuppressionListSupport;
use App\Notifications\SuppressionListReadyNotification;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class FileSuppressionListHelper
{
    /** @var array Only these types are used for suppression lists at this time. */
    const COLUMN_TYPES = [
        FileAnalysisHelper::TYPE_EMAIL,
        FileAnalysisHelper::TYPE_PHONE,
    ];

    /** @var File */
    private $file;

    /** @var Collection */
    private $scrubSuppressionLists;

    /** @var FileHashHelper */
    private $fileHashHelper;

    /** @var array */
    private $columnSupports = [];

    /** @var bool */
    private $insertIds;

    /** @var array */
    private $errors = [];

    /** @var array */
    private $warnings = [];


    /**
     * FileSuppressionListHelper constructor.
     *
     * @param  File  $file
     * @param  array  $formValues
     * @param  null  $scrubSuppressionLists
     *
     * @throws Exception
     */
    public function __construct(
        File $file,
        $formValues = [],
        $scrubSuppressionLists = null
    ) {
        $this->file                  = $file;
        $this->scrubSuppressionLists = $scrubSuppressionLists;

        if (
            empty($this->file->user_id)
            && $this->file->mode & (File::MODE_LIST_CREATE | File::MODE_LIST_APPEND | File::MODE_LIST_REPLACE)
        ) {
            $this->errors[] = __('File not associated to a user.');
        }

        // Do not insert IDs for append mode, because that could prevent insertion or overwrite.
        $this->insertIds = (bool) ($this->file->mode ^ File::MODE_LIST_APPEND);

        if ($this->file->mode & (File::MODE_LIST_CREATE | File::MODE_LIST_APPEND)) {
            $this->destinationSupports(FileSuppressionList::REL_FILE_INTO_LIST, $formValues);
        }

        if ($this->file->mode & File::MODE_LIST_REPLACE) {
            $this->destinationSupports(FileSuppressionList::REL_FILE_REPLACE_LIST, $formValues);
        }

        // Scrub against an existing Suppression List.
        if ($this->file->mode & File::MODE_SCRUB) {
            $this->scrubSupports($formValues);
        }

        return $this;
    }

    /**
     * @param $relationship
     * @param  array  $formValues
     *
     * @return $this
     * @throws Exception
     */
    private function destinationSupports($relationship, $formValues = [])
    {
        /** @var SuppressionList $suppressionList */
        if ($suppressionList = $this->file->suppressionLists->whereIn('pivot.relationship', $relationship)->first()) {
            foreach ($this->discernSupportsNeeded($formValues) as $columnType => $columns) {
                $support = $suppressionList->suppressionListSupports()
                    ->firstOrCreate([
                        'column_type' => $columnType,
                        'hash_type'   => array_values($columns)[0],
                    ], [
                        'status' => SuppressionListSupport::STATUS_BUILDING,
                    ]);
                if ($support->status !== SuppressionListSupport::STATUS_BUILDING) {
                    // Pre-existing supports can continue to be used during the update process (appending or replacing).
                    if ($relationship == FileSuppressionList::REL_FILE_REPLACE_LIST) {
                        $support->status = SuppressionListSupport::STATUS_TO_BE_REPLACED;
                    } else {
                        $support->status = SuppressionListSupport::STATUS_TO_BE_APPENDED;
                    }
                    $support->save();
                }
                foreach (array_keys($columns) as $columnIndex) {
                    if (!isset($this->columnSupports[$columnIndex])) {
                        $this->columnSupports[$columnIndex] = (new Collection)->add($support);
                    } else {
                        if (!$this->columnSupports[$columnIndex]->contains('id', $support->id)) {
                            $this->columnSupports[$columnIndex]->add($support);
                        }
                    }
                }
            }
        }
        if (!$this->columnSupports) {
            $this->errors['static_columns'] = __('There was no Email or Phone column to build your suppression list from.');
        }

        return $this;
    }

    /**
     * Suppression lists can contain email/phone/both at the moment.
     *
     * @param  array  $formValues
     *
     * @return array
     */
    private function discernSupportsNeeded($formValues = [])
    {
        // Suppression lists can contain email/phone/both at the moment.
        $supportsNeeded = [];
        foreach (self::COLUMN_TYPES as $columnType) {
            $columns = $this->file->getColumnsWithInputHashes($columnType, $formValues);
            if ($columns) {
                if (count(array_unique($columns)) > 1) {
                    $this->errors[$columnType] = __('Multiple hash types were used for :type. This is not supported. Please only use one hash type, or use plain-text.',
                        ['type' => __('column_types.plural.'.$columnType)]);
                }
                $supportsNeeded[$columnType] = $columns;
            }
        }

        return $supportsNeeded;
    }

    /**
     * Evaluate if the suppression list in question supports the column/hash types in use here before beginning the
     * scrubbing process, while also building the column map.
     *
     * @param  array  $formValues
     *
     * @return $this
     */
    private function scrubSupports($formValues = [])
    {
        $unsupportedListIds = [];
        if ($this->file) {
            if ($suppressionListIds = $this->scrubSuppressionLists()->pluck('id')->toArray()) {
                $unsupportedListIds += array_flip($suppressionListIds);
                $supportedListIds   = [];
                $issues             = [];
                $supportedHashTypes = $this->getFileHashHelper()->supportedHashTypes();
                foreach ($this->discernSupportsNeeded($formValues) as $columnType => $columns) {
                    $q = SuppressionListSupport::query();
                    $q->whereIn('suppression_list_id', array_keys($unsupportedListIds));
                    $q->where('column_type', $columnType);
                    $hashType = array_values($columns)[0];
                    if (null === $hashType && count($supportedHashTypes)) {
                        $q->where(function ($q) use ($supportedHashTypes) {
                            $q->whereNull('hash_type');
                            $q->orWhereIn('hash_type', $supportedHashTypes);
                        });
                    } else {
                        $q->where('hash_type', $hashType);
                    }
                    $q->where('status', SuppressionListSupport::STATUS_READY);
                    $q->groupBy(['suppression_list_id']);
                    $q->orderBy('hash_type', 'ASC');
                    $supports = $q->get();

                    if ($supports->count()) {
                        foreach (array_keys($columns) as $columnIndex) {
                            $this->columnSupports[$columnIndex] = $supports;
                            foreach ($supports as $columnSupport) {
                                unset($unsupportedListIds[$columnSupport->suppressionList->id]);
                                $supportedListIds[$columnSupport->suppressionList->id] = null;
                            }
                        }
                    } else {
                        $issues[$columnType.'_'.$hashType] = __('Unable to scrub :columnType in :hashType.',
                            [
                                'columnType' => __('column_types.plural.'.$columnType),
                                'hashType'   => $hashType ?? __('plaintext'),
                            ]);
                    }
                }
                // Remaining suppression lists were not supported.
                if (count($unsupportedListIds)) {
                    foreach ($this->scrubSuppressionLists()
                                 ->whereIn('id', array_keys($unsupportedListIds)) as $suppressionList) {
                        $key                 = 'suppression_list_use_'.$suppressionList->id;
                        $type                = $suppressionList->required ? 'warning' : 'error';
                        $this->{$type}[$key] = __('This suppression list cannot be used with your file.').
                            implode(' ', $issues);
                        $issues              = [];
                    }
                }
                // If NO supported lists were found, we should elevate warnings as errors.
                if (!count($supportedListIds) && !$this->errors) {
                    if ($this->warnings) {
                        $this->errors   = array_merge($this->errors, $this->warnings);
                        $this->warnings = [];
                    } else {
                        $this->errors['static_suppression_list_use'] = __('Suppression list selection cannot scrub this file. Make sure the column types overlap with at least one suppression list.');
                    }
                }
            }
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
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
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
                // Get sanitized values for each hash type needed.
                $valuesByHashType = [];
                foreach ($supports as $support) {
                    $value = $row[$columnIndex];
                    if (
                        !isset($valuesByHashType[$support->hash_type])
                        && $this->getFileHashHelper()->sanitizeColumn($value, $columnIndex, 'output', true,
                            $support->hash_type)
                    ) {
                        $valuesByHashType[$support->hash_type] = $value;
                    }
                }
                foreach ($supports as $support) {
                    if (isset($valuesByHashType[$support->hash_type])) {
                        if ($support->getContent()->where('content', $valuesByHashType[$support->hash_type])->exists()) {
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
            if (!$this->insertIds && $rowIndex) {
                $rowIndex = 0;
            }
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

        // Notify that the Suppression List is ready.
        if ($persisted && $this->file->mode & (File::MODE_LIST_CREATE | File::MODE_LIST_APPEND | File::MODE_LIST_REPLACE)) {
            $suppressionList = null;
            if ($this->file->mode & (File::MODE_LIST_CREATE | File::MODE_LIST_APPEND)) {
                $suppressionList = $this->file->suppressionLists
                    ->whereIn('pivot.relationship', FileSuppressionList::REL_FILE_INTO_LIST)
                    ->first();
            }
            if ($this->file->mode & File::MODE_LIST_REPLACE) {
                $suppressionList = $this->file->suppressionLists
                    ->whereIn('pivot.relationship', FileSuppressionList::REL_FILE_REPLACE_LIST)
                    ->first();
            }
            if ($suppressionList) {
                $notification = new SuppressionListReadyNotification($suppressionList);
                if ($suppressionList->user) {
                    // Notify the user.
                    $suppressionList->user->notify($notification);
                } else {
                    // Notify the owner of the file if possible.
                    $suppressionList->file->notify($notification);
                }
            }
        }

        return $persisted;
    }
}
