<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0a4b76; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { border: 1px solid #e5e7eb; border-top: 0; padding: 24px; border-radius: 0 0 8px 8px; }
        .btn { display: inline-block; background: #0a4b76; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 16px; }
        .meta { color: #6b7280; font-size: 14px; margin-top: 8px; }
        .footer { text-align: center; margin-top: 24px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 22px;">New Message Received</h1>
    </div>
    <div class="content">
        <p>Hi {{ $recipient->name }},</p>

        <p>You have received a new message from <strong>{{ $message->sender_name }}</strong> regarding the project:</p>

        <p style="font-weight: bold; font-size: 16px;">{{ $message->product->title ?? 'a project' }}</p>

        <p><strong>Subject:</strong> {{ $message->subject }}</p>

        <p>Please log in to your dashboard to read and respond to this message.</p>

        <a href="{{ url('/dashboard/messages') }}" class="btn">View Messages</a>

        <p class="meta" style="margin-top: 20px;">Do not reply directly to this email. Please use the dashboard to respond.</p>
    </div>
    <div class="footer">
        <p>You received this email because someone sent you a message on {{ config('app.name') }}.</p>
    </div>
</body>
</html>
