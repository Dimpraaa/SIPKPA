<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['nama_lengkap', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    public function modelMl(): HasMany
    {
        return $this->hasMany(ModelMl::class, 'dibuat_oleh');
    }

    public function hasilPrediksi(): HasMany
    {
        return $this->hasMany(HasilPrediksi::class, 'diprediksi_oleh');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
