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
        <h1 style="margin: 0; font-size: 22px;">New Project Available</h1>
    </div>
    <div class="content">
        <h2 style="margin-top: 0;">{{ $product->title }}</h2>

        @if($product->user)
            <p class="meta">By {{ $product->user->name }}</p>
        @endif

        @if($product->faculty)
            <p class="meta">Faculty: {{ $product->faculty->name }}</p>
        @endif

        @if($product->abstract)
            <p>{{ Str::limit(strip_tags($product->abstract), 200) }}</p>
        @endif

        <a href="{{ url('/products/' . $product->slug) }}" class="btn">View Project</a>
    </div>
    <div class="footer">
        <p>You received this email because you are a registered user on {{ config('app.name') }}.</p>
    </div>
</body>
</html>
