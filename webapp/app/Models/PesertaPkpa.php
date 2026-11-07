<?php

namespace App\Models;

use Database\Factories\PesertaPkpaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PesertaPkpa extends Model
{
    /** @use HasFactory<PesertaPkpaFactory> */
    use HasFactory;

    protected $table = 'peserta_pkpa';

    protected $fillable = [
        'angkatan_id', 'nomor_peserta', 'nama_lengkap', 'jenis_kelamin',
        'email', 'no_hp', 'asal_kampus_s1', 'ipk_s1',
    ];

    protected function casts(): array
    {
        return ['ipk_s1' => 'decimal:2'];
    }

    public function angkatan(): BelongsTo
    {
        return $this->belongsTo(AngkatanPkpa::class, 'angkatan_id');
    }

    public function penilaian(): HasOne
    {
        return $this->hasOne(PenilaianPkpa::class, 'peserta_id');
    }

    public function hasilPrediksi(): HasMany
    {
        return $this->hasMany(HasilPrediksi::class, 'peserta_id');
    }

    public function prediksiTerbaru(): HasOne
    {
        return $this->hasOne(HasilPrediksi::class, 'peserta_id')
            ->ofMany(['tanggal_prediksi' => 'max', 'id' => 'max']);
    }
}
