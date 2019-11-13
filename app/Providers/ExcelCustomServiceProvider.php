<?php

namespace App\Providers;

use App\Imports\CustomReader;
use App\Imports\CustomTemporaryFileFactory;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\ExcelServiceProvider;
use Maatwebsite\Excel\Files\Filesystem;
use Maatwebsite\Excel\QueuedWriter;
use Maatwebsite\Excel\Writer;

class ExcelCustomServiceProvider extends ExcelServiceProvider
{

    public function register()
    {
        parent::register();

        $this->app->bind('excelCustom', function () {
            $customReader = $this->app->make(CustomReader::class);

            return [
                new Excel(
                    $this->app->make(Writer::class),
                    $this->app->make(QueuedWriter::class),
                    $customReader,
                    $this->app->make(Filesystem::class)
                ),
                $customReader,
            ];
        });
    }

}
