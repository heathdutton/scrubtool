<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppressionListSupportScrubsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppression_list_support_scrubs', function (Blueprint $table) {
            // Standard ActionAbstract fields:
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');
            $table->timestamp('created_at', 0)->useCurrent()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->string('token', 64)->index();
            $table->string('referrer', 2083);
            $table->string('user_agent', 1023);

            // Additions.
            $table->unsignedBigInteger('file_id')->nullable()->index();
            $table->unsignedBigInteger('suppression_list_support_id')->index('suppression_list_support_id');
            $table->unsignedBigInteger('rows_filled')->default(0);
            $table->unsignedBigInteger('rows_scrubbed')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suppression_list_support_scrubs');
    }
}
