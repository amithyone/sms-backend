<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to FADDED SMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .feature-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .feature-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Welcome to FADDED SMS!</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->name }}!</h2>
        
        <p>Thank you for registering with FADDED SMS. We're excited to have you on board!</p>
        
        <p>Your account has been successfully created with the email: <strong>{{ $user->email }}</strong></p>
        
        <div class="feature-list">
            <h3>What You Can Do:</h3>
            <div class="feature-item">
                📱 <strong>Virtual Number Verification</strong> - Get SMS verification for WhatsApp, Telegram, and 200+ services
            </div>
            <div class="feature-item">
                💳 <strong>VTU Services</strong> - Buy airtime and data for all networks
            </div>
            <div class="feature-item">
                ⚡ <strong>Electricity</strong> - Pay your electricity bills instantly
            </div>
            <div class="feature-item">
                📺 <strong>Cable TV</strong> - Subscribe to DSTV, GOTV, and Startimes
            </div>
            <div class="feature-item">
                🎓 <strong>Education</strong> - Buy exam pins and scratch cards
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ env('FRONTEND_URL') }}" class="button">Get Started</a>
        </div>
        
        <p style="margin-top: 30px;">
            <strong>Need Help?</strong><br>
            If you have any questions or need assistance, feel free to contact our support team at 
            <a href="mailto:help@faddedsms.com">help@faddedsms.com</a>
        </p>
        
        <p>
            Best regards,<br>
            <strong>The FADDED SMS Team</strong>
        </p>
    </div>
    
    <div class="footer">
        <p>
            © {{ date('Y') }} FADDED SMS. All rights reserved.<br>
            <a href="{{ env('FRONTEND_URL') }}">Visit our website</a>
        </p>
    </div>
</body>
</html>

