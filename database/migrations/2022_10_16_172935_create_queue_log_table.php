<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueueLogTable extends Migration
{
    public function up()
    {
        Schema::create('queue_log', function (Blueprint $table) {
            $table->id();
            $table->string('task');
            $table->string('connection');
            $table->string('queue')->nullable();
            $table->string('class');
            $table->string('job_id');
            $table->timestamp('created_at', 5)->nullable();
        });
    }

    public function down()
    {
        Schema::drop('queue_log');
    }
};
