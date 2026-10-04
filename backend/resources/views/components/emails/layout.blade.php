<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2430;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#0f172a;padding:20px 32px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:14px;line-height:1.6;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px;background:#f4f5f7;font-size:12px;color:#6b7280;">
                            This is an automated message from {{ config('app.name') }}. Please do not reply directly to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
