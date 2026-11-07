<?php

namespace App\Models;

use Database\Factories\AngkatanPkpaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AngkatanPkpa extends Model
{
    /** @use HasFactory<AngkatanPkpaFactory> */
    use HasFactory;

    protected $table = 'angkatan_pkpa';

    protected $fillable = [
        'kode_angkatan', 'nama_angkatan', 'tanggal_mulai', 'tanggal_selesai', 'status_angkatan',
    ];

    protected function casts(): array
    {
        return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];
    }

    public function peserta(): HasMany
    {
        return $this->hasMany(PesertaPkpa::class, 'angkatan_id');
    }
}
