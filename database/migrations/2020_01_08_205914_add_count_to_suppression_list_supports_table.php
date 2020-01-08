<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountToSuppressionListSupportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('suppression_list_supports', function (Blueprint $table) {
            if (!Schema::hasColumn('suppression_list_supports', 'count')) {
                $table->unsignedBigInteger('count')->default(0)->after('hash_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('suppression_list_supports', function (Blueprint $table) {
            if (Schema::hasColumn('suppression_list_supports', 'count')) {
                $table->removeColumn('count');
            }
        });
    }
}
