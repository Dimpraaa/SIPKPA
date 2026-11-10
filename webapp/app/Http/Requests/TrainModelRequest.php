<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TrainModelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin_akademik';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_versi' => ['required', 'string', 'max:255'],
            'n_estimators' => ['required', 'integer', 'between:1,2000'],
            'max_depth' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
