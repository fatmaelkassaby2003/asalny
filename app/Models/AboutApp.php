<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutApp extends Model
{
    protected $table = 'about_app';

    protected $fillable = [
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'app_version',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
