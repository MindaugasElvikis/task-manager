<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Task;
use App\Transformers\TaskTransformer;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        return fractal(
            Task::query()
                ->with(['owner', 'attached_users'])
                ->whereBelongsTo(auth()->user(), 'owner')
                ->orWhereHas('attached_users', function ($query) {
                    $query->where('user_id', auth()->user()->id);
                })
                ->paginate(10),
            new TaskTransformer(),
        )->respond();
    }

    public function store(TaskStoreRequest $request): JsonResponse
    {
        $task = (new Task())->fill($request->validated());
        $task->owner()->associate(auth()->user());
        $task->save();

        if ($request->has('attached_users')) {
            $task->attached_users()->sync($request->get('attached_users'));
            $task->save();
        }

        return fractal($task, new TaskTransformer())->respond();
    }

    public function show(Task $task): JsonResponse
    {
        if(auth()->user()->cant('show', $task)) {
            abort(403, 'Forbidden.');
        }

        return fractal($task, new TaskTransformer())->respond();
    }

    public function update(TaskUpdateRequest $request, Task $task): JsonResponse
    {
        $task->fill($request->validated());
        if ($request->has('attached_users')) {
            $task->attached_users()->sync($request->get('attached_users'));
        }
        $task->save();

        return fractal($task, new TaskTransformer())->respond();
    }

    public function destroy(Task $task): JsonResponse
    {
        if(auth()->user()->cant('destroy', $task)) {
            abort(403, 'Forbidden.');
        }

        $task->delete();

        return response()->json(['message' => 'Ok.'], 200);
    }

    public function close(Task $task): JsonResponse
    {
        if(auth()->user()->cant('close', $task)) {
            abort(403, 'Forbidden.');
        }

        $task->status = Task::STATUS_CLOSED;
        $task->save();

        return fractal($task, new TaskTransformer())->respond();
    }
}
