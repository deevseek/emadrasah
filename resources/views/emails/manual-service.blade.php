<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><title>{{ $outgoingEmail->subject }}</title></head>
<body style="margin:0;background:#f1f5f9;color:#1e293b;font-family:Arial,sans-serif;line-height:1.6">
<div style="max-width:680px;margin:24px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
    <div style="background:#064e3b;padding:18px 24px;color:#ffffff;font-weight:bold">{{ config('mail.from.name') }}</div>
    <div style="padding:24px">{!! nl2br(e($outgoingEmail->body)) !!}</div>
    <div style="padding:14px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px">
        Email resmi ini dikirim melalui layanan administrasi {{ config('mail.from.name') }}.
    </div>
</div>
</body>
</html>
