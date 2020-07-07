<?php

namespace App\Helpers;

use App\Models\SuppressionListSupport;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class ApiSuppressionListHelper
{

    /** @var array Only these types are used for suppression lists at this time. */
    const COLUMN_TYPES = [
        FileAnalysisHelper::TYPE_EMAIL,
        FileAnalysisHelper::TYPE_PHONE,
    ];

    /** @var Collection */
    private $scrubSuppressionLists;

    /** @var FileHashHelper */
    private $fileHashHelper;

    /** @var array */
    private $columnSupports = [];

    /** @var array */
    private $errors = [];

    /** @var array */
    private $warnings = [];

    /** @var int */
    private $type;

    /**
     * ApiSuppressionListHelper constructor.
     *
     * @param $scrubSuppressionLists
     * @param  int  $type
     */
    public function __construct(
        $scrubSuppressionLists,
        $type = FileAnalysisHelper::TYPE_EMAIL
    ) {
        $this->scrubSuppressionLists = $scrubSuppressionLists;
        $this->type                  = $type;

        // Scrub against an existing Suppression List.
        $this->scrubSupports();

        return $this;
    }

    /**
     * @return $this
     */
    private function scrubSupports()
    {
        if ($this->errors) {
            return $this;
        }
        $unsupportedListIds = [];
        if ($suppressionListIds = $this->scrubSuppressionLists->pluck('id')->toArray()) {
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
                foreach ($this->scrubSuppressionLists
                             ->whereIn('id', array_keys($unsupportedListIds)) as $suppressionList) {
                    $key                 = 'suppression_list_use_'.$suppressionList->id;
                    $type                = $suppressionList->required ? 'warning' : 'error';
                    $this->{$type}[$key] = __('This suppression list cannot be used with your record.').
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
                    $this->errors['static_suppression_list_use'] = __('Suppression list selection cannot scrub this record.');
                }
            }
        }

        return $this;
    }

    /**
     * Suppression lists can contain email/phone/both at the moment.
     *
     * @return array
     */
    private function discernSupportsNeeded()
    {
        $supportsNeeded = [];
        foreach (self::COLUMN_TYPES as $columnType) {
            if ($this->type === $columnType) {
                // Only one column with api currently.
                $supportsNeeded[$columnType] = [0];
            }
        }

        return $supportsNeeded;
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
                            $support->hash_type, $this->type, null)
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
            $this->fileHashHelper = new FileHashHelper();
        }

        return $this->fileHashHelper;
    }

}
