<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'offer_id',
        'asker_id',
        'answerer_id',
        'price',
        'response_time',
        'status',
        'answer_text',
        'answer_image',
        'answered_at',
        'cancelled_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'response_time' => 'integer',
            'answered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * العلاقات
     */
    public function question()
    {
        return $this->belongsTo(UserQuestion::class, 'question_id');
    }

    public function offer()
    {
        return $this->belongsTo(QuestionOffer::class, 'offer_id');
    }

    public function asker()
    {
        return $this->belongsTo(User::class, 'asker_id');
    }

    public function answerer()
    {
        return $this->belongsTo(User::class, 'answerer_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAnswered($query)
    {
        return $query->where('status', 'answered');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * التحقق من انتهاء المدة
     */
    public function isExpired(): bool
    {
        return $this->status === 'pending' && Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * الوقت المتبقي بالدقائق
     */
    public function getRemainingTimeAttribute(): int
    {
        if ($this->status !== 'pending') {
            return 0;
        }

        $remaining = Carbon::now()->diffInMinutes($this->expires_at, false);
        return max(0, $remaining);
    }

    /**
     * التحقق من إمكانية إلغاء الطلب من قبل السائل
     * يمكن الإلغاء إذا كان الوقت المتبقي للإجابة أكثر من 5 دقائق
     */
    public function canBeCancelledByAsker(): array
    {
        // لا يمكن إلغاء طلب غير معلق
        if ($this->status !== 'pending') {
            return [
                'can_cancel' => false,
                'reason' => 'لا يمكن إلغاء طلب ' . $this->status,
            ];
        }

        // حساب الوقت المتبقي للإجابة بالدقائق
        $remainingMinutes = $this->remaining_time;

        // يمكن الإلغاء فقط إذا كان الباقي أكثر من 5 دقائق
        if ($remainingMinutes <= 5) {
            return [
                'can_cancel' => false,
                'reason' => 'لا يمكن إلغاء الطلب. يجب أن يكون الوقت المتبقي للإجابة أكثر من 5 دقائق',
                'remaining_minutes' => $remainingMinutes,
            ];
        }

        return [
            'can_cancel' => true,
            'remaining_minutes' => $remainingMinutes,
        ];
    }

    /**
     * إلغاء الطلب من قبل السائل مع إرجاع الأموال
     */
    public function cancelByAsker()
    {
        $walletService = app(\App\Services\WalletService::class);
        
        // التحقق من وجود مبلغ محجوز
        if ($this->held_amount > 0) {
            // إرجاع المبلغ المحجوز
            $walletService->releaseHeldAmount($this->asker, $this);
        }

        // إلغاء الطلب
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * إلغاء الطلب (الطريقة القديمة)
     */
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * إلغاء الطلب وإرجاع المبلغ المحجوز
     */
    public function cancelAndRefund()
    {
        $walletService = app(\App\Services\WalletService::class);
        
        // إرجاع المبلغ المحجوز إن وجد
        if ($this->held_amount > 0) {
            $walletService->releaseHeldAmount($this->asker, $this);
        }

        // إلغاء الطلب
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * تحديث حالة الطلبات المنتهية
     */
    public static function updateExpiredOrders()
    {
        self::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}