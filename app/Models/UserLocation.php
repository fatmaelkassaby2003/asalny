<?php

// app/Models/UserLocation.php

// app/Models/UserLocation.php - تحديث

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_current' => 'boolean',
        ];
    }

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة الأسئلة المرتبطة بهذا الموقع
     */
    public function questions()
    {
        return $this->hasMany(UserQuestion::class, 'location_id');
    }

    /**
     * حساب المسافة بين نقطتين (بالكيلومتر)
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Scope: البحث بالقرب من موقع معين
     */
    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 10)
    {
        $latDelta = $radiusKm / 111;
        $lonDelta = $radiusKm / (111 * cos(deg2rad($latitude)));

        return $query->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
                     ->whereBetween('longitude', [$longitude - $lonDelta, $longitude + $lonDelta]);
    }
}