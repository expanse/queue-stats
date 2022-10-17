<?php

namespace Expanse\QueueStats\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueLog extends Model
{
    use HasFactory;

    public $table = 'queue_log';

    public const UPDATED_AT = null;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $fillable = [
        'task',
        'connection',
        'class',
        'job_id',
    ];
}
