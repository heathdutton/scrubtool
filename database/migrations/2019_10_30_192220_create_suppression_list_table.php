<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppressionListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppression_lists', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->text('name');
            $table->text('description');
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('global')->default(0);
            $table->unsignedTinyInteger('required')->default(0);
            $table->unsignedTinyInteger('private')->default(0);
            $table->string('token')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suppression_list');
    }
}
