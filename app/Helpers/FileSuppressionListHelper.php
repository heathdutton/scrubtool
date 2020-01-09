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

    /** @var array */
    private $formValues = [];

    /**
     * FileSuppressionListHelper constructor.
     *
     * @param  File  $file
     * @param  array  $formValues  Optionally provided for form validation.
     * @param  null  $scrubSuppressionLists  Optionally provided for form validation.
     *
     * @throws Exception
     */
    public function __construct(
        File $file,
        $formValues = [],
        $scrubSuppressionLists = null
    ) {
        $this->file                  = $file;
        $this->formValues            = $formValues;
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
            $this->destinationSupports(FileSuppressionList::REL_FILE_INTO_LIST);
        }

        if ($this->file->mode & File::MODE_LIST_REPLACE) {
            $this->destinationSupports(FileSuppressionList::REL_FILE_REPLACE_LIST);
        }

        // Scrub against an existing Suppression List.
        if ($this->file->mode & File::MODE_SCRUB) {
            $this->scrubSupports();
        }

        return $this;
    }

    /**
     * @param $relationship
     *
     * @return $this
     * @throws Exception
     */
    private function destinationSupports($relationship)
    {
        if ($this->errors) {
            return $this;
        }
        /** @var SuppressionList $suppressionList */
        if ($suppressionList = $this->file->suppressionLists->whereIn('pivot.relationship', $relationship)->first()) {
            foreach ($this->discernSupportsNeeded() as $columnType => $columns) {
                $supports  = new Collection;
                $hashTypes = array_unique($columns);
                if (count($hashTypes) > 1) {
                    // While we could support this, it's not advisable due to how quickly it can make things confusing.
                    throw new Exception(__('Multiple hash types were used for :columnType in the same suppression list. This is not supported.',
                        [
                            'columnType' => __('column_types.plural.'.$columnType),
                        ]));
                }
                $hashType   = $hashTypes[0];
                $attributes = [
                    'suppression_list_id' => $suppressionList->id,
                    'column_type'         => $columnType,
                    'hash_type'           => $hashType,
                ];
                // Find preexisting supports (for appending/replacing).
                if ($this->file->mode & (File::MODE_LIST_REPLACE | File::MODE_LIST_APPEND)) {
                    $supports = SuppressionListSupport::findPreferredSupports($attributes);
                    if ($hashType) {
                        // Hash was provided.
                        if (1 === $supports->where('hash_type', '=', $hashType)->count()) {
                            // Hash has a match.
                            if (0 === $supports->where('hash_type', '=', null)->count()) {
                                // There is a plaintext support.
                                // We are incompatible because we cannot fully match existing supports.
                                $this->errors['suppression_list_append'] = __('Suppression list was created with :columnType in plaintext. You can not append this list using hashed :columnType. Append with plaintext or create a new suppression list.',
                                    [
                                        'columnType' => __('column_types.plural.'.$columnType),
                                    ]);
                            } else {
                                // There is NO plaintext support.
                                // The support count must be 1 otherwise we will not fully cover the supports.
                                if (1 < $supports->count()) {
                                    throw new Exception(__('Multiple hash types exist without plaintext for :columnType. This is not supported. This suppression list cannot be appended as a result.',
                                        [
                                            'columnType' => __('column_types.plural.'.$columnType),
                                        ]));
                                }
                            }
                        } else {
                            // Hash has NO match.
                            // We are incompatible.
                            $this->errors['suppression_list_append'] = __('Suppression list was created with :columnType in :hashTypes. Your file must match this or be in plaintext to append to it.',
                                [
                                    'columnType' => __('column_types.plural.'.$columnType),
                                    'hashTypes'  => implode(', ', $supports->pluck('hash_type')->toArray()),
                                ]);
                        }
                    }
                }
                // Create new support if necessary.
                if (!$this->errors && !$supports->count() && !$this->formValues) {
                    $support = $suppressionList->suppressionListSupports()
                        ->orderBy('hash_type', 'ASC')
                        ->firstOrCreate($attributes, [
                            'status' => SuppressionListSupport::STATUS_BUILDING,
                        ]);
                    // Check support status.
                    if ($support->status & SuppressionListSupport::STATUS_TO_BE_REPLACED) {
                        $this->errors[] = __('Suppression list :list is in the process of rebuilding. Please try later.',
                            ['list' => $suppressionList->name]);
                    } else {
                        $supports->add($support);
                    }
                }
                if (!$this->errors && $supports->count()) {
                    foreach (array_keys($columns) as $columnIndex) {
                        if (!isset($this->columnSupports[$columnIndex])) {
                            $this->columnSupports[$columnIndex] = $supports;
                        } else {
                            foreach ($supports as $support) {
                                if (!$this->columnSupports[$columnIndex]->contains('id', $support->id)) {
                                    $this->columnSupports[$columnIndex]->add($support);
                                }
                            }
                        }
                    }
                } else {
                    return $this;
                }
            }
        }

        $this->prepSupportsForAdditions($relationship);

        return $this;
    }

    /**
     * Suppression lists can contain email/phone/both at the moment.
     *
     * @return array
     */
    private function discernSupportsNeeded()
    {
        // Suppression lists can contain email/phone/both at the moment.
        $supportsNeeded = [];
        foreach (self::COLUMN_TYPES as $columnType) {
            $columns = $this->file->getColumnsWithInputHashes($columnType, $this->formValues);
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
     * Set to pending/replacing as appropriate before building.
     *
     * @param $relationship
     */
    private function prepSupportsForAdditions($relationship)
    {
        if (
            $relationship
            && !$this->errors
            && count($this->columnSupports)
            && !$this->formValues
        ) {
            // Mark supports as being replaced/appended as appropriate.
            foreach ($this->columnSupports as $columnIndex => $supports) {
                $supports->each(function ($support, $key) use ($relationship) {
                    if ($support->status !== SuppressionListSupport::STATUS_BUILDING) {
                        // Pre-existing supports can continue to be used during the update process (appending or replacing).
                        if ($relationship == FileSuppressionList::REL_FILE_REPLACE_LIST) {
                            $support->status = SuppressionListSupport::STATUS_TO_BE_REPLACED;
                            $support->save();
                        } elseif ($relationship == FileSuppressionList::REL_FILE_INTO_LIST) {
                            $support->status = SuppressionListSupport::STATUS_TO_BE_APPENDED;
                            $support->save();
                        }
                    }
                });
            }
        }
    }

    /**
     * Evaluate if the suppression list in question supports the column/hash types in use here before beginning the
     * scrubbing process, while also building the column map.
     *
     * @return $this
     */
    private function scrubSupports()
    {
        if ($this->errors) {
            return $this;
        }
        $unsupportedListIds = [];
        if ($this->file) {
            if ($suppressionListIds = $this->scrubSuppressionLists()->pluck('id')->toArray()) {
                $unsupportedListIds += array_flip($suppressionListIds);
                $supportedListIds   = [];
                $issues             = [];
                foreach ($this->discernSupportsNeeded() as $columnType => $columns) {
                    $hashType = array_values($columns)[0];
                    $supports = SuppressionListSupport::findPreferredSupports([
                        'suppression_list_id' => array_keys($unsupportedListIds),
                        'column_type'         => $columnType,
                        'hash_type'           => $hashType,
                    ]);

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
                /** @var SuppressionListSupport $support */
                foreach ($supports as $support) {
                    $value = $row[$columnIndex];
                    if (
                        !isset($valuesByHashType[$support->hash_type])
                        && $this->getFileHashHelper()->sanitizeColumn($value, $columnIndex, true,
                            $support->hash_type)
                    ) {
                        $valuesByHashType[$support->hash_type] = $value;
                    }
                }
                /** @var SuppressionListSupport $support */
                foreach ($supports as $support) {
                    if (isset($valuesByHashType[$support->hash_type])) {
                        if ($support->getContent()->where('content',
                            $valuesByHashType[$support->hash_type])->exists()) {
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
            if (!$this->insertIds && $rowIndex) {
                $rowIndex = 0;
            }
            foreach ($this->columnSupports as $columnIndex => $supports) {
                $valuesByHashType = [];
                /** @var SuppressionListSupport $support */
                foreach ($supports as $support) {
                    $value = $row[$columnIndex];
                    if (
                        !isset($valuesByHashType[$support->hash_type])
                        && $this->getFileHashHelper()->sanitizeColumn($value, $columnIndex, true,
                            $support->hash_type)
                    ) {
                        $valuesByHashType[$support->hash_type] = $value;
                    }
                }
                /** @var SuppressionListSupport $support */
                foreach ($supports as $support) {
                    if (isset($valuesByHashType[$support->hash_type])) {
                        $support->addContentToQueue($valuesByHashType[$support->hash_type], $rowIndex);
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
                try {
                    $notification = new SuppressionListReadyNotification($suppressionList);
                    if ($suppressionList->user) {
                        // Notify the user.
                        $suppressionList->user->notify($notification);
                    } else {
                        // Notify the owner of the file if possible.
                        $suppressionList->file->notify($notification);
                    }
                } catch (Exception $exception) {
                    // Do not abort or delete the file for exceptions regarding notifications.
                    report($exception);
                }
            }
        }

        return $persisted;
    }
}
