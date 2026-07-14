<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $titleText }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            padding: 20px;
            margin: 0;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 24px;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
        }
        .header {
            font-size: 18px;
            font-weight: bold;
            color: #1035A8;
            margin-bottom: 16px;
        }
        .content {
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
        }
        .footer {
            margin-top: 24px;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            {{ $titleText }}
        </div>
        <div class="content">
            {!! nl2br(e($messageText)) !!}
        </div>
        <div class="footer">
            Cet e-mail vous a été envoyé par l'administration de Go Gorée.
        </div>
    </div>
</body>
</html>
