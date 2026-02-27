<?php

namespace App\Http\Controllers;

use App\Mail\TicketResponseMail;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // Listado de tickets
    // ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user   = auth()->user();
        $status = $request->query('status');

        $query = SupportTicket::with('user')->latest();

        if ($user->role === 'SuperAdmin') {
            // SuperAdmin ve todos los tickets excepto cancelados
            $query->where('status', '!=', 'cancelled');
            if ($status) {
                $query->where('status', $status);
            }
        } else {
            // Admin/Usuario solo ven sus propios tickets
            $query->where('user_id', $user->id);
        }

        $tickets = $query->paginate(20)->withQueryString();

        return view('tickets.index', compact('tickets', 'status'));
    }

    // ──────────────────────────────────────────────────────────
    // Detalle del ticket
    // ──────────────────────────────────────────────────────────

    public function show(SupportTicket $ticket)
    {
        $user = auth()->user();

        // Solo el dueño o el SuperAdmin pueden ver el ticket
        if ($user->role !== 'SuperAdmin' && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $ticket->load(['user', 'messages.sender', 'messages.attachments']);

        return view('tickets.show', compact('ticket'));
    }

    // ──────────────────────────────────────────────────────────
    // Responder ticket (solo SuperAdmin)
    // ──────────────────────────────────────────────────────────

    public function respond(Request $request, SupportTicket $ticket)
    {
        $user = auth()->user();

        if ($user->role !== 'SuperAdmin') {
            abort(403);
        }

        $request->validate([
            'body'          => 'required|string|max:5000',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        // Crear mensaje de respuesta
        $message = SupportTicketMessage::create([
            'ticket_id'          => $ticket->id,
            'sender_id'          => $user->id,
            'body'               => $request->input('body'),
            'is_support_response'=> true,
        ]);

        // Guardar adjuntos si los hay
        foreach ($request->file('attachments', []) as $file) {
            $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs("support-attachments/ticket-{$ticket->id}", $storedName, 'local');

            SupportTicketAttachment::create([
                'message_id'    => $message->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $storedName,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        // Si viene con cambio de estatus, actualizarlo
        if ($request->filled('status')) {
            $ticket->update(['status' => $request->input('status')]);
        }

        // Enviar correo de notificación al usuario que creó el ticket
        try {
            $ticket->load('user');
            (new TicketResponseMail(
                ticketOwner:  $ticket->user,
                ticket:       $ticket,
                responseBody: $request->input('body'),
                senderName:   'Soporte Técnico',
            ))->send();
        } catch (\Exception $e) {
            Log::error('TicketController: Error al notificar respuesta por correo', [
                'ticket_id' => $ticket->id,
                'error'     => $e->getMessage(),
            ]);
        }

        Log::channel('audit')->info('Respuesta enviada a ticket', [
            'ticket_id'  => $ticket->id,
            'responder'  => $user->email,
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Respuesta enviada correctamente.');
    }

    // ──────────────────────────────────────────────────────────
    // Actualizar estatus (solo SuperAdmin)
    // ──────────────────────────────────────────────────────────

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        if (auth()->user()->role !== 'SuperAdmin') {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,closed',
        ]);

        $ticket->update(['status' => $request->input('status')]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Estatus actualizado.');
    }

    // ──────────────────────────────────────────────────────────
    // Cancelar ticket (solo el dueño)
    // ──────────────────────────────────────────────────────────

    public function cancel(SupportTicket $ticket)
    {
        $user = auth()->user();

        if (! $ticket->canBeCancelledBy($user)) {
            abort(403);
        }

        $ticket->update(['status' => 'cancelled']);

        Log::channel('audit')->info('Ticket cancelado por el usuario', [
            'ticket_id' => $ticket->id,
            'user'      => $user->email,
        ]);

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket cancelado correctamente.');
    }

    // ──────────────────────────────────────────────────────────
    // Descargar adjunto
    // ──────────────────────────────────────────────────────────

    public function downloadAttachment(SupportTicketAttachment $attachment)
    {
        $user   = auth()->user();
        $ticket = $attachment->message->ticket;

        if ($user->role !== 'SuperAdmin' && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $path = "support-attachments/ticket-{$ticket->id}/{$attachment->stored_name}";

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, $attachment->original_name);
    }
}
