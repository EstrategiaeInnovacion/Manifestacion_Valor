<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aviso General - FILE</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f4f8; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    {{-- HEADER CON LOGO --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%); padding: 40px 30px; text-align: center;">
                            <img src="cid:logo_file" alt="FILE" style="max-width: 120px; height: auto; margin-bottom: 16px;" />
                            <h1 style="color: #ffffff; font-size: 28px; margin: 0; font-weight: 700; letter-spacing: 1px;">
                                Aviso General
                            </h1>
                            <p style="color: #94a3b8; font-size: 14px; margin: 8px 0 0 0;">
                                Sistema de Manifestación de Valor Electrónica
                            </p>
                        </td>
                    </tr>

                    {{-- BADGE DE AVISO --}}
                    <tr>
                        <td style="padding: 0 36px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding: 20px 0 0 0;">
                                        <span style="display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 999px; border: 1px solid #fde68a; text-transform: uppercase; letter-spacing: 1px;">
                                            📢 Comunicado Oficial
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CUERPO PRINCIPAL --}}
                    <tr>
                        <td style="padding: 20px 36px 36px 36px;">
                            <p style="color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hola <strong style="color: #0f172a;">{{ $recipient->full_name }}</strong>,
                            </p>

                            <p style="color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 24px 0;">
                                El equipo de administración de <strong style="color: #1e3a5f;">FILE</strong> tiene un aviso importante para ti:
                            </p>

                            {{-- TARJETA DEL AVISO --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-radius: 12px; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 24px 28px;">
                                        <p style="color: #78350f; font-size: 18px; font-weight: 700; margin: 0 0 14px 0; line-height: 1.4;">
                                            {{ $announcement->title }}
                                        </p>
                                        <div style="color: #451a03; font-size: 14px; line-height: 1.7; margin: 0;">
                                            {!! nl2br(e($announcement->body)) !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            {{-- FECHA --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                                <tr>
                                    <td style="padding: 14px 20px; background-color: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                                        <p style="color: #64748b; font-size: 12px; margin: 0;">
                                            📅 <strong>Fecha de publicación:</strong>
                                            {{ $announcement->created_at->format('d \d\e F \d\e Y, H:i') }} hrs
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/dashboard') }}"
                                           style="display: inline-block; background: linear-gradient(135deg, #003399 0%, #0047cc 100%); color: #ffffff; font-size: 15px; font-weight: 700; padding: 14px 36px; border-radius: 10px; text-decoration: none; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(0,51,153,0.3);">
                                            Ingresar al Sistema
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #94a3b8; font-size: 12px; text-align: center; margin: 20px 0 0 0; line-height: 1.6;">
                                Este es un mensaje automático generado por el sistema FILE.<br>
                                Por favor no respondas directamente a este correo.
                            </p>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 36px; text-align: center;">
                            <p style="color: #94a3b8; font-size: 12px; margin: 0; line-height: 1.8;">
                                © {{ date('Y') }} <strong>Estrategia e Innovación</strong> — Todos los derechos reservados.<br>
                                <span style="color: #cbd5e1;">Sistema FILE — Manifestación de Valor Electrónica</span>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
