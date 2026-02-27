<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta a tu ticket — FILE</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,26,77,0.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#ffffff; padding:32px 40px 24px; text-align:center; border-bottom:4px solid #003399;">
                            <img src="cid:logo_file" alt="FILE" style="max-width:90px; height:auto; margin-bottom:14px; display:block; margin-left:auto; margin-right:auto;">
                            <h1 style="margin:0; color:#001a4d; font-size:20px; font-weight:800; letter-spacing:1px;">
                                💬 Respuesta de Soporte Técnico
                            </h1>
                            <p style="margin:6px 0 0; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:2px;">
                                FILE — Manifestación de Valor
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px 40px 32px;">

                            <p style="margin:0 0 20px; color:#334155; font-size:15px; line-height:1.6;">
                                Hola <strong style="color:#001a4d;">{{ $ticketOwner->full_name }}</strong>,
                            </p>
                            <p style="margin:0 0 24px; color:#334155; font-size:14px; line-height:1.6;">
                                El equipo de soporte ha respondido a tu ticket. A continuación encontrarás la respuesta:
                            </p>

                            {{-- Ticket info --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border-radius:10px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:14px 20px;">
                                        <p style="margin:0 0 4px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1.5px; font-weight:700;">Ticket</p>
                                        <p style="margin:0 0 2px; font-size:15px; color:#001a4d; font-weight:700;">#{{ $ticket->id }} — {{ $ticket->subject }}</p>
                                        <p style="margin:0; font-size:12px; color:#003399; font-weight:600;">{{ $ticket->category }}</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Response --}}
                            <p style="margin:0 0 8px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1.5px; font-weight:700;">
                                Respuesta de {{ $senderName }}
                            </p>
                            <div style="background:#f0f4ff; border-left:4px solid #003399; padding:16px 20px; border-radius:0 8px 8px 0; margin-bottom:24px;">
                                <p style="margin:0; color:#334155; font-size:14px; line-height:1.7; white-space:pre-wrap;">{{ $responseBody }}</p>
                            </div>

                            {{-- Status badge --}}
                            @php
                                $statusLabel = match($ticket->status) {
                                    'open'        => 'Abierto',
                                    'in_progress' => 'En Proceso',
                                    'closed'      => 'Cerrado',
                                    default       => $ticket->status
                                };
                                $statusColor = match($ticket->status) {
                                    'open'        => '#f59e0b',
                                    'in_progress' => '#3b82f6',
                                    'closed'      => '#64748b',
                                    default       => '#64748b'
                                };
                            @endphp
                            <p style="margin:0 0 8px; font-size:12px; color:#64748b;">
                                Estatus actual del ticket:
                                <strong style="color:{{ $statusColor }};">{{ $statusLabel }}</strong>
                            </p>

                            {{-- CTA --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}"
                                           style="display:inline-block; background:#001a4d; color:#ffffff; text-decoration:none; font-size:14px; font-weight:700; padding:13px 36px; border-radius:10px; letter-spacing:0.5px;">
                                            Ver Ticket Completo
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f8fafc; padding:20px 40px; border-top:1px solid #eef2f6; text-align:center;">
                            <p style="margin:0; color:#94a3b8; font-size:11px;">
                                Este correo es una notificación automática. Para responder, ingresa al sistema.
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
