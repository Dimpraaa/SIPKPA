<?php

namespace App\Models;

use Database\Factories\ModelMlFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelMl extends Model
{
    /** @use HasFactory<ModelMlFactory> */
    use HasFactory;

    protected $table = 'model_ml';

    protected $fillable = [
        'dibuat_oleh', 'nama_versi', 'jumlah_data_latih', 'jumlah_data_uji',
        'n_estimators', 'max_depth', 'criterion', 'akurasi', 'presisi', 'recall',
        'f1_score', 'confusion_matrix_json', 'feature_importance_json', 'file_path_model', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_data_latih' => 'integer',
            'jumlah_data_uji' => 'integer',
            'n_estimators' => 'integer',
            'max_depth' => 'integer',
            'akurasi' => 'decimal:2',
            'presisi' => 'decimal:2',
            'recall' => 'decimal:2',
            'f1_score' => 'decimal:2',
            'confusion_matrix_json' => 'array',
            'feature_importance_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function hasilPrediksi(): HasMany
    {
        return $this->hasMany(HasilPrediksi::class, 'model_id');
    }
}
