<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class PaymentController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * معالجة webhook من Fawaterak
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            Log::info('📥 Webhook من Fawaterak', [
                'data' => $data,
            ]);

            // التحقق من نوع العملية من الـ reference_id
            $refId = $data['refrence_id'] ?? $data['reference_id'] ?? null;
            $status = $data['payment_status'] ?? null;
            
            if (!$refId || $status !== 'paid') {
                Log::warning('⚠️ Webhook غير مكتمل', [
                    'ref_id' => $refId,
                    'status' => $status,
                ]);
                
                return response()->json(['message' => 'Invalid webhook'], 400);
            }

            // استخراج معلومات من success URL
            $successUrl = $data['success_url'] ?? '';
            parse_str(parse_url($successUrl, PHP_URL_QUERY), $params);
            
            $type = $params['type'] ?? null;
            $userId = $params['user_id'] ?? null;
            
            if ($type === 'deposit' && $userId) {
                // معالجة إيداع
                $this->processDeposit($userId, $data);
            }

            return response()->json(['message' => 'Webhook processed'], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في معالجة webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Error'], 500);
        }
    }

    /**
     * معالجة إيداع بعد الدفع الناجح
     */
    protected function processDeposit($userId, $data)
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                Log::error('❌ المستخدم غير موجود', ['user_id' => $userId]);
                return;
            }

            $amount = $data['cart_amount'] ?? 0;
            $paymentMethod = $data['payment_method'] ?? 'fawaterak';
            
            $paymentMethodNames = [
                'card' => 'بطاقة',
                'bank_transfer' => 'تحويل بنكي',
                'cash' => 'كاش',
                'fawaterak' => 'Fawaterak'
            ];
            
            $methodName = $paymentMethodNames[$paymentMethod] ?? $paymentMethod;
            
            // إضافة المبلغ للمحفظة
            $transaction = $this->walletService->deposit(
                $user,
                $amount,
                "إيداع عبر {$methodName} - Invoice: {$data['refrence_id']}"
            );

            // ✅ إرسال إشعار للمستخدم بنجاح الإيداع
            \App\Helpers\NotificationHelper::notifyWalletDeposit($user, $amount, $data['refrence_id']);

            Log::info('✅ تم إيداع المبلغ في المحفظة', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'invoice_id' => $data['refrence_id'],
                'transaction_id' => $transaction->id,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في معالجة الإيداع', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * معالجة callback (redirect) بعد الدفع
     */
    public function callback(Request $request)
    {
        $type = $request->query('type');
        $status = $request->query('status', 'success');

        if ($status === 'success') {
            return view('payment-success', [
                'type' => $type,
            ]);
        }

        return view('payment-failed', [
            'type' => $type,
        ]);
    }
}
