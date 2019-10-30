<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();
            $table->text('name');
            $table->text('input_location')->nullable();
            $table->text('output_location')->nullable();
            $table->integer('user_id')->unsigned();
            $table->json('input_settings')->nullable();
            $table->ipAddress('ip_address');
            $table->string('session_id');
            $table->unsignedTinyInteger('status');
            $table->unsignedTinyInteger('mode');
            $table->string('type')->nullable();
            $table->json('columns')->nullable();
            $table->unsignedInteger('column_count');
            $table->unsignedBigInteger('size');
            $table->string('message')->nullable();
            $table->unsignedBigInteger('rows_total');
            $table->unsignedBigInteger('rows_processed');
            $table->unsignedBigInteger('rows_scrubbed');
            $table->unsignedBigInteger('rows_invalid');
            $table->unsignedBigInteger('rows_email_valid');
            $table->unsignedBigInteger('rows_email_invalid');
            $table->unsignedBigInteger('rows_email_duplicate');
            $table->unsignedBigInteger('rows_email_dnc');
            $table->unsignedBigInteger('rows_phone_valid');
            $table->unsignedBigInteger('rows_phone_invalid');
            $table->unsignedBigInteger('rows_phone_duplicate');
            $table->unsignedBigInteger('rows_phone_dnc');
            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index('size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
