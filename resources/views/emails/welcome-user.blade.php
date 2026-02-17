<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a FILE</title>
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
                                Bienvenido a FILE
                            </h1>
                            <p style="color: #94a3b8; font-size: 14px; margin: 8px 0 0 0;">
                                Sistema de Manifestación de Valor Electrónica
                            </p>
                        </td>
                    </tr>

                    {{-- CUERPO PRINCIPAL --}}
                    <tr>
                        <td style="padding: 36px 36px 20px 36px;">
                            {{-- Mensaje de bienvenida --}}
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hola <strong style="color: #0f172a;">{{ $newUser->full_name }}</strong>,
                            </p>

                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                El usuario <strong style="color: #1e3a5f;">{{ $createdBy->full_name }}</strong> te ha registrado en el sistema <strong style="color: #1e3a5f;">FILE</strong>.
                                A continuación encontrarás tus credenciales de acceso:
                            </p>

                            {{-- TARJETA DE CREDENCIALES --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; margin: 0 0 16px 0;">
                                            🔐 Credenciales de Acceso
                                        </p>

                                        {{-- Correo --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="120" style="color: #64748b; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Correo:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                                                    <span style="color: #0f172a; font-size: 14px; font-weight: 600;">{{ $newUser->email }}</span>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Contraseña --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="120" style="color: #64748b; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Contraseña:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                                                    <code style="color: #dc2626; font-size: 15px; font-weight: 700; font-family: 'Courier New', monospace; letter-spacing: 1px;">{{ $plainPassword }}</code>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Licencia --}}
                                        @if($licenseKey)
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="120" style="color: #64748b; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    N° Licencia:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                                                    <span style="color: #059669; font-size: 14px; font-weight: 600; font-family: 'Courier New', monospace;">{{ $licenseKey }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            {{-- ADVERTENCIA DE CONTRASEÑA TEMPORAL --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef3c7; border-radius: 12px; border-left: 4px solid #f59e0b; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="color: #92400e; font-size: 14px; line-height: 1.6; margin: 0; font-weight: 600;">
                                            ⚠️ Contraseña Temporal
                                        </p>
                                        <p style="color: #92400e; font-size: 13px; line-height: 1.6; margin: 8px 0 0 0;">
                                            Esta contraseña es temporal. Te recomendamos cambiarla desde tu <strong>Perfil</strong> una vez que inicies sesión para no perderla y mantener tu cuenta segura.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- BOTÓN DE ACCESO (opcional) --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.url') }}/login" style="display: inline-block; background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 40px; border-radius: 10px; letter-spacing: 0.5px;">
                                            Iniciar Sesión en FILE
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
                                Si no solicitaste esta cuenta, por favor contacta al administrador de tu organización.
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
