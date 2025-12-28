<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'asker_id',
        'answerer_id',
        'order_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * العلاقة مع السائل
     */
    public function asker()
    {
        return $this->belongsTo(User::class, 'asker_id');
    }

    /**
     * العلاقة مع المجيب
     */
    public function answerer()
    {
        return $this->belongsTo(User::class, 'answerer_id');
    }

    /**
     * العلاقة مع الطلب
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * العلاقة مع الرسائل
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * آخر رسالة
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * عدد الرسائل غير المقروءة للمستخدم
     */
    public function unreadCountFor($userId)
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * الحصول على المشارك الآخر في المحادثة
     */
    public function getOtherParticipant($userId)
    {
        if ($this->asker_id == $userId) {
            return $this->answerer;
        }
        return $this->asker;
    }

    /**
     * التحقق من أن المستخدم مشارك في المحادثة
     */
    public function isParticipant($userId)
    {
        return $this->asker_id == $userId || $this->answerer_id == $userId;
    }
}
