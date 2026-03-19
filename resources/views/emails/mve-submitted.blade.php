<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVE Enviada — Acuse de Recibo</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f4f8; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="580" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #001a4d 0%, #003399 100%); padding: 36px 30px; text-align: center;">
                            <img src="cid:logo_file" alt="E&I" style="max-width: 110px; height: auto; margin-bottom: 14px;" />
                            <h1 style="color: #ffffff; font-size: 22px; margin: 0; font-weight: 700;">
                                Manifestación de Valor Electrónica
                            </h1>
                            <p style="color: #93c5fd; font-size: 13px; margin: 6px 0 0 0;">
                                Enviada correctamente a VUCEM
                            </p>
                        </td>
                    </tr>

                    {{-- CUERPO --}}
                    <tr>
                        <td style="padding: 32px 36px 28px 36px;">

                            <p style="color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 24px 0;">
                                Hola <strong style="color: #0f172a;">{{ $user->full_name }}</strong>,
                                la siguiente Manifestación de Valor fue enviada exitosamente:
                            </p>

                            {{-- TARJETA PRINCIPAL --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #eff6ff; border-radius: 12px; border: 1px solid #bfdbfe; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 20px 24px;">

                                        {{-- Folio MVE --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14px;">
                                            <tr>
                                                <td width="150" style="color: #1e40af; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 0; vertical-align: middle;">
                                                    Folio MVE
                                                </td>
                                                <td style="padding: 10px 14px; background: #ffffff; border-radius: 8px; border: 1px solid #bfdbfe;">
                                                    <code style="color: #003399; font-size: 15px; font-weight: 700; font-family: 'Courier New', monospace; letter-spacing: 0.5px;">{{ $folioMostrar }}</code>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Estatus --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 14px;">
                                            <tr>
                                                <td width="150" style="color: #1e40af; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 0; vertical-align: middle;">
                                                    Estatus
                                                </td>
                                                <td style="padding: 10px 14px; background: #ffffff; border-radius: 8px; border: 1px solid #bfdbfe;">
                                                    <span style="display: inline-block; background: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        {{ $acuse->status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>

                                        {{-- Fecha de Envío --}}
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="150" style="color: #1e40af; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 0; vertical-align: middle;">
                                                    Fecha de Envío
                                                </td>
                                                <td style="padding: 10px 14px; background: #ffffff; border-radius: 8px; border: 1px solid #bfdbfe;">
                                                    <span style="color: #0f172a; font-size: 14px; font-weight: 600;">
                                                        {{ $acuse->fecha_envio?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>

                            {{-- NOTA XML / INSTRUCCIÓN --}}
                            @if($tieneAcuseXml && $tieneDeclaracionXml)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 0 8px 8px 0; margin-bottom: 0;">
                                    <tr>
                                        <td style="padding: 14px 18px;">
                                            <p style="color: #166534; font-size: 13px; margin: 0 0 6px 0; font-weight: 600;">📎 Se adjuntan dos archivos XML:</p>
                                            <ul style="color: #166534; font-size: 13px; margin: 0; padding-left: 18px; line-height: 1.8;">
                                                <li><strong>acuse_mve_*.xml</strong> &mdash; Acuse firmado por VUCEM (equivalente al acuse PDF).</li>
                                                <li><strong>declaracion_mve_*.xml</strong> &mdash; Datos completos de lo declarado.</li>
                                            </ul>
                                        </td>
                                    </tr>
                                </table>
                            @elseif($tieneAcuseXml)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 0 8px 8px 0; margin-bottom: 0;">
                                    <tr>
                                        <td style="padding: 14px 18px;">
                                            <p style="color: #166534; font-size: 13px; margin: 0; font-weight: 600;">📎 Se adjunta el acuse XML firmado por VUCEM.</p>
                                        </td>
                                    </tr>
                                </table>
                            @else
                                {{-- Sin XMLs: mostrar folio y enlace a consultas --}}
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; margin-bottom: 0;">
                                    <tr>
                                        <td style="padding: 16px 18px;">
                                            <p style="color: #92400e; font-size: 13px; margin: 0 0 10px 0; font-weight: 600;">
                                                ℹ️ La MVE fue registrada exitosamente en VUCEM con el folio <strong>{{ $folioMostrar }}</strong>.
                                            </p>
                                            <p style="color: #78350f; font-size: 13px; margin: 0 0 12px 0; line-height: 1.6;">
                                                Para obtener el XML del acuse firmado y la declaración completa, consulte la MVE desde el sistema. El XML estará disponible una vez que VUCEM procese su solicitud.
                                            </p>
                                            <a href="{{ $urlConsultas }}"
                                               style="display: inline-block; background: #003399; color: #ffffff; font-size: 13px; font-weight: 700; padding: 10px 22px; border-radius: 8px; text-decoration: none;">
                                                Consultar y descargar XML &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background: #f8fafc; padding: 20px 36px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="color: #94a3b8; font-size: 12px; line-height: 1.6; margin: 0;">
                                Correo generado automáticamente por el sistema <strong>MVE</strong>.
                            </p>
                            <p style="color: #cbd5e1; font-size: 11px; margin: 8px 0 0 0;">
                                &copy; {{ date('Y') }} Estrategia e Innovación
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
