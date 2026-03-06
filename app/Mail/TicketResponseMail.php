<?php

namespace App\Mail;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class TicketResponseMail
{
    protected User         $ticketOwner;
    protected SupportTicket $ticket;
    protected string       $responseBody;
    protected string       $senderName;

    public function __construct(
        User          $ticketOwner,
        SupportTicket $ticket,
        string        $responseBody,
        string        $senderName,
    ) {
        $this->ticketOwner  = $ticketOwner;
        $this->ticket       = $ticket;
        $this->responseBody = $responseBody;
        $this->senderName   = $senderName;
    }

    public function send(): bool
    {
        try {
            $html = View::make('emails.ticket-response', [
                'ticketOwner'  => $this->ticketOwner,
                'ticket'       => $this->ticket,
                'responseBody' => $this->responseBody,
                'senderName'   => $this->senderName,
            ])->render();

            // Logo inline
            $allAttachments = [];
            $logoPath = public_path('Gemini_Generated_Image_bmz5e9bmz5e9bmz5-removebg-preview.png');
            if (file_exists($logoPath)) {
                $allAttachments[] = [
                    '@odata.type'  => '#microsoft.graph.fileAttachment',
                    'name'         => 'logo.png',
                    'contentType'  => 'image/png',
                    'contentBytes' => base64_encode(file_get_contents($logoPath)),
                    'contentId'    => 'logo_file',
                    'isInline'     => true,
                ];
            }

            $mailService = app(MicrosoftGraphMailService::class);

            return $mailService->sendMail(
                to:      $this->ticketOwner->email,
                subject: "[FILE Soporte] Respuesta a tu ticket: {$this->ticket->subject}",
                htmlBody: $html,
                attachments: $allAttachments,
            );
        } catch (\Exception $e) {
            Log::error('TicketResponseMail: Error al enviar notificación', [
                'ticket_id' => $this->ticket->id,
                'to'        => $this->ticketOwner->email,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }
}
