<?php

namespace App\Helpers;

use App\Models\File;

class FileHashHelper
{
    /** @var File */
    private $file;

    /** @var HashHelper */
    private $hashHelper;

    /** @var FileAnalysisHelper */
    private $fileAnalysisHelper;

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
            if ($this->sanitizeColumn($value, $columnIndex)) {
                $valid = true;
            }
        }

        return $valid;
    }

    /**
     * @param $value
     * @param $columnIndex
     * @param  bool  $binary
     * @param  null  $algoOutput
     *
     * @return string|string[]|null
     */
    public function sanitizeColumn(&$value, $columnIndex, $binary = false, $algoOutput = null)
    {
        if (!empty($value)) {
            $type      = $this->file->input_settings['column_type_'.$columnIndex] ?? null;
            $algoInput = $this->file->input_settings['column_hash_input_'.$columnIndex] ?? null;

            if (!$algoInput) {
                // We are starting with plain text.
                if ($type & FileAnalysisHelper::TYPE_PHONE) {
                    $countryCode = $this->file->input_settings['country'] ?? $this->file->country ?? 'US';
                    $value       = $this->getFileAnalysisHelper()->getPhone($value, $countryCode, true);
                    $value       = preg_replace("/[^0-9]/", '', $value);
                } elseif ($type & FileAnalysisHelper::TYPE_EMAIL) {
                    $value = strtolower(trim($value));
                    $value = $this->getFileAnalysisHelper()->getEmail($value);
                }
            }
            if ($value) {
                if ($algoInput) {
                    $this->getHashHelper()->filter($value, $binary, $algoInput);
                } else {
                    if (!$algoOutput) {
                        if ($this->file->mode & File::MODE_HASH) {
                            // Generate hash for output from the filtered plaintext.
                            $algoOutput = $this->file->input_settings['column_hash_output_'.$columnIndex] ?? null;
                        }
                    }
                    if ($algoOutput) {
                        $this->getHashHelper()->hash($value, $algoOutput, $binary);
                    }
                }
            }
        }

        return (bool) $value;
    }

    /**
     * @return FileAnalysisHelper
     */
    private function getFileAnalysisHelper()
    {
        if (!$this->fileAnalysisHelper) {
            $this->fileAnalysisHelper = new FileAnalysisHelper($this->file);
        }

        return $this->fileAnalysisHelper;
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

    /**
     * @return array
     */
    public function supportedHashTypes()
    {
        return array_keys($this->getHashHelper()->listChoices());
    }
}
