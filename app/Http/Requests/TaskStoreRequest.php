<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
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
