<?php

namespace App\Helpers;

class FileHashHelper
{
    protected $inputSettings = [];

    public function __construct($inputSettings)
    {
        $this->inputSettings = $inputSettings;
    }

    public function hashRow(&$row) {
        foreach ($row as $rowIndex => $value) {
            $tmp = 12;
        }
    }
}
