<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download {{ $product->title }} - {{ config('app.name') }}</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 40px 20px; background: #f9fafb; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #0a4b76; font-size: 1.5rem; }
        p { color: #6b7280; margin: 16px 0; }
        .download-link { display: inline-block; margin-top: 20px; padding: 12px 32px; background: #0a4b76; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .download-link:hover { background: #1f90bb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Downloading: {{ $product->title }}</h1>
        <p>Your download will begin shortly.</p>

        {{-- Smart Link Code injected by admin --}}
        {!! $smartLinkCode !!}

        <a href="{{ $downloadUrl }}" class="download-link">Click here if download doesn't start</a>
    </div>

    <script>
        // Auto-redirect to actual file after a delay (smart link needs to load first)
        setTimeout(function() {
            window.location.href = "{{ $downloadUrl }}";
        }, 5000);
    </script>
</body>
</html>
