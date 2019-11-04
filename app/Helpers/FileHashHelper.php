<?php

namespace App\Helpers;

class FileHashHelper
{
    /** @var array */
    protected $inputSettings = [];

    /** @var HashHelper */
    protected $hashHelper;

    /**
     * FileHashHelper constructor.
     *
     * @param $inputSettings
     */
    public function __construct($inputSettings)
    {
        $this->inputSettings = $inputSettings;
    }

    /**
     * @param $row
     */
    public function hashRow(&$row)
    {
        foreach ($row as $rowIndex => &$value) {
            if (
                !empty($value)
                && isset($this->inputSettings['column_hash_output_'.$rowIndex])
            ) {
                $algo = $this->inputSettings['column_hash_output_'.$rowIndex];
                $this->getHashHelper()->hash($value, $algo);
            }
        }
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
