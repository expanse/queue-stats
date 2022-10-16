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
            $table->string('task');
            $table->string('connection');
            $table->string('class');
            $table->timestamp('created_at', 5)->nullable();
        });
    }

    public function down()
    {
        Schema::drop('queue_stats');
    }
};
