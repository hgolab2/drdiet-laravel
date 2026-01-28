<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Tahoma", "Arial", sans-serif;
            background: #f8f8f8;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            max-width: 480px;
            margin: auto;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #4CAF50;
            font-size: 18px;
            margin-bottom: 10px;
        }
        p {
            font-size: 13px;
            line-height: 1.6;
            margin: 8px 0;
        }
        .login-link {
            display: block;
            background: #4CAF50;
            color: #fff;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin: 10px 0 15px 0;
        }
        .password-box {
            padding: 10px;
            background: #f1f1f1;
            border-radius: 6px;
            font-size: 16px;
            text-align: center;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        .footer {
            font-size: 11px;
            color: #999;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>مرحباً {{ $user->first_name }} 🌿</h2>

    <p>تم إنشاء حسابك بنجاح في نظام الحمية الغذائية.</p>

    <a href="{{ $loginLink }}" class="login-link">تسجيل الدخول مباشرة</a>

    <p>يمكنك أيضاً تسجيل الدخول باستخدام كلمة المرور التالية:</p>
    <div class="password-box">
        {{ $password }}
    </div>

    <p>يُرجى تغيير كلمة المرور بعد تسجيل الدخول لحماية حسابك وضمان أمان بياناتك.</p>

    <div class="footer">
        © {{ date('Y') }} نظام الحمية الغذائية – جميع الحقوق محفوظة.
    </div>
</div>
</body>
</html>
