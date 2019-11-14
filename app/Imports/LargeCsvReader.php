<?php

namespace App\Imports;

use Exception;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class LargeCsvReader extends Csv
{
    /**
     * Input encoding.
     *
     * @var string
     */
    private $inputEncoding = 'UTF-8';

    /**
     * Delimiter.
     *
     * @var string
     */
    private $delimiter;

    /**
     * Enclosure.
     *
     * @var string
     */
    private $enclosure = '"';

    /**
     * Sheet index to read.
     *
     * @var int
     */
    private $sheetIndex = 0;

    /**
     * Load rows contiguously.
     *
     * @var bool
     */
    private $contiguous = false;

    /**
     * Row counter for loading rows contiguously.
     *
     * @var int
     */
    private $contiguousRow = -1;

    /**
     * The character that can escape the enclosure.
     *
     * @var string
     */
    private $escapeCharacter = '\\';

    /**
     * @param $pFilename
     * @param  callable  $callback
     *
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function loadIntoCallback($pFilename, callable $callback)
    {
        $lineEnding = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', true);

        // Open file
        if (!$this->canRead($pFilename)) {
            throw new Exception($pFilename.' is an Invalid Spreadsheet file.');
        }
        $this->openFile($pFilename);
        $fileHandle = $this->fileHandle;

        // Skip BOM, if any
        $this->skipBOM();
        $this->checkSeparator();
        $this->inferSeparator();

        $currentRow = 1;

        // Loop through each line of the file in turn
        while (($rowData = fgetcsv($fileHandle, 0, $this->delimiter, $this->enclosure,
                $this->escapeCharacter)) !== false
        ) {
            foreach ($rowData as &$rowDatum) {
                if ($rowDatum != '') {
                    // Convert encoding if necessary
                    if ($this->inputEncoding !== 'UTF-8') {
                        $rowDatum = StringHelper::convertEncoding($rowDatum, 'UTF-8', $this->inputEncoding);
                    }
                }
            }
            $callback($rowData, $currentRow);
            ++$currentRow;
        }

        // Close file
        fclose($fileHandle);

        ini_set('auto_detect_line_endings', $lineEnding);

        return;
    }


    /**
     * Set input encoding.
     *
     * @param  string  $pValue  Input encoding, eg: 'UTF-8'
     *
     * @return Csv
     */
    public function setInputEncoding($pValue)
    {
        $this->inputEncoding = $pValue;

        return $this;
    }

    /**
     * Set delimiter.
     *
     * @param  string  $delimiter  Delimiter, eg: ','
     *
     * @return CSV
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Set enclosure.
     *
     * @param  string  $enclosure  Enclosure, defaults to "
     *
     * @return CSV
     */
    public function setEnclosure($enclosure)
    {
        if ($enclosure == '') {
            $enclosure = '"';
        }
        $this->enclosure = $enclosure;

        return $this;
    }

    /**
     * Set sheet index.
     *
     * @param  int  $pValue  Sheet index
     *
     * @return CSV
     */
    public function setSheetIndex($pValue)
    {
        $this->sheetIndex = $pValue;

        return $this;
    }

    /**
     * Set Contiguous.
     *
     * @param  bool  $contiguous
     *
     * @return Csv
     */
    public function setContiguous($contiguous)
    {
        $this->contiguous = (bool) $contiguous;
        if (!$contiguous) {
            $this->contiguousRow = -1;
        }

        return $this;
    }

    /**
     * Set escape backslashes.
     *
     * @param  string  $escapeCharacter
     *
     * @return $this
     */
    public function setEscapeCharacter($escapeCharacter)
    {
        $this->escapeCharacter = $escapeCharacter;

        return $this;
    }


}
