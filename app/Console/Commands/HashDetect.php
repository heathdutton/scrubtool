<?php

namespace App\Console\Commands;

use App\Helpers\HashHelper;
use Illuminate\Console\Command;

class HashDetect extends Command
{

    protected $signature = 'hash:detect {hash}';

    protected $description = 'Detect a hash from a string.';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle()
    {
        $helper = new HashHelper();

        var_export($helper->detectHash($this->argument('hash')));
    }
}
