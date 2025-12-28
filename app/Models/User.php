<?php

// app/Models/User.php - تحديث

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'gender',
        'password',
        'is_asker',
        'is_active',
        'description',
        'wallet_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_asker' => 'boolean',
        'is_active' => 'boolean',
        'viewed_at' => 'datetime',
        'wallet_balance' => 'decimal:2',
    ];

    public function canAccessFilament(): bool
    {
        return true;
    }

    // في UserQuestion.php
    public function offers()
    {
        return $this->hasMany(QuestionOffer::class, 'question_id');
    }

    public function canAccessPanel($panel): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->attributes['name'] ?? $this->attributes['email'] ?? 'User';
    }

    public function getNameAttribute($value): string
    {
        return $value ?? $this->attributes['email'] ?? 'User';
    }

    /**
     * علاقة المواقع
     */
    public function locations()
    {
        return $this->hasMany(UserLocation::class);
    }

    /**
     * الموقع الحالي (الافتراضي)
     */
    public function currentLocation()
    {
        return $this->hasOne(UserLocation::class)->where('is_default', true);
    }

    /**
     * علاقة الأسئلة
     */
    public function questions()
    {
        return $this->hasMany(UserQuestion::class);
    }

    /**
     * الأسئلة النشطة فقط
     */
    public function activeQuestions()
    {
        return $this->hasMany(UserQuestion::class)->where('is_active', true);
    }
}
