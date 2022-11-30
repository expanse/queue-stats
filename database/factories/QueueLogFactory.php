<?php

namespace Expanse\QueueStats\Database\Factories;

use Expanse\QueueStats\Models\QueueLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Expanse\QueueStats\Models\QueueLog>
 */
class QueueLogFactory extends Factory
{
    protected $model = QueueLog::class;

    /**
     * @return array<string,mixed>
     */
    public function definition() : array
    {
        return [
            'task' => null,
            'connection' => 'database',
            'queue' => null,
            'class' => null,
            'job_id' => null,
        ];
    }
}
