<?php

namespace App\Models;

use Database\Factories\PenilaianPkpaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenilaianPkpa extends Model
{
    public const FEATURE_LABELS = [
        'persen_kehadiran' => 'Kehadiran (%)',
        'nilai_kode_etik' => 'Kode Etik',
        'nilai_hukum_acara' => 'Hukum Acara',
        'nilai_tugas_praktik' => 'Tugas Praktik',
        'nilai_keaktifan' => 'Keaktifan',
        'nilai_simulasi_upa' => 'Simulasi UPA',
    ];

    protected static function booted(): void
    {
        static::saving(function (PenilaianPkpa $penilaian): void {
            $nilai = [];
            foreach (array_keys(self::FEATURE_LABELS) as $fitur) {
                $penilaian->{$fitur} = round((float) $penilaian->{$fitur}, 2);
                if ($fitur !== 'persen_kehadiran') {
                    $nilai[] = (float) $penilaian->{$fitur};
                }
            }
            $penilaian->nilai_akhir_rata = round(array_sum($nilai) / 5, 2);
        });
    }

    /** @return array<string, list<string>> */
    public static function nilaiRules(): array
    {
        $rules = [];
        foreach (array_keys(self::FEATURE_LABELS) as $fitur) {
            $rules[$fitur] = ['required', 'numeric', 'between:0,100'];
        }
        $rules['status_aktual'] = ['required', 'in:Lulus,Tidak Lulus,Belum Ditentukan'];

        return $rules;
    }

    /** @use HasFactory<PenilaianPkpaFactory> */
    use HasFactory;

    protected $table = 'penilaian_pkpa';

    protected $fillable = [
        'peserta_id', 'persen_kehadiran', 'nilai_kode_etik', 'nilai_hukum_acara',
        'nilai_tugas_praktik', 'nilai_keaktifan', 'nilai_simulasi_upa',
        'nilai_akhir_rata', 'status_aktual',
    ];

    protected function casts(): array
    {
        return [
            'persen_kehadiran' => 'decimal:2',
            'nilai_kode_etik' => 'decimal:2',
            'nilai_hukum_acara' => 'decimal:2',
            'nilai_tugas_praktik' => 'decimal:2',
            'nilai_keaktifan' => 'decimal:2',
            'nilai_simulasi_upa' => 'decimal:2',
            'nilai_akhir_rata' => 'decimal:2',
        ];
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(PesertaPkpa::class, 'peserta_id');
    }

    public function hasilPrediksi(): HasMany
    {
        return $this->hasMany(HasilPrediksi::class, 'penilaian_id');
    }
}
