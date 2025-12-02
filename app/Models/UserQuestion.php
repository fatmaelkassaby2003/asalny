<?php

// app/Models/UserQuestion.php - محدث

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'question',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * العلاقة مع المستخدم (السائق)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع الموقع
     */
    public function location()
    {
        return $this->belongsTo(UserLocation::class, 'location_id');
    }

    /**
     * العلاقة مع المشاهدات
     */
    public function views()
    {
        return $this->hasMany(QuestionView::class, 'question_id');
    }

    /**
     * عدد المشاهدات
     */
    public function viewsCount()
    {
        return $this->views()->count();
    }


    public function offers()
    {
        return $this->hasMany(QuestionOffer::class, 'question_id');
    }

    /**
     * المشاهدين (المجيبين اللي شافوا السؤال)
     */
    public function viewers()
    {
        return $this->belongsToMany(User::class, 'question_views', 'question_id', 'viewer_id')
            ->withTimestamps()
            ->withPivot('viewed_at');
    }
}
