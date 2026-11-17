<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLE_PENGELOLA = 'pengelola';
    public const ROLE_MITRA_PEMILIK = 'mitra_pemilik';

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function isPengelola(): bool
    {
        return $this->role === self::ROLE_PENGELOLA;
    }

    public function isMitraPemilik(): bool
    {
        return $this->role === self::ROLE_MITRA_PEMILIK;
    }

    public function roleLabel(): string
    {
        return $this->isPengelola() ? 'Pengelola' : 'Mitra / Pemilik';
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    public function feedTransactions(): HasMany
    {
        return $this->hasMany(FeedTransaction::class);
    }

    public function waterQualities(): HasMany
    {
        return $this->hasMany(WaterQuality::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
