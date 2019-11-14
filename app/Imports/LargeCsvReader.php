<?php

namespace App\Imports;

use Exception;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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

    /** @var int  */
    private $largeCsvLimit;

    /**
     * LargeCsvReader constructor.
     *
     * @param  null  $largeCsvLimit
     */
    public function __construct($largeCsvLimit = null)
    {
        $this->largeCsvLimit = $largeCsvLimit;

        parent::__construct();
    }


    /**
     * Loads PhpSpreadsheet from file into PhpSpreadsheet instance.
     *
     * @param  string  $pFilename
     * @param  Spreadsheet  $spreadsheet
     *
     * @return Spreadsheet
     * @throws Exception
     *
     */
    public function loadIntoExisting($pFilename, Spreadsheet $spreadsheet)
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

        // Create new PhpSpreadsheet object
        while ($spreadsheet->getSheetCount() <= $this->sheetIndex) {
            $spreadsheet->createSheet();
        }
        $sheet = $spreadsheet->setActiveSheetIndex($this->sheetIndex);

        // Set our starting row based on whether we're in contiguous mode or not
        $currentRow = 1;
        if ($this->contiguous) {
            $currentRow = ($this->contiguousRow == -1) ? $sheet->getHighestRow() : $this->contiguousRow;
        }

        // Loop through each line of the file in turn
        while (($rowData = fgetcsv($fileHandle, 0, $this->delimiter, $this->enclosure,
                $this->escapeCharacter)) !== false
            && (!$this->largeCsvLimit || $currentRow <= $this->largeCsvLimit)
        ) {
            $columnLetter = 'A';
            foreach ($rowData as $rowDatum) {
                if ($rowDatum != '' && $this->readFilter->readCell($columnLetter, $currentRow)) {
                    // Convert encoding if necessary
                    if ($this->inputEncoding !== 'UTF-8') {
                        $rowDatum = StringHelper::convertEncoding($rowDatum, 'UTF-8', $this->inputEncoding);
                    }

                    // Set cell value
                    $sheet->getCell($columnLetter.$currentRow)->setValue($rowDatum);
                }
                ++$columnLetter;
            }
            ++$currentRow;
        }

        // Close file
        fclose($fileHandle);

        if ($this->contiguous) {
            $this->contiguousRow = $currentRow;
        }

        ini_set('auto_detect_line_endings', $lineEnding);

        // Return
        return $spreadsheet;
    }
}
