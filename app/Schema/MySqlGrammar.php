<?php namespace App\Schema;

use Illuminate\Support\Fluent;

class MySqlGrammar extends \Illuminate\Database\Schema\Grammars\MySqlGrammar
{

    protected function typeCustomBinary(Fluent $column)
    {
        return 'BINARY('.$column->length.')';
    }

}
