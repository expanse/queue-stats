<?php

namespace Expanse\QueueStats\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueStats extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $fillable = [
        'class',
        'queue_count',
        'fail_count',
        'processing_wait',
        'processing_time',
        'report_date',
    ];
}

