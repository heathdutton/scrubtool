<?php

namespace App\Helpers;

use libphonenumber\Leniency;
use libphonenumber\PhoneNumberUtil;

class FileAnalysisHelper
{
    const TYPE_EMAIL   = 1;

    const MODE_HASH    = 2;

    const TYPE_PHONE   = 4;

    const TYPE_UNKNOWN = 8;

    protected $columnCount = 0;

    protected $columnNames = [];

    protected $columnTypes = [];

    protected $columnHashes = [];

    protected $hashHelper;

    protected $rowIsHeader = false;

    protected $rowData = [];

    /** @var array Simplified strings that indicate the row may be a header. */
    private $headerWhitelist = [
        'email',
        'phone',
        'mobile',
        'cell',
        'firstname',
        'fname',
        'lastname',
        'lname',
        'ip',
        'ipaddress',
        'id',
        'date',
        'dateadded',
        'datecreated',
        'dateidentified',
        'url',
        'timestamp',
        'time',
    ];

    /** @var array Simplified strings that identify the column type with fair certainty. */
    private $typeIdentifiers = [
        'email'     => self::TYPE_EMAIL,
        'mail'      => self::TYPE_EMAIL,
        'phone'     => self::TYPE_PHONE,
        'home'      => self::TYPE_PHONE,
        'homephone' => self::TYPE_PHONE,
        'ph'        => self::TYPE_PHONE,
        'cell'      => self::TYPE_PHONE,
        'mobile'    => self::TYPE_PHONE,
        'hash'      => self::MODE_HASH,
        'emailhash' => self::MODE_HASH | self::TYPE_EMAIL,
        'hashemail' => self::MODE_HASH | self::TYPE_EMAIL,
        'phonehash' => self::MODE_HASH | self::TYPE_PHONE,
        'hashphone' => self::MODE_HASH | self::TYPE_PHONE,
    ];

    /**
     * @param $row
     * @param $rowIndex
     *
     * @return $this
     */
    public function parseRow($row, $rowIndex)
    {
        $this->padColumns($row);

        if ($this->rowIsHeader($row, $rowIndex)) {
            $this->detectColumnsFromHeader($row);
        } else {
            $this->detectColumnsFromRow($row, $rowIndex);
        }

        $this->rowData = $row;

        return $this;
    }

    /**
     * Get the full column count as the file is processed, and pad out our arrays for other checks.
     *
     * @param $row
     */
    private function padColumns($row)
    {
        $count = count($row);
        if ($this->columnCount < $count) {
            $this->columnNames  = array_pad($this->columnNames, $count, null);
            $this->columnHashes = array_pad($this->columnHashes, $count, null);
            $this->columnTypes  = array_pad($this->columnTypes, $count, self::TYPE_UNKNOWN);
            $this->columnCount  = $count;
        }
    }

