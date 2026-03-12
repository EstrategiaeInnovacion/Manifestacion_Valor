<?php

namespace App\Http\Controllers;

use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:10000',
        ]);

        $announcement = Announcement::create([
            'title'      => $request->title,
            'body'       => $request->body,
            'created_by' => auth()->id(),
        ]);

        // Enviar correo a todos los usuarios registrados
        $users = User::all();
        foreach ($users as $user) {
            (new AnnouncementMail($announcement, $user))->send();
        }

        return back()->with('success', 'Aviso publicado y enviado por correo a todos los usuarios.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Aviso eliminado correctamente.');
    }

    public function markRead(Announcement $announcement): JsonResponse
    {
        AnnouncementRead::firstOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id'         => auth()->id(),
            ],
            ['read_at' => now()]
        );

        return response()->json(['ok' => true]);
    }
}
