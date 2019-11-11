<?php

namespace App\Helpers;

use App\File;

class FileHashHelper
{
    /** @var File */
    private $file;

    /** @var HashHelper */
    private $hashHelper;

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
     *
     * @return bool
     */
    public function modifyRowForOutput(&$row)
    {
        $valid = false;
        foreach ($row as $columnIndex => &$value) {
            if ($this->sanitizeColumn($value, $columnIndex, 'output')) {
                $valid = true;
            }
        }

        return $valid;
    }

    /**
     * @param $value
     * @param $columnIndex
     * @param  string  $mode
     * @param  null  $algo
     * @param  bool  $binary
     *
     * @return string|string[]|null
     */
    public function sanitizeColumn(&$value, $columnIndex, $mode = 'input', $algo = null, $binary = false)
    {
        if (!empty($value)) {
            $type = $this->file->input_settings['column_type_'.$columnIndex] ?? null;

            if ($type & FileAnalysisHelper::TYPE_PHONE) {
                $countryCode = $this->file->input_settings['country'] ?? $this->file->country ?? 'US';
                $value       = FileAnalysisHelper::getPhone($value, $countryCode, true);
                $value       = preg_replace("/[^0-9]/", '', $value);
            }

            if ($type & FileAnalysisHelper::TYPE_EMAIL) {
                $value = strtolower(trim($value));
                $value = FileAnalysisHelper::getEmail($value);
            }

            if ($value) {
                $algo = $algo ?? $this->file->input_settings['column_hash_'.$mode.'_'.$columnIndex] ?? null;
                if ($algo) {
                    $this->getHashHelper()->hash($value, $algo, $binary);
                }
            }
        }

        return (bool) $value;
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
