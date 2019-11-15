<?php

namespace App\Imports;

use Maatwebsite\Excel\Reader;

class CustomReader extends Reader
{

    /** @var array */
    private $totalRows = [];

    /**
     * Remembers the rowcount so that we can get it externally without recalculation.
     *
     * @return array
     */
    public function getTotalRows(): array
    {
        if (!$this->totalRows && file_exists($this->currentFile->getLocalPath())) {
            $this->totalRows = parent::getTotalRows();
        }

        return $this->totalRows;
    }

    /**
     * Set totalRows on a second run through the file to save time. Does not exist in the parent.
     *
     * @param $totalRows
     *
     * @return $this
     */
    public function setTotalRows($totalRows)
    {
        $this->totalRows = $totalRows;

        return $this;
    }
}
