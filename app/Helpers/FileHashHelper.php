<?php

namespace App\Helpers;

use App\File;

class FileHashHelper
{
    /** @var File */
    protected $file;

    /** @var HashHelper */
    protected $hashHelper;

    /**
     * FileHashHelper constructor.
     *
     * FileHashHelper constructor.
     *
     * @param  File  $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * @param $row
     */
    public function hashRow(&$row)
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
