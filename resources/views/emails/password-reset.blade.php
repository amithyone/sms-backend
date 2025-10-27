<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - FADDED SMS</title>
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
            padding: 15px 40px;
            background: #667eea;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔐 Password Reset Request</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $user->name }},</h2>
        
        <p>We received a request to reset your password for your FADDED SMS account.</p>
        
        <p>Click the button below to reset your password:</p>
        
        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Reset Password</a>
        </div>
        
        <p>Or copy and paste this link into your browser:</p>
        <p style="background: #fff; padding: 15px; border-radius: 5px; word-break: break-all;">
            {{ $resetUrl }}
        </p>
        
        <div class="warning-box">
            <strong>⚠️ Security Notice:</strong><br>
            This password reset link will expire in <strong>60 minutes</strong>.<br>
            If you didn't request a password reset, please ignore this email or contact support if you have concerns.
        </div>
        
        <p>
            <strong>Need Help?</strong><br>
            If you're having trouble clicking the button, copy and paste the URL above into your web browser.<br>
            Contact us at <a href="mailto:help@faddedsms.com">help@faddedsms.com</a> if you need assistance.
        </p>
        
        <p>
            Best regards,<br>
            <strong>The FADDED SMS Team</strong>
        </p>
    </div>
    
    <div class="footer">
        <p>
            © {{ date('Y') }} FADDED SMS. All rights reserved.<br>
            This email was sent to {{ $user->email }}
        </p>
    </div>
</body>
</html>

