<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔄 جاري التحويل للدفع...</title>
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
            padding: 50px 40px;
            text-align: center;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        h1 {
            color: #1f2937;
            margin-bottom: 15px;
            font-size: 28px;
        }
        p {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .info-box {
            background: #f3f4f6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-item:last-child {
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
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }
        .note {
            margin-top: 20px;
            font-size: 14px;
            color: #9ca3af;
        }
        .countdown {
            font-size: 14px;
            color: #667eea;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">💳</div>
        <h1>✅ تم إنشاء رابط الدفع بنجاح!</h1>
        <p>سيتم تحويلك تلقائياً لصفحة الدفع...</p>

        <div class="info-box">
            <div class="info-item">
                <span class="label">المبلغ</span>
                <span class="value">{{ number_format($amount, 2) }} جنيه</span>
            </div>
            <div class="info-item">
                <span class="label">رقم الفاتورة</span>
                <span class="value">{{ $invoice_id }}</span>
            </div>
        </div>

        <a href="{{ $payment_url }}" class="btn" id="paymentBtn">
            🚀 انتقل للدفع الآن
        </a>

        <p class="countdown">سيتم التحويل التلقائي خلال <span id="timer">3</span> ثواني...</p>

        <p class="note">
            💡 إذا لم يتم التحويل تلقائياً، اضغط الزر أعلاه
        </p>
    </div>

    <script>
        let countdown = 3;
        const timerElement = document.getElementById('timer');
        const paymentUrl = '{{ $payment_url }}';

        const interval = setInterval(() => {
            countdown--;
            timerElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = paymentUrl;
            }
        }, 1000);
    </script>
</body>
</html>
