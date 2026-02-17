<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licencia Asignada — FILE</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f4f8; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%); padding: 40px 30px; text-align: center;">
                            <img src="cid:logo_file" alt="FILE" style="max-width: 120px; height: auto; margin-bottom: 16px;" />
                            <h1 style="color: #ffffff; font-size: 26px; margin: 0; font-weight: 700; letter-spacing: 1px;">
                                Licencia Activada
                            </h1>
                            <p style="color: #94a3b8; font-size: 14px; margin: 8px 0 0 0;">
                                Tu licencia de FILE ha sido asignada exitosamente
                            </p>
                        </td>
                    </tr>

                    {{-- CUERPO --}}
                    <tr>
                        <td style="padding: 36px 36px 20px 36px;">
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hola <strong style="color: #0f172a;">{{ $admin->full_name }}</strong>,
                            </p>

                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                Se te ha asignado una nueva licencia en el sistema <strong style="color: #1e3a5f;">FILE</strong>.
                                A continuación encontrarás los detalles:
                            </p>

                            {{-- TARJETA DE LICENCIA --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 12px; border: 1px solid #6ee7b7; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="color: #065f46; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; margin: 0 0 16px 0;">
                                            🔑 Detalles de la Licencia
                                        </p>

                                        {{-- Clave --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="140" style="color: #047857; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    N° de Licencia:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #a7f3d0;">
                                                    <code style="color: #059669; font-size: 15px; font-weight: 700; font-family: 'Courier New', monospace; letter-spacing: 1px;">{{ $license->license_key }}</code>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Duración --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="140" style="color: #047857; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Duración:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #a7f3d0;">
                                                    <span style="color: #0f172a; font-size: 14px; font-weight: 600;">{{ $durationLabel }}</span>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Fecha de inicio --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="140" style="color: #047857; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Fecha de inicio:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #a7f3d0;">
                                                    <span style="color: #0f172a; font-size: 14px;">{{ $license->starts_at->format('d/m/Y H:i') }}</span>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Fecha de vencimiento --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="140" style="color: #047857; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Vence el:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #a7f3d0;">
                                                    <span style="color: #0f172a; font-size: 14px; font-weight: 600;">{{ $license->expires_at->format('d/m/Y H:i') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- INFO --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border-radius: 12px; border-left: 4px solid #3b82f6; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="color: #1e40af; font-size: 14px; line-height: 1.6; margin: 0;">
                                            ℹ️ Con esta licencia activa, tú y los usuarios que hayas creado podrán acceder a todas las funciones del sistema FILE.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- BOTÓN --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.url') }}/login" style="display: inline-block; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 40px; border-radius: 10px; letter-spacing: 0.5px;">
                                            Ir al sistema FILE
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 36px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; font-size: 12px; line-height: 1.6; margin: 0; text-align: center;">
                                Este correo fue generado automáticamente por el sistema <strong>FILE</strong>.<br>
                                Si tienes dudas, contacta al administrador de tu organización.
                            </p>
                            <p style="color: #cbd5e1; font-size: 11px; margin: 12px 0 0 0; text-align: center;">
                                &copy; {{ date('Y') }} FILE — Estrategia e Innovación
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
