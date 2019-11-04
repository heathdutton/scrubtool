<?php

namespace App\Helpers;

use libphonenumber\Leniency;
use libphonenumber\PhoneNumberUtil;

class FileAnalysisHelper
{
    const TYPE_AGE        = 1;

    const TYPE_DATETIME   = 2;

    const TYPE_DOB        = 4;

    const TYPE_EMAIL      = 8;

    const TYPE_FLOAT      = 16;

    const TYPE_HASH       = 32;

    const TYPE_INTEGER    = 64;

    const TYPE_L_ADDRESS1 = 128;

    const TYPE_L_ADDRESS2 = 256;

    const TYPE_L_CITY     = 512;

    const TYPE_L_COUNTRY  = 1024;

    const TYPE_L_ZIP      = 2048;

    const TYPE_NAME_FIRST = 4096;

    const TYPE_NAME_LAST  = 8192;

    const TYPE_PHONE      = 16384;

    const TYPE_STRING     = 32768;

    const TYPE_UNKNOWN    = 65536;

    protected $columnCount = 0;

    protected $columnNames = [];

    protected $columnTypes = [];

    protected $columnHashes = [];

    protected $hashHelper;

    protected $rowIsHeader = false;

    protected $rowData = [];

    protected $rowIndex = 0;

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
        'age'         => self::TYPE_AGE,
        'dob'         => self::TYPE_DOB,
        'dateofbirth' => self::TYPE_DOB,
        'birth'       => self::TYPE_DOB,
        'birthdate'   => self::TYPE_DOB,
        'fnam'        => self::TYPE_NAME_FIRST,
        'first'       => self::TYPE_NAME_FIRST,
        'firstname'   => self::TYPE_NAME_FIRST,
        'fname'       => self::TYPE_NAME_FIRST,
        'lnam'        => self::TYPE_NAME_LAST,
        'last'        => self::TYPE_NAME_LAST,
        'lastname'    => self::TYPE_NAME_LAST,
        'lname'       => self::TYPE_NAME_LAST,
        'address'     => self::TYPE_L_ADDRESS1,
        'address1'    => self::TYPE_L_ADDRESS1,
        'address2'    => self::TYPE_L_ADDRESS2,
        'city'        => self::TYPE_L_CITY,
        'zip'         => self::TYPE_L_ZIP,
        'postal'      => self::TYPE_L_ZIP,
        'zipcode'     => self::TYPE_L_ZIP,
        'country'     => self::TYPE_L_COUNTRY,
        'email'       => self::TYPE_EMAIL,
        'mail'        => self::TYPE_EMAIL,
        'phone'       => self::TYPE_PHONE,
        'home'        => self::TYPE_PHONE,
        'homephone'   => self::TYPE_PHONE,
        'ph'          => self::TYPE_PHONE,
        'cell'        => self::TYPE_PHONE,
        'mobile'      => self::TYPE_PHONE,
        'hash'        => self::TYPE_HASH,
    ];

    /**
     * @param $row
     * @param $rowIndex
     *
     * @return $this
     */
    public function parseRow(&$row, $rowIndex)
    {
        $this->rowData  = $row;
        $this->rowIndex = $rowIndex;

        $this->padColumns();

        if ($this->rowIsHeader()) {
            $this->detectColumnsFromHeader();
        } else {
            $this->detectColumnsFromRow();
        }

        $row = $this->getRowData();

        return $this;
    }

    /**
     * Get the full column count as the file is processed, and pad out our arrays for other checks.
     */
    private function padColumns()
    {
        $count = count($this->rowData);
        if ($this->columnCount < $count) {
            $this->columnNames  = array_pad($this->columnNames, $count, null);
            $this->columnHashes = array_pad($this->columnHashes, $count, null);
            $this->columnTypes  = array_pad($this->columnTypes, $count, self::TYPE_UNKNOWN);
            $this->columnCount  = $count;
        }
    }

    /**
     * @return bool
     */
    private function rowIsHeader()
    {
        $this->rowIsHeader = false;

        if (
            !count(array_filter($this->columnNames))
            && $this->rowIndex < 5
            && count(array_filter($this->rowData))
        ) {
            foreach ($this->rowData as $value) {
                if ($this->isEmail($value)) {
                    $this->rowIsHeader = false;
                    break;
                } elseif ($this->isPhone($value)) {
                    $this->rowIsHeader = false;
                    break;
                } elseif (in_array($this->simplify($value), $this->headerWhitelist)) {
                    $this->rowIsHeader = true;
                    break;
                } elseif (
                    1 === $this->rowIndex
                    && is_string($value)
                    && !is_numeric($value)
                    && false !== strpos($value, ' ')
                ) {
                    // Extra lenient with first row.
                    $this->rowIsHeader = true;
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
     * @param $value
     * @param  string  $countryCode
     * @param  bool  $lenient
     *
     * @return bool
     */
    private function isPhone($value, $countryCode = 'US', $lenient = false)
    {
        if (!$lenient && ctype_alpha($value)) {
            return false;
        }
        if (!$lenient && strpos($value, '/')) {
            return false;
        }
        if (!$lenient && strpos($value, '\\')) {
            return false;
        }
        $numeric = preg_replace("/[^0-9]/", '', $value);
        $length  = strlen($numeric);
        if ($length >= ($lenient ? 7 : 10) && $length <= 15) {
            $util     = PhoneNumberUtil::getInstance();
            $leniency = $lenient ? Leniency::POSSIBLE() : Leniency::VALID();
            $matches  = $util->findNumbers($value, $countryCode, $leniency, 10000);
            if ($lenient && !$matches->current()) {
                $matches = $util->findNumbers($numeric, $countryCode, $leniency, 10000);
            }

            return (bool) $matches->current();
        }

        return false;
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
     */
    private function detectColumnsFromHeader()
    {
        foreach ($this->rowData as $i => $value) {
            // Try not to manipulate the column headers more than necessary so that output matches input.
            $this->columnNames[$i] = trim((string) $value);

            // Assume type based on header contents (can be overridden later).
            $this->columnTypes[$i] = self::TYPE_UNKNOWN;
            $simple                = $this->simplify($value);
            if (isset($this->typeIdentifiers[$simple])) {
                $this->columnTypes[$i] = $this->typeIdentifiers[$simple];
            } elseif (
                isset($this->typeIdentifiers['hash'.$simple])
                || isset($this->typeIdentifiers[$simple.'hash'])
            ) {
                $this->columnTypes[$i] = $this->typeIdentifiers[$simple] & self::TYPE_HASH;
            } elseif (in_array($simple, $this->getHashHelper()->listSimple())) {
                $this->columnTypes[$i] = self::TYPE_HASH;
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
     */
    private function detectColumnsFromRow()
    {
        // For performance, prioritize the first few lines and then try again every once and a while.
        if ($this->rowIndex < 101) { // } || 0 === $rowIndex % 997) {
            foreach ($this->columnTypes as $i => &$type) {
                if ($value = trim((string) $this->rowData[$i] ?? '')) {
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
            return self::TYPE_HASH;
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
     * Gives us an opportunity to sanitize input in the same class that performs the column analysis later.
     *
     * @param $row
     *
     * @return array
     */
    private function getRowData()
    {
        return $this->rowData;
    }

    /**
     * @return bool
     */
    public function rowIsValid()
    {
        foreach ($this->rowData as $value) {
            if (!empty($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getRowIsHeader()
    {
        return $this->rowIsHeader;
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

    /**
     * @param $columnIndex
     *
     * @return mixed|null
     */
    public function getColumnName($columnIndex)
    {
        return $this->columnNames[$columnIndex] ?? null;
    }

    /**
     * @param $columnIndex
     *
     * @return mixed|null
     */
    public function getColumnType($columnIndex)
    {
        return $this->columnTypes[$columnIndex] ?? null;
    }

    /**
     * @param $columnIndex
     *
     * @return mixed|null
     */
    public function getColumnHash($columnIndex)
    {
        return $this->columnHashes[$columnIndex] ?? null;
    }

    /**
     * @return int
     */
    public function getColumnCount()
    {
        return $this->columnCount;
    }
}
