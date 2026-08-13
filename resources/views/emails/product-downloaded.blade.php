<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0a4b76; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { border: 1px solid #e5e7eb; border-top: 0; padding: 24px; border-radius: 0 0 8px 8px; }
        .detail { background: #f3f4f6; border-radius: 8px; padding: 16px; margin: 18px 0; }
        .btn { display: inline-block; background: #0a4b76; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .footer { text-align: center; margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 22px;">Your product was downloaded</h1>
    </div>
    <div class="content">
        <p>Hi {{ $product->user?->name ?? 'Creator' }},</p>
        <p><strong>{{ $downloader->name }}</strong> downloaded your product.</p>

        <div class="detail">
            <strong>{{ $product->title }}</strong><br>
            Downloaded {{ $download->created_at?->format('M j, Y \a\t g:i A') }}
        </div>

        <a href="{{ url('/products/'.$product->slug) }}" class="btn">View product</a>
    </div>
    <div class="footer">
        <p>This activity notification was sent by {{ config('app.name') }}.</p>
    </div>
</body>
</html>
