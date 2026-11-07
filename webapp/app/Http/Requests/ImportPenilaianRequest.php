<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportPenilaianRequest extends FormRequest
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
            'angkatan_id' => ['required', 'integer', 'exists:angkatan_pkpa,id'],
            'file' => ['required', 'file', 'extensions:csv,xlsx,xls', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ];
    }
}
