<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 صفحة دفع تجريبية - Fawaterak Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .test-badge {
            display: inline-block;
            background: #fbbf24;
            color: #78350f;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        .invoice-info {
            background: #f3f4f6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            color: #6b7280;
            font-weight: 500;
        }
        .value {
            color: #1f2937;
            font-weight: 600;
        }
        .amount {
            font-size: 32px;
            color: #10b981;
        }
        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16,185,129,0.3);
        }
        .btn-fail {
            background: #ef4444;
            color: white;
        }
        .btn-fail:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239,68,68,0.3);
        }
        .note {
            background: #fef3c7;
            border-right: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="test-badge">🧪 وضع الاختبار</span>
            <h1>صفحة الدفع التجريبية</h1>
            <p style="color: #6b7280;">Fawaterak Test Payment</p>
        </div>

        <div class="invoice-info">
            <div class="info-row">
                <span class="label">رقم الفاتورة</span>
                <span class="value">{{ $invoice }}</span>
            </div>
            <div class="info-row">
                <span class="label">المبلغ</span>
                <span class="value amount">{{ number_format($amount, 2) }} جنيه</span>
            </div>
            <div class="info-row">
                <span class="label">الخدمة</span>
                <span class="value">إيداع في المحفظة</span>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div style="margin-bottom: 25px;">
            <label style="display: block; margin-bottom: 12px; color: #1f2937; font-weight: 600; font-size: 15px;">
                💳 اختر وسيلة الدفع
            </label>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                <label class="payment-option" for="card">
                    <input type="radio" name="payment_method" id="card" value="card" checked>
                    <div class="option-content">
                        <span class="icon">💳</span>
                        <span class="text">بطاقة</span>
                    </div>
                </label>
                <label class="payment-option" for="bank">
                    <input type="radio" name="payment_method" id="bank" value="bank_transfer">
                    <div class="option-content">
                        <span class="icon">🏦</span>
                        <span class="text">تحويل</span>
                    </div>
                </label>
                <label class="payment-option" for="cash">
                    <input type="radio" name="payment_method" id="cash" value="cash">
                    <div class="option-content">
                        <span class="icon">💵</span>
                        <span class="text">كاش</span>
                    </div>
                </label>
            </div>
        </div>

        <div class="buttons">
            <button class="btn-success" onclick="completePayment('success')">
                ✅ دفع ناجح
            </button>
            <button class="btn-fail" onclick="completePayment('failed')">
                ❌ رفض الدفع
            </button>
        </div>

        <div class="note">
            <strong>ملاحظة:</strong> هذه صفحة تجريبية للاختبار فقط. في الوضع الفعلي، سيتم التوجيه إلى بوابة الدفع الحقيقية.
        </div>
    </div>

    <style>
        .payment-option {
            cursor: pointer;
            display: block;
        }
        .payment-option input[type="radio"] {
            display: none;
        }
        .option-content {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px 10px;
            text-align: center;
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .payment-option:hover .option-content {
            border-color: #667eea;
            background: #f5f3ff;
        }
        .payment-option input[type="radio"]:checked + .option-content {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .option-content .icon {
            font-size: 28px;
            display: block;
        }
        .option-content .text {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
        }
    </style>

    <script>
        function completePayment(status) {
            const invoice = '{{ $invoice }}';
            const amount = {{ $amount }};
            const userId = {{ $userId ?? 1 }};
            
            // Get selected payment method
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const paymentMethodNames = {
                'card': 'بطاقة',
                'bank_transfer': 'تحويل بنكي',
                'cash': 'كاش'
            };
            
            if (status === 'success') {
                // محاكاة webhook للدفع الناجح
                fetch('/api/fawaterak/webhook', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        refrence_id: invoice,
                        payment_status: 'paid',
                        cart_amount: amount,
                        payment_method: paymentMethod,
                        success_url: window.location.origin + '/api/fawaterak/callback?type=deposit&user_id=' + userId + '&status=success'
                    })
                }).then(() => {
                    window.location.href = '/api/fawaterak/callback?status=success';
                });
            } else {
                window.location.href = '/api/fawaterak/callback?status=failed';
            }
        }
    </script>
</body>
</html>
