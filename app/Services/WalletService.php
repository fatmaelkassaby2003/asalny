<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    /**
     * إضافة رصيد للمحفظة
     */
    public function deposit(User $user, float $amount, string $description = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $balanceBefore = $user->wallet_balance;
            $balanceAfter = $balanceBefore + $amount;

            $user->update(['wallet_balance' => $balanceAfter]);

            return WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description ?? 'إيداع في المحفظة',
            ]);
        });
    }

    /**
     * سحب رصيد من المحفظة
     */
    public function withdraw(User $user, float $amount, string $description = null): WalletTransaction
    {
        if ($user->wallet_balance < $amount) {
            throw new Exception('الرصيد غير كافي');
        }

        return DB::transaction(function () use ($user, $amount, $description) {
            $balanceBefore = $user->wallet_balance;
            $balanceAfter = $balanceBefore - $amount;

            $user->update(['wallet_balance' => $balanceAfter]);

            return WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'withdraw',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description ?? 'سحب من المحفظة',
            ]);
        });
    }

    /**
     * حجز مبلغ عند قبول العرض
     */
    public function holdAmount(User $user, Order $order, float $amount): WalletTransaction
    {
        if ($user->wallet_balance < $amount) {
            throw new Exception('الرصيد غير كافي. يرجى شحن المحفظة أولاً.');
        }

        return DB::transaction(function () use ($user, $order, $amount) {
            $balanceBefore = $user->wallet_balance;
            $balanceAfter = $balanceBefore - $amount;

            $user->update(['wallet_balance' => $balanceAfter]);
            $order->update(['held_amount' => $amount]);

            return WalletTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => 'hold',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "حجز مبلغ {$amount} للطلب #{$order->id}",
            ]);
        });
    }

    /**
     * إرجاع المبلغ المحجوز عند الإلغاء
     */
    public function releaseHeldAmount(User $user, Order $order): WalletTransaction
    {
        $amount = $order->held_amount;

        if ($amount <= 0) {
            throw new Exception('لا يوجد مبلغ محجوز لهذا الطلب');
        }

        return DB::transaction(function () use ($user, $order, $amount) {
            $balanceBefore = $user->wallet_balance;
            $balanceAfter = $balanceBefore + $amount;

            $user->update(['wallet_balance' => $balanceAfter]);
            $order->update(['held_amount' => 0]);

            return WalletTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'type' => 'release',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "إرجاع مبلغ محجوز {$amount} من الطلب #{$order->id}",
            ]);
        });
    }

    /**
     * تحويل المبلغ للمجيب عند الإجابة
     */
    public function transferToAnswerer(Order $order): array
    {
        $amount = $order->held_amount;

        if ($amount <= 0) {
            throw new Exception('لا يوجد مبلغ محجوز لهذا الطلب');
        }

        if ($order->amount_transferred) {
            throw new Exception('تم تحويل المبلغ مسبقاً');
        }

        return DB::transaction(function () use ($order, $amount) {
            $asker = $order->asker;
            $answerer = $order->answerer;

            // تسجيل معاملة للسائل (تحويل صادر)
            $askerBalanceBefore = $asker->wallet_balance;
            
            $askerTransaction = WalletTransaction::create([
                'user_id' => $asker->id,
                'order_id' => $order->id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'balance_before' => $askerBalanceBefore,
                'balance_after' => $askerBalanceBefore, // الرصيد لم يتغير لأنه محجوز
                'description' => "تحويل {$amount} للمجيب عن الطلب #{$order->id}",
            ]);

            // تسجيل معاملة للمجيب (تحويل وارد)
            $answererBalanceBefore = $answerer->wallet_balance;
            $answererBalanceAfter = $answererBalanceBefore + $amount;

            $answerer->update(['wallet_balance' => $answererBalanceAfter]);

            $answererTransaction = WalletTransaction::create([
                'user_id' => $answerer->id,
                'order_id' => $order->id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $answererBalanceBefore,
                'balance_after' => $answererBalanceAfter,
                'description' => "استلام {$amount} من الطلب #{$order->id}",
            ]);

            // تحديث حالة التحويل في الطلب
            $order->update([
                'held_amount' => 0,
                'amount_transferred' => true,
                'amount_transferred_at' => now(),
            ]);

            return [
                'asker_transaction' => $askerTransaction,
                'answerer_transaction' => $answererTransaction,
            ];
        });
    }

    /**
     * عرض رصيد المحفظة
     */
    public function getBalance(User $user): float
    {
        return $user->wallet_balance;
    }

    /**
     * عرض سجل المعاملات
     */
    public function getTransactions(User $user, int $limit = 50)
    {
        return WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}