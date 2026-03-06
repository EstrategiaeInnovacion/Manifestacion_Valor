<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,26,77,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#001a4d 0%,#003399 100%); padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:22px; font-weight:800; letter-spacing:1px;">
                                🔐 Recuperación de Contraseña
                            </h1>
                            <p style="margin:8px 0 0; color:rgba(255,255,255,0.7); font-size:12px; text-transform:uppercase; letter-spacing:2px;">
                                FILE — Manifestación de Valor
                            </p>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding:40px 40px 32px;">
                            <p style="margin:0 0 12px; color:#334155; font-size:15px; line-height:1.7;">
                                Hola <strong style="color:#001a4d;">{{ $userName }}</strong>,
                            </p>
                            <p style="margin:0 0 32px; color:#334155; font-size:15px; line-height:1.7;">
                                Recibimos una solicitud para recuperar tu contraseña. Usa el siguiente código de verificación:
                            </p>

                            {{-- Code block --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td align="center">
                                        <div style="background:#eef2ff; border:2px dashed #003399; border-radius:16px; padding:28px 40px; display:inline-block;">
                                            <p style="margin:0 0 10px; font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:2px; font-weight:700;">Código de Verificación</p>
                                            <p style="margin:0; font-size:52px; font-weight:900; color:#001a4d; letter-spacing:14px; font-family:monospace;">{{ $code }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div style="background:#fff7ed; border-left:4px solid #f97316; border-radius:0 8px 8px 0; padding:14px 18px; margin-bottom:24px;">
                                <p style="margin:0; color:#92400e; font-size:13px; font-weight:600;">
                                    ⏱ Este código es válido únicamente por <strong>5 minutos</strong> a partir de este momento.
                                </p>
                            </div>

                            <p style="margin:0; color:#64748b; font-size:13px; line-height:1.7;">
                                Si no solicitaste este cambio, puedes ignorar este mensaje. Tu contraseña actual seguirá siendo la misma.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc; padding:20px 40px; border-top:1px solid #eef2f6; text-align:center;">
                            <p style="margin:0; color:#94a3b8; font-size:11px;">
                                No compartas este código con nadie. El equipo de soporte nunca te lo solicitará.
                            </p>
                            <p style="margin:8px 0 0; color:#cbd5e1; font-size:10px; text-transform:uppercase; letter-spacing:2px;">
                                Estrategia e Innovación — Comercio Exterior
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
