<?php

namespace App\Console\Commands;

use App\Helpers\HashHelper;
use Illuminate\Console\Command;

class HashList extends Command
{

    protected $signature = 'hash:list {--regenerate}';

    protected $description = 'List hashes supported.';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle()
    {
        $helper = new HashHelper();

        var_export($helper->list($this->option('regenerate')));
    }
}
