<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function isPengelola(): bool
    {
        return $this->role === 'pengelola';
    }

    public function roleLabel(): string
    {
        return $this->isPengelola() ? 'Admin / Pengelola' : 'Mitra / Pemilik';
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
