<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * عرض رصيد المحفظة
     */
    public function balance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $balance = $this->walletService->getBalance($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'balance' => $balance,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض الرصيد',
            ], 500);
        }
    }

    /**
     * إضافة رصيد للمحفظة
     */
    public function deposit(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1|max:10000',
                'payment_method' => 'required|string|in:card,bank_transfer,cash',
            ], [
                'amount.required' => 'المبلغ مطلوب',
                'amount.numeric' => 'المبلغ يجب أن يكون رقم',
                'amount.min' => 'الحد الأدنى للإيداع هو 1',
                'amount.max' => 'الحد الأقصى للإيداع هو 10000',
                'payment_method.required' => 'طريقة الدفع مطلوبة',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            
            // هنا يمكن إضافة معالجة الدفع الفعلية
            $transaction = $this->walletService->deposit(
                $user, 
                $request->amount,
                "إيداع عبر {$request->payment_method}"
            );

            Log::info('✅ تم إيداع مبلغ في المحفظة', [
                'user_id' => $user->id,
                'amount' => $request->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إيداع المبلغ بنجاح',
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'balance_after' => $transaction->balance_after,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إيداع المبلغ', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * سحب رصيد من المحفظة
     */
    public function withdraw(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
            ], [
                'amount.required' => 'المبلغ مطلوب',
                'amount.numeric' => 'المبلغ يجب أن يكون رقم',
                'amount.min' => 'الحد الأدنى للسحب هو 1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();

            $transaction = $this->walletService->withdraw($user, $request->amount);

            return response()->json([
                'success' => true,
                'message' => 'تم سحب المبلغ بنجاح',
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'balance_after' => $transaction->balance_after,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * عرض سجل المعاملات
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $transactions = $this->walletService->getTransactions($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => $transactions->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'type' => $transaction->type,
                            'amount' => $transaction->amount,
                            'balance_before' => $transaction->balance_before,
                            'balance_after' => $transaction->balance_after,
                            'description' => $transaction->description,
                            'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'total' => $transactions->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء عرض المعاملات',
            ], 500);
        }
    }

    /**
     * إيداع عبر Fawaterak - إنشاء invoice
     */
    public function depositViaFawaterak(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10|max:10000',
            ], [
                'amount.required' => 'المبلغ مطلوب',
                'amount.numeric' => 'المبلغ يجب أن يكون رقم',
                'amount.min' => 'الحد الأدنى للإيداع هو 10',
                'amount.max' => 'الحد الأقصى للإيداع هو 10000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            $fawaterakService = app(\App\Services\FawaterakService::class);

            // إنشاء فاتورة للإيداع
            $result = $fawaterakService->createDepositInvoice($user, $request->amount);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل إنشاء فاتورة الإيداع',
                    'error' => $result['message'] ?? 'حدث خطأ',
                    'details' => $result['error_details'] ?? null,
                ], 500);
            }

            Log::info('✅ تم إنشاء فاتورة إيداع', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'invoice_id' => $result['data']['invoice_id'],
            ]);

            // إذا كان الطلب من المتصفح، اعرض صفحة redirect
            if ($request->header('Accept') && str_contains($request->header('Accept'), 'text/html')) {
                $paymentUrl = $result['data']['url'];
                return response()->view('payment-redirect', [
                    'payment_url' => $paymentUrl,
                    'amount' => $request->amount,
                    'invoice_id' => $result['data']['invoice_id']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء رابط الدفع بنجاح',
                'data' => [
                    'payment_url' => $result['data']['url'],
                    'invoice_id' => $result['data']['invoice_id'],
                    'amount' => $request->amount,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إنشاء فاتورة Fawaterak', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء رابط الدفع',
            ], 500);
        }
    }

    /**
     * طلب سحب عبر Fawaterak (يتطلب موافقة إدارية)
     */
    public function withdrawViaFawaterak(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10',
                'bank_account' => 'required|string',
            ], [
                'amount.required' => 'المبلغ مطلوب',
                'amount.numeric' => 'المبلغ يجب أن يكون رقم',
                'amount.min' => 'الحد الأدنى للسحب هو 10',
                'bank_account.required' => 'رقم الحساب البنكي مطلوب',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            $currentBalance = $this->walletService->getBalance($user);

            if ($currentBalance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'رصيدك غير كافي',
                    'data' => [
                        'current_balance' => $currentBalance,
                        'requested_amount' => $request->amount,
                    ]
                ], 400);
            }

            // سحب المبلغ من المحفظة (سيتم تحويله لاحقاً)
            $transaction = $this->walletService->withdraw(
                $user,
                $request->amount,
                "طلب سحب إلى {$request->bank_account}"
            );

            Log::info('✅ تم إنشاء طلب سحب', [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'bank_account' => $request->bank_account,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء طلب السحب بنجاح. سيتم تحويل المبلغ خلال 1-3 أيام عمل',
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'balance_after' => $transaction->balance_after,
                        'status' => 'pending',
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في طلب السحب', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}