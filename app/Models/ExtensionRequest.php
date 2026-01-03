<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtensionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'answerer_id',
        'asker_id',
        'additional_minutes',
        'reason',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'additional_minutes' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function answerer()
    {
        return $this->belongsTo(User::class, 'answerer_id');
    }

    public function asker()
    {
        return $this->belongsTo(User::class, 'asker_id');
    }

    /**
     * قبول الطلب
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        // تمديد مدة الطلب
        $currentExpiry = \Carbon\Carbon::parse($this->order->expires_at);
        $this->order->update([
            'expires_at' => $currentExpiry->addMinutes($this->additional_minutes),
        ]);
    }

    /**
     * رفض الطلب
     */
    public function reject()
    {
        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        // إلغاء الطلب وإرجاع المبلغ
        $this->order->cancelAndRefund();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
