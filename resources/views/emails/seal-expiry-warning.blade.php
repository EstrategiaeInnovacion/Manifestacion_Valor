<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sello VUCEM por Vencer — MVE</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f4f8; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #92400e 0%, #b45309 50%, #92400e 100%); padding: 40px 30px; text-align: center;">
                            <img src="cid:logo_file" alt="E&I" style="max-width: 120px; height: auto; margin-bottom: 16px;" />
                            <h1 style="color: #ffffff; font-size: 26px; margin: 0; font-weight: 700; letter-spacing: 1px;">
                                Sello VUCEM por Vencer
                            </h1>
                            <p style="color: #fde68a; font-size: 14px; margin: 8px 0 0 0;">
                                Tu certificado digital vencerá pronto
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
                                Te informamos que el sello digital (certificado <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 14px;">.cer</code>) del siguiente solicitante está próximo a vencer:
                            </p>

                            {{-- TARJETA DEL SOLICITANTE --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-radius: 12px; border: 1px solid #fcd34d; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <p style="color: #92400e; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; margin: 0 0 16px 0;">
                                            ⚠️ Certificado por Vencer
                                        </p>

                                        {{-- Solicitante --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="160" style="color: #92400e; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Solicitante:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #fcd34d;">
                                                    <span style="color: #0f172a; font-size: 14px; font-weight: 700;">{{ $applicant->business_name }}</span>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- RFC --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="160" style="color: #92400e; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    RFC:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #fcd34d;">
                                                    <code style="color: #1e3a5f; font-size: 14px; font-weight: 700; font-family: 'Courier New', monospace;">{{ $applicant->applicant_rfc }}</code>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Fecha de vencimiento --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                                            <tr>
                                                <td width="160" style="color: #92400e; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Vence el:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #fcd34d;">
                                                    <span style="color: #dc2626; font-size: 15px; font-weight: 700;">{{ $applicant->vucem_cert_vigencia->format('d/m/Y') }}</span>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Días restantes --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="160" style="color: #92400e; font-size: 13px; font-weight: 600; padding: 8px 0; vertical-align: top;">
                                                    Días restantes:
                                                </td>
                                                <td style="padding: 8px 12px; background-color: #ffffff; border-radius: 8px; border: 1px solid #fcd34d;">
                                                    <span style="color: #dc2626; font-size: 15px; font-weight: 700;">{{ $daysLeft }} día(s)</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- ALERTA DE ACCIÓN --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef2f2; border-radius: 12px; border-left: 4px solid #ef4444; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="color: #991b1b; font-size: 14px; line-height: 1.6; margin: 0; font-weight: 600;">
                                            🔑 Acción Requerida
                                        </p>
                                        <p style="color: #b91c1c; font-size: 13px; line-height: 1.6; margin: 8px 0 0 0;">
                                            Para evitar interrupciones en las operaciones de Manifestación de Valor Electrónica, renueva el sello digital ante el SAT y actualiza el certificado en el sistema antes de la fecha de vencimiento.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #64748b; font-size: 13px; line-height: 1.6; margin: 0 0 8px 0;">
                                Una vez que tengas el nuevo certificado, actualízalo en el sistema accediendo al perfil del solicitante → sección <strong>Sellos VUCEM</strong>.
                            </p>
                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 36px; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; font-size: 12px; line-height: 1.6; margin: 0; text-align: center;">
                                Este correo fue generado automáticamente por el sistema <strong>MVE</strong>.<br>
                                Si ya realizaste la renovación, puedes ignorar este mensaje.
                            </p>
                            <p style="color: #cbd5e1; font-size: 11px; margin: 12px 0 0 0; text-align: center;">
                                &copy; {{ date('Y') }} Estrategia e Innovación — Todos los derechos reservados
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
