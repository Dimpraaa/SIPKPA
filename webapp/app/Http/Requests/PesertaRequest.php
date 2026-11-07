<?php

namespace App\Http\Requests;

use App\Models\PenilaianPkpa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PesertaRequest extends FormRequest
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
            'nomor_peserta' => ['required', 'string', 'max:255', Rule::unique('peserta_pkpa')->ignore($this->route('peserta'))],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'email' => ['required', 'email', 'max:255'],
            'no_hp' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s()-]{8,20}$/'],
            'asal_kampus_s1' => ['required', 'string', 'max:255'],
            'ipk_s1' => ['required', 'numeric', 'between:0,4'],
            ...PenilaianPkpa::nilaiRules(),
        ];
    }
}
