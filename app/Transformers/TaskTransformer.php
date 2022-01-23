<?php

namespace App\Transformers;

use App\Models\Task;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\TransformerAbstract;

class TaskTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'owner',
        'attached_users',
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        //
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Task $task)
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'type' => $task->type,
            'status' => $task->status,
        ];
    }

    public function includeOwner(Task $task): ResourceInterface
    {
        return $this->item($task->owner, new UserTransformer());
    }

    public function includeAttachedUsers(Task $task): ResourceInterface
    {
        return $this->collection($task->attached_users, new UserTransformer());
    }
}
