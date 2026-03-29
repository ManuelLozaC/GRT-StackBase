<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
        <div style="background:#ffffff;border:1px solid #dbe3f0;border-radius:18px;padding:32px;">
            <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:12px;">
                GRT StackBase
            </div>
            <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;color:#0f172a;">
                {{ $title }}
            </h1>
            <p style="margin:0 0 20px;font-size:16px;line-height:1.7;color:#334155;">
                {{ $messageBody }}
            </p>

            @if (! empty($actionUrl))
                <p style="margin:0 0 24px;">
                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#10b981;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:12px;">
                        Abrir accion
                    </a>
                </p>
                <p style="margin:0 0 20px;font-size:13px;line-height:1.6;color:#64748b;">
                    Si el boton no funciona, abre este enlace: {{ $actionUrl }}
                </p>
            @endif

            <div style="padding-top:20px;border-top:1px solid #e2e8f0;font-size:13px;line-height:1.7;color:#64748b;">
                Este correo fue enviado automaticamente por la base operativa del sistema.
            </div>
        </div>
    </div>
</body>
</html>
