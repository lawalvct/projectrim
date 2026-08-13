<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0a4b76; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { border: 1px solid #e5e7eb; border-top: 0; padding: 24px; border-radius: 0 0 8px 8px; }
        .amount { background: #ecfdf5; color: #047857; border-radius: 8px; padding: 18px; margin: 18px 0; text-align: center; font-size: 24px; font-weight: bold; }
        .btn { display: inline-block; background: #0a4b76; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .footer { text-align: center; margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 22px;">Payout approved</h1>
    </div>
    <div class="content">
        <p>Hi {{ $payout->user?->name ?? 'Seller' }},</p>
        <p>Your payout request has been approved.</p>

        <div class="amount">${{ number_format((float) $payout->amount_usd, 2) }}</div>

        <p>You can monitor its status from your seller dashboard.</p>
        <a href="{{ url('/dashboard/seller/payouts') }}" class="btn">View payout status</a>
    </div>
    <div class="footer">
        <p>This payout notification was sent by {{ config('app.name') }}.</p>
    </div>
</body>
</html>
