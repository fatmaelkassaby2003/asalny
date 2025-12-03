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
}