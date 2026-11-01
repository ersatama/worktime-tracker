<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartTimerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['description' => 'описание задачи'];
    }
}
