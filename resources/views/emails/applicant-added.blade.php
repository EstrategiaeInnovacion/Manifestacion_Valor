<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Solicitante — FILE</title>
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
                                Nuevo Solicitante
                            </h1>
                            <p style="color: #94a3b8; font-size: 14px; margin: 8px 0 0 0;">
                                Se ha registrado un nuevo solicitante en FILE
                            </p>
                        </td>
                    </tr>

                    {{-- CUERPO --}}
                    <tr>
                        <td style="padding: 36px 36px 20px 36px;">
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hola <strong style="color: #0f172a;">{{ $user->full_name }}</strong>,
                            </p>

                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0;">
                                Has añadido un nuevo solicitante al sistema <strong style="color: #1e3a5f;">FILE</strong>.
                                A continuación los datos registrados:
                            </p>

                            {{-- TARJETA DEL SOLICITANTE --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; margin: 0 0 16px 0;">
                                            📋 Datos del Solicitante
                                        </p>

                                        {{-- RFC --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="140" style="color: #64748b; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    RFC:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                                                    <code style="color: #0f172a; font-size: 15px; font-weight: 700; font-family: 'Courier New', monospace; letter-spacing: 1px;">{{ $applicant->applicant_rfc }}</code>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Razón Social --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="140" style="color: #64748b; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Razón Social:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                                                    <span style="color: #0f172a; font-size: 14px; font-weight: 600;">{{ $applicant->business_name }}</span>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Correo del Solicitante --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="140" style="color: #64748b; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Correo:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                                                    <span style="color: #1e3a5f; font-size: 14px; font-weight: 600;">{{ $applicant->applicant_email }}</span>
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
                                        <p style="color: #1e40af; font-size: 13px; line-height: 1.6; margin: 0;">
                                            ℹ️ Este solicitante ya está disponible para crear Manifestaciones de Valor y operaciones VUCEM en el sistema FILE.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- BOTÓN --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.url') }}/applicants" style="display: inline-block; background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 40px; border-radius: 10px; letter-spacing: 0.5px;">
                                            Ver Solicitantes
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
                                Este correo fue generado automáticamente por el sistema <strong>FILE</strong>.
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
