<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;

class GenericImport implements ToModel, WithChunkReading, WithValidation
{
    use SkipsFailures, SkipsErrors;

    private $row = 0;

    private $columns = [];

    private $type;

    /** @var array Values that indicate the row may be a header. Non alpha characters removed. */
    private $headerIdentifiers = [
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
    ];

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function model(array $row)
    {
        $this->row++;

        if ($this->row == 1) {
            $tmp = $row;
        }


        // return new FileRow([
        //     'name' => $row[0],
        // ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function rules(): array
    {
        return [
            // '1' => Rule::in(['patrick@maatwebsite.nl']),
            //
            // // Above is alias for as it always validates in batches
            // '*.1' => Rule::in(['patrick@maatwebsite.nl']),
            //
            // // Can also use callback validation rules
            // '0' => function($attribute, $value, $onFailure) {
            //     if ($value !== 'Patrick Brouwers') {
            //         $onFailure('Name is not Patrick Brouwers');
            //     }
            // }
        ];
    }

    private function rowIsHeader($row)
    {
        foreach ($row as $value) {
            $simple = strtolower(preg_replace('/[^a-z]/i', '', $value));
            if (in_array($simple, $this->headerIdentifiers)) {
                return true;
            }
        }

        return false;
    }
}
