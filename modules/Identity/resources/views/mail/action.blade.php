<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#eef4fb;font-family:Inter,'Segoe UI',Roboto,Arial,sans-serif;color:#1e293b;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef4fb;padding:32px 16px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
            <tr><td style="background:#0d2647;padding:20px 32px;color:#ffffff;font-size:20px;font-weight:700;">{{ $appName }}</td></tr>
            <tr><td style="padding:32px;">
                <h1 style="margin:0 0 16px;font-size:20px;color:#0d2647;">Halo, {{ $name }}</h1>
                @foreach ($intro as $line)
                    <p style="margin:0 0 16px;line-height:1.6;">{{ $line }}</p>
                @endforeach
                <p style="margin:24px 0;">
                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#f2b84b;color:#08182e;text-decoration:none;font-weight:600;padding:12px 24px;border-radius:8px;">{{ $actionText }}</a>
                </p>
                @foreach ($outro as $line)
                    <p style="margin:0 0 12px;line-height:1.6;color:#475569;font-size:14px;">{{ $line }}</p>
                @endforeach
                <p style="margin:24px 0 0;line-height:1.6;">Salam,<br>Tim {{ $appName }}</p>
            </td></tr>
            <tr><td style="padding:16px 32px;border-top:1px solid #e2e8f0;font-size:12px;color:#64748b;line-height:1.5;">
                Jika tombol tidak berfungsi, salin dan buka tautan berikut di browser Anda:<br>
                <a href="{{ $actionUrl }}" style="color:#1f5598;word-break:break-all;">{{ $actionUrl }}</a>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
