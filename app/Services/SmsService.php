<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsService
{
    protected $twilio;
    
    public function __construct()
    {
        $this->twilio = new Client(
            env('TWILIO_SID'),
            env('TWILIO_TOKEN')
        );
    }
    
    /**
     * إرسال رسالة SMS
     */
    public function sendVerificationCode($phone, $code)
    {
        try {
            // تنسيق رقم الهاتف بشكل صحيح
            $formattedPhone = $this->formatPhoneNumber($phone);
            
            Log::channel('daily')->info('=================================');
            Log::channel('daily')->info('📱 Verification Code Sent');
            Log::channel('daily')->info('Original Phone: ' . $phone);
            Log::channel('daily')->info('Formatted Phone: ' . $formattedPhone);
            Log::channel('daily')->info('Code: ' . $code);
            Log::channel('daily')->info('Time: ' . now()->format('Y-m-d H:i:s'));
            Log::channel('daily')->info('=================================');
            
            return $this->sendViaTwilioSms($formattedPhone, $code);
            
        } catch (\Exception $e) {
            Log::error('❌ SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تنسيق رقم الهاتف المصري
     */
    protected function formatPhoneNumber($phone)
    {
        // إزالة أي مسافات أو رموز
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // إذا كان الرقم يبدأ بـ +20، أرجعه كما هو
        if (str_starts_with($phone, '+20')) {
            return $phone;
        }
        
        // إذا كان يبدأ بـ 20، أضف +
        if (str_starts_with($phone, '20')) {
            return '+' . $phone;
        }
        
        // إذا كان يبدأ بـ 0، استبدلها بـ +20
        if (str_starts_with($phone, '0')) {
            return '+2' . substr($phone, 1); // +201008379521
        }
        
        // إذا كان الرقم بدون أي prefix، أضف +20
        return '+20' . $phone;
    }
    
    /**
     * إرسال SMS عبر Twilio
     */
    protected function sendViaTwilioSms($phone, $code)
    {
        try {
            $message = $this->twilio->messages->create(
                $phone,
                [
                    'from' => env('TWILIO_FROM'),
                    'body' => "كود التحقق: $code\n\nYour verification code is: $code"
                ]
            );
            
            Log::info('✅ Twilio SMS sent successfully', [
                'sid' => $message->sid,
                'status' => $message->status,
                'to' => $message->to
            ]);
            
            return true;
            
        } catch (\Twilio\Exceptions\RestException $e) {
            Log::error('❌ Twilio SMS failed: ' . $e->getMessage());
            Log::error('Phone number used: ' . $phone);
            return false;
        }
    }
}