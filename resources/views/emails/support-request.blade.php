<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Soporte — FILE</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,26,77,0.08);">

                    {{-- Header con logo --}}
                    <tr>
                        <td style="background:#ffffff; padding:32px 40px 24px; text-align:center; border-bottom:4px solid #003399;">
                            <img src="cid:logo_file" alt="FILE" style="max-width:90px; height:auto; margin-bottom:14px; display:block; margin-left:auto; margin-right:auto;">
                            <h1 style="margin:0; color:#001a4d; font-size:20px; font-weight:800; letter-spacing:1px;">
                                🎫 Nuevo Ticket de Soporte
                            </h1>
                            <p style="margin:6px 0 0; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:2px;">
                                FILE — Manifestación de Valor
                            </p>
                        </td>
                    </tr>

                    {{-- Category Badge --}}
                    <tr>
                        <td style="padding:24px 40px 0;">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:#eef2ff; color:#003399; font-size:11px; font-weight:700; padding:6px 14px; border-radius:20px; text-transform:uppercase; letter-spacing:1px;">
                                        {{ $category }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding:20px 40px 32px;">

                            {{-- Subject --}}
                            <h2 style="margin:0 0 24px; color:#001a4d; font-size:20px; font-weight:700; border-bottom:2px solid #eef2f6; padding-bottom:16px;">
                                {{ $subject }}
                            </h2>

                            {{-- User info --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border-radius:12px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 4px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1.5px; font-weight:700;">Enviado por</p>
                                        <p style="margin:0 0 2px; font-size:16px; color:#001a4d; font-weight:700;">{{ $userName }}</p>
                                        <p style="margin:0; font-size:14px; color:#003399;">{{ $userEmail }}</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Description --}}
                            <p style="margin:0 0 8px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1.5px; font-weight:700;">Descripción</p>
                            <div style="background:#fafbfc; border-left:4px solid #003399; padding:16px 20px; border-radius:0 8px 8px 0; margin-bottom:24px;">
                                <p style="margin:0; color:#334155; font-size:14px; line-height:1.7; white-space:pre-wrap;">{{ $description }}</p>
                            </div>

                            {{-- Capturas adjuntas --}}
                            @if ($screenshotCount > 0)
                                <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; margin-bottom:8px;">
                                    <tr>
                                        <td style="padding:12px 18px;">
                                            <p style="margin:0; font-size:13px; color:#92400e; font-weight:600;">
                                                📎 {{ $screenshotCount }} {{ $screenshotCount === 1 ? 'captura adjunta' : 'capturas adjuntas' }} — revísalas en los archivos adjuntos de este correo.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc; padding:20px 40px; border-top:1px solid #eef2f6; text-align:center;">
                            <p style="margin:0; color:#94a3b8; font-size:11px;">
                                Responde directamente a este correo para contactar al usuario.
                            </p>
                            <p style="margin:8px 0 0; color:#cbd5e1; font-size:10px; text-transform:uppercase; letter-spacing:2px;">
                                &copy; {{ date('Y') }} Estrategia e Innovación — Comercio Exterior
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
