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
            $table->text('location')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('list_id')->nullable();
            $table->ipAddress('ip_address');
            $table->string('session_id');
            $table->unsignedTinyInteger('type')->nullable();
            $table->unsignedTinyInteger('format')->nullable();
            $table->json('columns')->nullable();
            $table->unsignedInteger('column_count');
            $table->unsignedBigInteger('size');
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
            $table->index(['user_id', 'updated_at']);
            $table->index(['list_id', 'updated_at']);
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
