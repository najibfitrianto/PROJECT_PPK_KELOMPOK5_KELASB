<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'deadline' => ['nullable', 'date'],
            'assignees' => ['nullable', 'array'], // daftar id user yang ditambahkan ke tugas
            'assignees.*' => ['exists:users,id'],
        ];
    }
}
