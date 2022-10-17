<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueStatsTable extends Migration
{
    public function up()
    {
        Schema::create('queue_stats', function (Blueprint $table) {
            $table->id();
            $table->string('class');
            $table->integer('queue_count');
            $table->integer('fail_count');
            $table->float('processing_wait');
            $table->float('processing_time');
            $table->date('report_date');
        });
    }

    public function down()
    {
        Schema::drop('queue_stats');
    }
};
