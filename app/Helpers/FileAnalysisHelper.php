<?php

namespace App\Helpers;

use App\File;
use App\SuppressionListSupport;
use libphonenumber\Leniency;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class FileAnalysisHelper
{
    /** @var array Simplified strings that indicate the row may be a header. */
    const HEADER_KEYWORDS = [
        'mail',
        'phone',
        'mobile',
        'cell',
        'name',
        'ip',
        'id',
        'date',
        'url',
        'time',
        'customer',
        'dob',
        'age',
        'dnc',
        'dne',
    ];

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

    private $columnCount = 0;

    private $columnNames = [];

    private $columnTypes = [];

    private $columnHashes = [];

    private $hashHelper;

    private $rowIsHeader = false;

    private $rowData = [];

    private $rowIndex = 0;

    /** @var array Simplified strings that identify the column type with fair certainty. */
    private $typeIdentifiers = [
        'age'         => SuppressionListSupport::TYPE_AGE,
        'dob'         => SuppressionListSupport::TYPE_DOB,
        'dateofbirth' => SuppressionListSupport::TYPE_DOB,
        'birth'       => SuppressionListSupport::TYPE_DOB,
        'birthdate'   => SuppressionListSupport::TYPE_DOB,
        'fnam'        => SuppressionListSupport::TYPE_NAME_FIRST,
        'first'       => SuppressionListSupport::TYPE_NAME_FIRST,
        'firstname'   => SuppressionListSupport::TYPE_NAME_FIRST,
        'fname'       => SuppressionListSupport::TYPE_NAME_FIRST,
        'lnam'        => SuppressionListSupport::TYPE_NAME_LAST,
        'last'        => SuppressionListSupport::TYPE_NAME_LAST,
        'lastname'    => SuppressionListSupport::TYPE_NAME_LAST,
        'lname'       => SuppressionListSupport::TYPE_NAME_LAST,
        'address'     => SuppressionListSupport::TYPE_L_ADDRESS1,
        'address1'    => SuppressionListSupport::TYPE_L_ADDRESS1,
        'address2'    => SuppressionListSupport::TYPE_L_ADDRESS2,
        'city'        => SuppressionListSupport::TYPE_L_CITY,
        'zip'         => SuppressionListSupport::TYPE_L_ZIP,
        'postal'      => SuppressionListSupport::TYPE_L_ZIP,
        'zipcode'     => SuppressionListSupport::TYPE_L_ZIP,
        'country'     => SuppressionListSupport::TYPE_L_COUNTRY,
        'email'       => SuppressionListSupport::TYPE_EMAIL,
        'mail'        => SuppressionListSupport::TYPE_EMAIL,
        'phone'       => SuppressionListSupport::TYPE_PHONE,
        'home'        => SuppressionListSupport::TYPE_PHONE,
        'homephone'   => SuppressionListSupport::TYPE_PHONE,
        'ph'          => SuppressionListSupport::TYPE_PHONE,
        'telephone'   => SuppressionListSupport::TYPE_PHONE,
        'number'      => SuppressionListSupport::TYPE_PHONE,
        'cell'        => SuppressionListSupport::TYPE_PHONE,
        'mobile'      => SuppressionListSupport::TYPE_PHONE,
        'hash'        => SuppressionListSupport::TYPE_HASH,
    ];

    /** @var File */
    private $file;

    /**
     * FileAnalysisHelper constructor.
     *
     * @param $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

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
            $this->columnTypes  = array_pad($this->columnTypes, $count, null);
            $this->columnCount  = $count;
        }
    }

    /**
     * @return bool
     */
    private function rowIsHeader()
    {
        $this->rowIsHeader = false;

        // We may have already evaluated the file's header row.
        if (1 === $this->rowIndex && !empty($this->file->columns)) {
            foreach ($this->file->columns as $columnIndex => $column) {
                if (!empty($column['name']) && $this->rowData[$columnIndex] === $column['name']) {
                    $this->rowIsHeader = true;

                    return $this->rowIsHeader;
                }
            }
        }

        if (
            $this->rowIndex < 5
            && !count(array_filter($this->columnNames))
            && count(array_filter($this->rowData))
        ) {
            foreach ($this->rowData as $value) {
                if (self::isEmail($value)) {
                    $this->rowIsHeader = false;
                    break;
                } elseif ($this->isPhone($value)) {
                    $this->rowIsHeader = false;
                    break;
                } elseif (self::isHeaderByKeywords($value)) {
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
    public static function isEmail($value)
    {
        return (bool) self::getEmail($value);
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public static function getEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @param $value
     * @param  string  $countryCode
     * @param  bool  $lenient
     *
     * @return bool
     */
    public static function isPhone($value, $countryCode = 'US', $lenient = false)
    {
        return (bool) self::getPhone($value, $countryCode, $lenient);
    }

    /**
     * Gets the phone number out of a field in a universal E.164 format.
     *
     * @param $value
     * @param  string  $countryCode
     * @param  bool  $lenient
     *
     * @return string|null
     */
    public static function getPhone($value, $countryCode = 'US', $lenient = false)
    {
        $value = (string) $value;
        if (!$lenient && ctype_alpha($value)) {
            return null;
        }
        if (!$lenient && strpos($value, '/')) {
            return null;
        }
        if (!$lenient && strpos($value, '\\')) {
            return null;
        }
        $numeric = preg_replace("/[^0-9]/", '', $value);
        $length  = strlen($numeric);
        if ($length >= ($lenient ? 7 : 10) && $length <= 15) {
            $util     = PhoneNumberUtil::getInstance();
            $leniency = $lenient ? Leniency::POSSIBLE() : Leniency::VALID();
            $matches  = $util->findNumbers($value, $countryCode, $leniency, 10000);
            $matches->rewind();
            $match = $matches->current();
            if ($lenient && !$match) {
                $matches = $util->findNumbers($numeric, $countryCode, $leniency, 10000);
                $matches->rewind();
                $match = $matches->current();
            }

            return $match ? $util->format($match->number(), PhoneNumberFormat::E164) : null;
        }

        return null;
    }

    /**
     * @param $string
     *
     * @return bool
     */
    private static function isHeaderByKeywords($string)
    {
        $simple = self::simplify($string);
        if (in_array($simple, self::HEADER_KEYWORDS)) {
            return true;
        }
        foreach (self::HEADER_KEYWORDS as $item) {
            if (false !== strpos($simple, $item)) {
                return true;
            }
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
    private static function simplify($value)
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
            $this->columnTypes[$i] = null;
            $simple                = $this->simplify($value);
            if ($simple) {
                if (isset($this->typeIdentifiers[$simple])) {
                    $this->columnTypes[$i] = $this->typeIdentifiers[$simple];
                }
                foreach ($this->getHashHelper()->listSimple() as $hashSimple) {
                    if (strpos($simple, $hashSimple)) {
                        $this->columnTypes[$i] += SuppressionListSupport::TYPE_HASH;
                    }
                }
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
                    if (!$type) {
                        $type = $this->getType($value, $i);
                    }
                    if ($type && !$this->columnHashes[$i]) {
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
        if (self::isEmail($value)) {
            return SuppressionListSupport::TYPE_EMAIL;
        }
        if ($this->isHash($value)) {
            return SuppressionListSupport::TYPE_HASH;
        }
        if (self::isPhone($value, $this->file->country ?? 'US')) {
            return SuppressionListSupport::TYPE_PHONE;
        }

        return null;
    }

    private function isHash($value)
    {
        return (bool) $this->getHashHelper()->detectHash($value);
    }

    /**
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
