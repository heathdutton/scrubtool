<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRunStartedToFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            if (!Schema::hasColumn('files', 'run_started')) {
                $table->timestamp('run_started', 6)->nullable()->after('updated_at');
                $table->timestamp('run_completed', 6)->nullable()->after('run_started');
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
        Schema::table('files', function (Blueprint $table) {
            if (Schema::hasColumn('files', 'run_started')) {
                $table->removeColumn('run_started');
            }
        });
    }
}
