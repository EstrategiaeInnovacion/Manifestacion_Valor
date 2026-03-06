<?php

namespace App\Http\Controllers;

use App\Mail\SupportRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'category'      => 'required|string|max:100',
            'subject'       => 'required|string|max:255',
            'description'   => 'required|string|max:5000',
            'screenshots'   => 'nullable|array|max:5',
            'screenshots.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $user = auth()->user();

        // Convertir capturas a formato de adjunto para Microsoft Graph (email)
        $emailAttachments = [];
        foreach ($request->file('screenshots', []) as $file) {
            $emailAttachments[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name'        => $file->getClientOriginalName(),
                'contentType' => $file->getMimeType(),
                'contentBytes'=> base64_encode($file->get()),
                'isInline'    => false,
            ];
        }

        try {
            // 1. Guardar ticket en BD
            $ticket = SupportTicket::create([
                'user_id'     => $user->id,
                'category'    => $validated['category'],
                'subject'     => $validated['subject'],
                'description' => $validated['description'],
                'status'      => 'open',
            ]);

            // 2. Si hay capturas, crear un mensaje inicial para anclarlas
            if ($request->hasFile('screenshots')) {
                $initMsg = SupportTicketMessage::create([
                    'ticket_id'          => $ticket->id,
                    'sender_id'          => $user->id,
                    'body'               => $validated['description'],
                    'is_support_response'=> false,
                ]);

                foreach ($request->file('screenshots') as $file) {
                    $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $file->storeAs("support-attachments/ticket-{$ticket->id}", $storedName, 'local');

                    SupportTicketAttachment::create([
                        'message_id'    => $initMsg->id,
                        'original_name' => $file->getClientOriginalName(),
                        'stored_name'   => $storedName,
                        'mime_type'     => $file->getMimeType(),
                        'size'          => $file->getSize(),
                    ]);
                }
            }

            // 4. Enviar correo a soporte
            (new SupportRequest(
                userName:    $user->full_name,
                userEmail:   $user->email,
                category:    $validated['category'],
                subject:     $validated['subject'],
                description: $validated['description'],
                attachments: $emailAttachments,
            ))->send();

            Log::channel('audit')->info('Ticket de soporte creado', [
                'ticket_id' => $ticket->id,
                'user'      => $user->email,
                'category'  => $validated['category'],
                'subject'   => $validated['subject'],
            ]);

            return response()->json([
                'success'   => true,
                'message'   => 'Tu solicitud de soporte fue enviada correctamente.',
                'ticket_id' => $ticket->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear ticket de soporte', [
                'user'  => $user->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al enviar tu solicitud. Intenta de nuevo.',
            ], 500);
        }
    }
}
