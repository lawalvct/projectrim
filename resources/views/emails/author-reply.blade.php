<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0a4b76; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { border: 1px solid #e5e7eb; border-top: 0; padding: 24px; border-radius: 0 0 8px 8px; }
        .btn { display: inline-block; background: #0a4b76; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 16px; }
        .reply { white-space: pre-wrap; border-left: 4px solid #0a4b76; background: #f8fafc; padding: 12px 16px; }
        .footer { text-align: center; margin-top: 24px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 22px;">You Have a New Reply</h1>
    </div>
    <div class="content">
        <p>Hi {{ $recipient->name }},</p>

        <p><strong>{{ $reply->sender_name }}</strong> replied to your message about:</p>
        <p style="font-weight: bold; font-size: 16px;">{{ $originalMessage->product->title ?? 'a project' }}</p>

        <p><strong>Subject:</strong> {{ $originalMessage->subject }}</p>
        <div class="reply">{{ $reply->body }}</div>

        <a href="{{ url('/dashboard/messages') }}" class="btn">View conversation</a>
    </div>
    <div class="footer">
        <p>You received this email because an author replied to your message on {{ config('app.name') }}.</p>
    </div>
</body>
</html>
