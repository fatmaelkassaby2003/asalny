<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'answerer_id',
        'asker_id',
        'price',
        'response_time',
        'note',
        'status',
        'accepted_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'response_time' => 'integer',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * العلاقة مع السؤال
     */
    public function question()
    {
        return $this->belongsTo(UserQuestion::class, 'question_id');
    }


    public function order()
    {
        return $this->hasOne(Order::class, 'offer_id');
    }

    /**
     * العلاقة مع المجيب
     */
    public function answerer()
    {
        return $this->belongsTo(User::class, 'answerer_id');
    }

    /**
     * العلاقة مع السائل
     */
    public function asker()
    {
        return $this->belongsTo(User::class, 'asker_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * قبول العرض
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    /**
     * رفض العرض
     */
    public function reject()
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);
    }

    /**
     * إلغاء العرض
     */
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}
