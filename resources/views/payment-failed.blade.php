<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>❌ فشل الدفع</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .error-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        .error-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
            fill: none;
        }
        h1 {
            color: #ef4444;
            margin-bottom: 15px;
            font-size: 32px;
        }
        .message {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 18px;
            line-height: 1.6;
        }
        .info-box {
            background: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .info-text {
            color: #dc2626;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .info-detail {
            color: #6b7280;
            font-size: 14px;
        }
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-retry {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }
        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(239, 68, 68, 0.4);
        }
        .btn-home {
            background: #f3f4f6;
            color: #6b7280;
        }
        .btn-home:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }
        .footer {
            margin-top: 30px;
            color: #9ca3af;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">
            <svg viewBox="0 0 50 50">
                <line x1="15" y1="15" x2="35" y2="35" />
                <line x1="35" y1="15" x2="15" y2="35" />
            </svg>
        </div>
        
        <h1>❌ فشل الدفع</h1>
        <p class="message">
            عذراً! لم تكتمل عملية الدفع<br>
            لم يتم خصم أي مبلغ
        </p>

        <div class="info-box">
            <div class="info-text">
                💡 الأسباب المحتملة:
            </div>
            <div class="info-detail">
                • إلغاء العملية<br>
                • رصيد غير كافٍ<br>
                • خطأ في بيانات الدفع
            </div>
        </div>

        <div class="buttons">
            <a href="javascript:history.back()" class="btn btn-retry">
                🔄 المحاولة مرة أخرى
            </a>
            <a href="/" class="btn btn-home">
                🏠 العودة للرئيسية
            </a>
        </div>

        <p class="footer">
            يمكنك المحاولة مرة أخرى أو التواصل مع الدعم
        </p>
    </div>
</body>
</html>
