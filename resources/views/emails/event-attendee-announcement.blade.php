<!doctype html>
<html lang="en">
<body style="margin:0;background:#f4f4f5;font-family:Arial,sans-serif;color:#18181b;">
    <div style="max-width:640px;margin:0 auto;padding:24px;">
        <div style="background:#ffffff;border:1px solid #e4e4e7;border-radius:8px;padding:24px;">
            <p style="margin:0 0 8px;color:#059669;font-size:14px;">{{ $event->starts_at->format('M j, Y H:i') }} · {{ $event->venue }}</p>
            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.25;">{{ $event->name }}</h1>
            <div style="font-size:16px;line-height:1.6;white-space:pre-line;">{{ $messageBody }}</div>
        </div>
    </div>
</body>
</html>
