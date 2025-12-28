<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ تم الدفع بنجاح!</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        .success-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
            fill: none;
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: drawCheck 0.5s ease-out 0.4s forwards;
        }
        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }
        h1 {
            color: #10b981;
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
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .info-text {
            color: #059669;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .info-detail {
            color: #6b7280;
            font-size: 14px;
        }
        .btn-home {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.4);
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
        <div class="success-icon">
            <svg viewBox="0 0 50 50">
                <path d="M5 30 L20 40 L45 10" />
            </svg>
        </div>
        
        <h1>✅ تم الدفع بنجاح!</h1>
        <p class="message">
            شكراً لك! تمت العملية بنجاح<br>
            تم إضافة المبلغ إلى محفظتك
        </p>

        <div class="info-box">
            <div class="info-text">
                💰 تمت إضافة المبلغ لمحفظتك
            </div>
            <div class="info-detail">
                يمكنك الآن استخدام رصيدك للدفع داخل التطبيق
            </div>
        </div>

        <a href="/" class="btn-home">
            🏠 العودة للرئيسية
        </a>

        <p class="footer">
            شكراً لاستخدامك خدماتنا 💚
        </p>
    </div>
</body>
</html>
