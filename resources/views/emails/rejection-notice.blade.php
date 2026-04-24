<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: #6b7280;
            padding: 32px;
            text-align: center;
        }

        .header h1 {
            color: #fff;
            margin: 0;
            font-size: 22px;
        }

        .body {
            padding: 32px;
            color: #374151;
            line-height: 1.7;
        }

        .footer {
            background: #f9fafb;
            padding: 20px 32px;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Application Update</h1>
        </div>
        <div class="body">
            {!! nl2br(e($mailBody)) !!}
        </div>
        <div class="footer">
            This email was sent via the Resume Screening Tool. Please do not reply directly to this email.
        </div>
    </div>
</body>

</html>
