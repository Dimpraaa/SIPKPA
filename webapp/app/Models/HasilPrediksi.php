<?php

namespace App\Models;

use Database\Factories\HasilPrediksiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilPrediksi extends Model
{
    /** @use HasFactory<HasilPrediksiFactory> */
    use HasFactory;

    protected $table = 'hasil_prediksi';

    protected $fillable = [
        'peserta_id', 'penilaian_id', 'model_id', 'diprediksi_oleh', 'status_prediksi',
        'probabilitas_lulus', 'probabilitas_gagal', 'rekomendasi_tindak_lanjut', 'tanggal_prediksi',
    ];

    protected function casts(): array
    {
        return [
            'probabilitas_lulus' => 'decimal:2',
            'probabilitas_gagal' => 'decimal:2',
            'tanggal_prediksi' => 'datetime',
        ];
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(PesertaPkpa::class, 'peserta_id');
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(PenilaianPkpa::class, 'penilaian_id');
    }

    public function modelMl(): BelongsTo
    {
        return $this->belongsTo(ModelMl::class, 'model_id');
    }

    public function prediktor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diprediksi_oleh');
    }
}