    /**
     * @param $row
     * @param $rowIndex
     *
     * @return bool
     */
    private function rowIsHeader($row, $rowIndex)
    {
        $this->rowIsHeader = false;

        if (
            !count(array_filter($this->columnNames))
            && $rowIndex < 5
            && count(array_filter($row))
        ) {
            foreach ($row as $value) {
                if ($this->isEmail($value)) {
                    break;
                } elseif (in_array($this->simplify($value), $this->headerWhitelist)) {
                    $this->rowIsHeader = true;
                    break;
                }
            }
        }

        return $this->rowIsHeader;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function isEmail($value)
    {
        return (bool) filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Simplify a column header/value for faster comparisons.
     *
     * @param $value
     *
     * @return string
     */
    private function simplify($value)
    {
        return strtolower(preg_replace('/[^a-z]/i', '', $value));
    }

    /**
     * Extract column names and types (if easily discernible) from a header row.
     *
     * @param $row
     */
    private function detectColumnsFromHeader($row)
    {
        foreach ($row as $i => $value) {
            // Try not to manipulate the column headers more than necessary so that output matches input.
            $this->columnNames[$i] = trim((string) $value);

            // Assume type based on header contents (can be overridden later).
            $this->columnTypes[$i] = self::TYPE_UNKNOWN;
            $simple                = $this->simplify($value);
            if (isset($this->typeIdentifiers[$simple])) {
                $this->columnTypes[$i] = $this->typeIdentifiers[$simple];
            } elseif (in_array($simple, $this->getHashHelper()->list(false, true))) {
                $this->columnTypes[$i] = self::MODE_HASH;
            }
        }
        $this->columnCount = count($this->columnNames);
    }

    private function getHashHelper()
    {
        if (!$this->hashHelper) {
            $this->hashHelper = new HashHelper();
        }

        return $this->hashHelper;
    }

    /**
     * Discern column types by the contents of the rows.
     *
     * @param $row
     * @param $rowIndex
     */
    private function detectColumnsFromRow($row, $rowIndex)
    {
        // For performance, prioritize the first few lines and then try again every once and a while.
        if ($rowIndex < 101) { // } || 0 === $rowIndex % 997) {
            foreach ($this->columnTypes as $i => &$type) {
                if ($value = trim((string) $row[$i] ?? '')) {
                    if ($type & self::TYPE_UNKNOWN) {
                        $type = $this->getType($value, $i);
                    }
                    if ($type ^ self::TYPE_UNKNOWN && !$this->columnHashes[$i]) {
                        $this->columnHashes[$i] = $this->getHashHelper()->detectHash($value);
                    }
                }
            }
        }
    }

    /**
     * Attempt to detect the column type given the value.
     *
     * @param $value
     * @param $columnIndex
     *
     * @return int
     */
    private function getType($value, $columnIndex)
    {
        if ($this->isEmail($value)) {
            return self::TYPE_EMAIL;
        }
        if ($this->isHash($value)) {
            return self::MODE_HASH;
        }
        if ($this->isPhone($value)) {
            return self::TYPE_PHONE;
        }

        return self::TYPE_UNKNOWN;
    }

    private function isHash($value)
    {
        return (bool) $this->getHashHelper()->detectHash($value);
    }

    /**
     * @param $value
     * @param  string  $countryCode
     *
     * @return bool
     */
    private function isPhone($value, $countryCode = 'US')
    {
        $numeric = preg_replace("/[^0-9]/", '', $value);
        $length  = strlen($numeric);
        if ($length >= 7 && $length <= 15) {
            $util     = PhoneNumberUtil::getInstance();
            $leniency = Leniency::VALID();
            $matches  = $util->findNumbers($value, $countryCode, $leniency, 10000);
            if (!$matches->current()) {
                $matches = $util->findNumbers($numeric, $countryCode, $leniency, 10000);
            }
            if ($matches) {
                return true;
            }
        }

        return false;
    }

    public function getRowIsHeader()
    {
        return $this->rowIsHeader;
    }

    /**
     * Gives us an opportunity to sanitize input in the same class that performs the column analysis later.
     *
     * @return array
     */
    public function getRowData()
    {
        return $this->rowData;
    }

    public function getColumnAnalysis()
    {
        $columns = [];
        for ($columnIndex = 0; $columnIndex < $this->columnCount; $columnIndex++) {
            $columns[$columnIndex] = [
                'name' => $this->getColumnName($columnIndex),
                'type' => $this->getColumnType($columnIndex),
                'hash' => $this->getColumnHash($columnIndex),
            ];
        }

        return $columns;
    }

    public function getColumnName($columnIndex)
    {
        return $this->columnNames[$columnIndex] ?? null;
    }

    public function getColumnType($columnIndex)
    {
        return $this->columnTypes[$columnIndex] ?? null;
    }

    public function getColumnHash($columnIndex)
    {
        return $this->columnHashes[$columnIndex] ?? null;
    }

    public function getColumnCount()
    {
        return $this->columnCount;
    }

    // private function normalized($row)
    // {
    //     // Floats can cause issues to evaluation.
    //     foreach ($row as $columnIndex => &$value) {
    //         if (is_float($value)) {
    //             $value = (string) $value;
    //         }
    //     }
    // }
}
