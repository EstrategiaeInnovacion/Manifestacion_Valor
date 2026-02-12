<?php

namespace App\Http\Controllers;

use App\Mail\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'subject'  => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        $user = auth()->user();
        $supportEmail = config('mail.support_address', 'soporte@tuempresa.com');

        try {
            Mail::to($supportEmail)->send(new SupportRequest(
                userName: $user->full_name,
                userEmail: $user->email,
                category: $validated['category'],
                subject: $validated['subject'],
                description: $validated['description'],
            ));

            Log::channel('audit')->info('Ticket de soporte enviado', [
                'user'     => $user->email,
                'category' => $validated['category'],
                'subject'  => $validated['subject'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tu solicitud de soporte fue enviada correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al enviar ticket de soporte', [
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