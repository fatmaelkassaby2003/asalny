<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'asker_id',
        'answerer_id',
        'stars',
        'comment',
        'order_id',
    ];

    protected $casts = [
        'stars' => 'integer',
    ];

    /**
     * السائل الذي قيّم
     */
    public function asker()
    {
        return $this->belongsTo(User::class, 'asker_id');
    }

    /**
     * المجيب الذي تم تقييمه
     */
    public function answerer()
    {
        return $this->belongsTo(User::class, 'answerer_id');
    }

    /**
     * الطلب المرتبط بالتقييم
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
