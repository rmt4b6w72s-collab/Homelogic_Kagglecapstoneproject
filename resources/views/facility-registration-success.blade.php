<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Submitted - Evergreen Care Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #d4edda;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: #155724;
        }

        h1 {
            color: #667eea;
            margin-bottom: 15px;
        }

        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .back-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <h1>Registration Submitted Successfully!</h1>
        <p>Thank you for your interest in our care management platform.</p>
        <p>We have received your registration request and will review it shortly. Our team will contact you at the email address you provided once your facility has been approved and set up.</p>
        <p><strong>What happens next?</strong></p>
        <ul style="text-align: left; display: inline-block; color: #666; margin: 20px 0;">
            <li>Our super admin will review your registration</li>
            <li>We'll set up your facility and create your admin account</li>
            <li>You'll receive an email with your login credentials</li>
            <li>You can then access your facility's admin panel</li>
        </ul>
        <a href="/" class="back-btn">Return to Home</a>
    </div>
</body>
</html>

