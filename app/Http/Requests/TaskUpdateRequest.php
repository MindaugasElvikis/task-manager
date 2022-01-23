<?php

namespace App\Http\Requests;

use App\Models\Task;
use Gate;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::allows('update', $this->route('task'))
            ? Response::allow()
            : Response::deny('Forbidden.');
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => 'string|required|max:255',
            'description' => 'string|required|max:4096',
            'type' => ['string', 'required', Rule::in(Task::TYPES)],
            'status' => ['string', 'required', Rule::in(Task::STATUSES)],
            'attached_users' => 'array',
            'attached_users.*' => 'exists:users,id'
        ];
    }
}
