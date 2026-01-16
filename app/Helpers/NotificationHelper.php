<?php

namespace App\Helpers;

use App\Services\FirebaseService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    protected static $firebase;

    protected static function getFirebase()
    {
        if (!self::$firebase) {
            self::$firebase = app(FirebaseService::class);
        }
        return self::$firebase;
    }

    /**
     * إشعار: سؤال جديد للمجيبين القريبين
     */
    public static function notifyNearbyAnswerers($question, $nearbyAnswerers)
    {
        $firebase = self::getFirebase();
        
        foreach ($nearbyAnswerers as $answerer) {
            if ($answerer->fcm_token) {
                $firebase->sendToUser(
                    $answerer->fcm_token,
                    "سؤال جديد قريب منك! 📍",
                    "سؤال جديد: {$question->title}",
                    [
                        'type' => 'new_question',
                        'question_id' => (string)$question->id,
                        'distance_km' => $answerer->distance_km ?? '0',
                    ]
                );
            }
        }
        
        Log::info('📢 Notified nearby answerers', [
            'question_id' => $question->id,
            'count' => count($nearbyAnswerers),
        ]);
    }

    /**
     * إشعار: عرض جديد على سؤالك
     */
    public static function notifyNewOffer($offer, $asker)
    {
        if (!$asker->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $asker->fcm_token,
            "عرض جديد على سؤالك! 💼",
            "{$offer->answerer->name} قدم عرض بقيمة {$offer->price} جنيه",
            [
                'type' => 'new_offer',
                'offer_id' => (string)$offer->id,
                'question_id' => (string)$offer->question_id,
                'answerer_id' => (string)$offer->answerer_id,
                'price' => (string)$offer->price,
            ]
        );
    }

    /**
     * إشعار: تم قبول عرضك
     */
    public static function notifyOfferAccepted($offer, $answerer)
    {
        if (!$answerer->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $answerer->fcm_token,
            "تم قبول عرضك! 🎉",
            "تم قبول عرضك على: {$offer->question->title}",
            [
                'type' => 'offer_accepted',
                'offer_id' => (string)$offer->id,
                'question_id' => (string)$offer->question_id,
                'price' => (string)$offer->price,
            ]
        );
    }

    /**
     * إشعار: تم رفض عرضك
     */
    public static function notifyOfferRejected($offer, $answerer)
    {
        if (!$answerer->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $answerer->fcm_token,
            "تم رفض عرضك",
            "عذراً، تم رفض عرضك على: {$offer->question->title}",
            [
                'type' => 'offer_rejected',
                'offer_id' => (string)$offer->id,
                'question_id' => (string)$offer->question_id,
            ]
        );
    }

    /**
     * إشعار: تقييم جديد
     */
    public static function notifyNewRating($rating, $answerer)
    {
        if (!$answerer->fcm_token) return;

        $stars = str_repeat('⭐', $rating->rating);
        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $answerer->fcm_token,
            "تقييم جديد! $stars",
            "{$rating->asker->name} قيمك بـ {$rating->rating} نجوم",
            [
                'type' => 'new_rating',
                'rating_id' => (string)$rating->id,
                'rating' => (string)$rating->rating,
                'asker_id' => (string)$rating->asker_id,
            ]
        );
    }

    /**
     * إشعار: شحن محفظة
     */
    public static function notifyWalletDeposit($user, $amount, $transactionId = null)
    {
        if (!$user->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $user->fcm_token,
            "تم شحن محفظتك بنجاح ✅",
            "تم إضافة {$amount} جنيه إلى محفظتك",
            [
                'type' => 'wallet_deposit',
                'amount' => (string)$amount,
                'transaction_id' => $transactionId ?? '',
            ]
        );
    }

    /**
     * إشعار: سحب من المحفظة
     */
    public static function notifyWalletWithdraw($user, $amount, $transactionId = null)
    {
        if (!$user->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $user->fcm_token,
            "تم السحب من محفظتك",
            "تم خصم {$amount} جنيه من محفظتك",
            [
                'type' => 'wallet_withdraw',
                'amount' => (string)$amount,
                'transaction_id' => $transactionId ?? '',
            ]
        );
    }

    /**
     * إشعار: تحويل الأموال للمجيب (بعد إتمام الطلب)
     */
    public static function notifyPaymentReceived($answerer, $amount, $questionTitle)
    {
        if (!$answerer->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $answerer->fcm_token,
            "استلمت دفعة جديدة! 💰",
            "تم تحويل {$amount} جنيه إلى محفظتك من: {$questionTitle}",
            [
                'type' => 'payment_received',
                'amount' => (string)$amount,
            ]
        );
    }

    /**
     * إشعار: استرجاع الأموال للسائل
     */
    public static function notifyRefund($asker, $amount, $reason)
    {
        if (!$asker->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $asker->fcm_token,
            "تم استرجاع أموالك 🔄",
            "تم إرجاع {$amount} جنيه إلى محفظتك. السبب: {$reason}",
            [
                'type' => 'refund',
                'amount' => (string)$amount,
                'reason' => $reason,
            ]
        );
    }

    /**
     * إشعار: رسالة شات جديدة
     */
    public static function notifyNewMessage($message, $receiver)
    {
        if (!$receiver->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $receiver->fcm_token,
            "رسالة جديدة من {$message->sender->name} 💬",
            substr($message->message, 0, 100),
            [
                'type' => 'new_message',
                'chat_id' => (string)$message->chat_id,
                'message_id' => (string)$message->id,
                'sender_id' => (string)$message->sender_id,
            ]
        );
    }

    /**
     * إشعار: اقتراب انتهاء وقت الإجابة
     */
    public static function notifyTimerWarning($order, $answerer, $timeLeft)
    {
        if (!$answerer->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $answerer->fcm_token,
            "تحذير: الوقت على وشك الانتهاء! ⏰",
            "المتبقي: {$timeLeft} دقيقة لإنهاء الطلب",
            [
                'type' => 'timer_warning',
                'order_id' => (string)$order->id,
                'time_left_minutes' => (string)$timeLeft,
            ]
        );
    }

    /**
     * إشعار: تم اعتماد الإجابة
     */
    public static function notifyAnswerApproved($order, $answerer)
    {
        if (!$answerer->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $answerer->fcm_token,
            "تم اعتماد إجابتك! ✅",
            "السائل وافق على إجابتك",
            [
                'type' => 'answer_approved',
                'order_id' => (string)$order->id,
                'question_id' => (string)$order->question_id,
            ]
        );
    }

    /**
     * إشعار: طلب تمديد وقت
     */
    public static function notifyExtensionRequest($extension, $asker)
    {
        if (!$asker->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $asker->fcm_token,
            "طلب تمديد الوقت ⏱️",
            "{$extension->answerer->name} يطلب {$extension->extra_minutes} دقيقة إضافية",
            [
                'type' => 'extension_request',
                'extension_id' => (string)$extension->id,
                'extra_minutes' => (string)$extension->extra_minutes,
            ]
        );
    }

    /**
     * إشعار: تم قبول/رفض طلب التمديد
     */
    public static function notifyExtensionResponse($extension, $answerer, $accepted)
    {
        if (!$answerer->fcm_token) return;

        $firebase = self::getFirebase();
        $title = $accepted ? "تم قبول طلب التمديد ✅" : "تم رفض طلب التمديد ❌";
        $body = $accepted 
            ? "حصلت على {$extension->extra_minutes} دقيقة إضافية"
            : "عذراً، تم رفض طلب التمديد";

        $firebase->sendToUser(
            $answerer->fcm_token,
            $title,
            $body,
            [
                'type' => 'extension_response',
                'extension_id' => (string)$extension->id,
                'accepted' => $accepted,
            ]
        );
    }

    /**
     * إشعار: الطلب ملغي
     */
    public static function notifyOrderCancelled($order, $user, $reason)
    {
        if (!$user->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $user->fcm_token,
            "تم إلغاء الطلب",
            "السبب: {$reason}",
            [
                'type' => 'order_cancelled',
                'order_id' => (string)$order->id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * إشعار عام مخصص
     */
    public static function sendCustom($user, $title, $body, $data = [])
    {
        if (!$user->fcm_token) return;

        $firebase = self::getFirebase();
        $firebase->sendToUser(
            $user->fcm_token,
            $title,
            $body,
            $data
        );
    }
}
