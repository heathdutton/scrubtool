<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppressionListSupportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppression_list_supports', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->bigInteger('suppression_list_id')->nullable();
            $table->unsignedTinyInteger('status');
            $table->unsignedInteger('column_type');
            $table->string('hash_type')->nullable();
            $table->unique(['suppression_list_id', 'column_type', 'hash_type'], 'unique_supports');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suppression_list_supports');
    }
}
