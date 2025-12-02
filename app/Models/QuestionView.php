<?php

// app/Models/QuestionView.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionView extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'viewer_id',
        'asker_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * العلاقة مع السؤال
     */
    public function question()
    {
        return $this->belongsTo(UserQuestion::class, 'question_id');
    }

    /**
     * العلاقة مع المجيب (المشاهد)
     */
    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }

    /**
     * العلاقة مع السائق (صاحب السؤال)
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}