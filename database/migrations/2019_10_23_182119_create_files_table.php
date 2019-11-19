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
            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);
            $table->timestamp('available_till', 6)->nullable();
            $table->text('name');
            $table->text('input_location')->nullable();
            $table->text('output_location')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->json('input_settings')->nullable();
            $table->ipAddress('ip_address');
            $table->string('session_id');
            $table->unsignedInteger('status');
            $table->unsignedInteger('mode');
            $table->string('type')->nullable();
            $table->json('columns')->nullable();
            $table->unsignedInteger('column_count');
            $table->unsignedBigInteger('size');
            $table->text('message')->nullable();
            $table->string('crc32b')->nullable();
            $table->string('md5')->nullable();
            $table->string('country');
            $table->json('sheets')->nullable();
            $table->unsignedBigInteger('rows_total'); // Total rows may include empty ones in XSL
            $table->unsignedBigInteger('rows_filled'); // Rows that actually got the import process. Records.

            $table->unsignedBigInteger('rows_imported');
            $table->unsignedBigInteger('rows_scrubbed');
            $table->unsignedBigInteger('rows_hashed');

            $table->unsignedBigInteger('rows_invalid');
            $table->unsignedBigInteger('rows_email_valid');
            $table->unsignedBigInteger('rows_email_invalid');
            $table->unsignedBigInteger('rows_email_duplicate');
            $table->unsignedBigInteger('rows_email_dnc');

            $table->unsignedBigInteger('rows_phone_valid');
            $table->unsignedBigInteger('rows_phone_invalid');
            $table->unsignedBigInteger('rows_phone_duplicate');
            $table->unsignedBigInteger('rows_phone_dnc');

            $table->unsignedInteger('download_count');
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
