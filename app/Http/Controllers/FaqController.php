<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaqController extends Controller
{
    public function index()
    {
        $isSuperAdmin = auth()->user()->role === 'SuperAdmin';

        $faqs = Faq::with('attachments')
            ->when(!$isSuperAdmin, fn($q) => $q->where('is_published', true))
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return view('faqs.index', compact('faqs', 'isSuperAdmin'));
    }

    public function create()
    {
        abort_unless(auth()->user()->role === 'SuperAdmin', 403);

        return view('faqs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'SuperAdmin', 403);

        $request->validate([
            'question'      => 'required|string|max:500',
            'answer'        => 'required|string|max:20000',
            'sort_order'    => 'nullable|integer|min:0',
            'attachments'   => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:jpeg,png,gif,webp,pdf|max:10240',
        ]);

        $faq = Faq::create([
            'question'     => $request->question,
            'answer'       => $request->answer,
            'sort_order'   => $request->input('sort_order', 0),
            'is_published' => $request->boolean('is_published', true),
            'created_by'   => auth()->id(),
        ]);

        $this->storeAttachments($request, $faq);

        return redirect()->route('faqs.index')->with('success', 'Pregunta creada correctamente.');
    }

    public function show(Faq $faq)
    {
        $isSuperAdmin = auth()->user()->role === 'SuperAdmin';

        if (!$faq->is_published && !$isSuperAdmin) {
            abort(404);
        }

        $faq->load('attachments');

        return view('faqs.show', compact('faq', 'isSuperAdmin'));
    }

    public function edit(Faq $faq)
    {
        abort_unless(auth()->user()->role === 'SuperAdmin', 403);

        $faq->load('attachments');

        return view('faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'SuperAdmin', 403);

        $request->validate([
            'question'      => 'required|string|max:500',
            'answer'        => 'required|string|max:20000',
            'sort_order'    => 'nullable|integer|min:0',
            'attachments'   => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:jpeg,png,gif,webp,pdf|max:10240',
        ]);

        $faq->update([
            'question'     => $request->question,
            'answer'       => $request->answer,
            'sort_order'   => $request->input('sort_order', $faq->sort_order),
            'is_published' => $request->boolean('is_published', true),
        ]);

        $this->storeAttachments($request, $faq);

        return redirect()->route('faqs.index')->with('success', 'Pregunta actualizada correctamente.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'SuperAdmin', 403);

        foreach ($faq->attachments as $attachment) {
            Storage::disk('local')->delete('faqs/' . $attachment->stored_name);
        }

        $faq->delete();

        return redirect()->route('faqs.index')->with('success', 'Pregunta eliminada correctamente.');
    }

    public function attachment(FaqAttachment $attachment): BinaryFileResponse
    {
        $path = storage_path('app/private/faqs/' . $attachment->stored_name);

        abort_unless(file_exists($path), 404, 'Archivo no encontrado.');

        return response()->file($path, [
            'Content-Type'        => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"',
        ]);
    }

    public function destroyAttachment(FaqAttachment $attachment): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'SuperAdmin', 403);

        Storage::disk('local')->delete('faqs/' . $attachment->stored_name);
        $attachment->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    protected function storeAttachments(Request $request, Faq $faq): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $ext        = $file->getClientOriginalExtension();
            $storedName = 'faq_' . $faq->id . '_' . Str::random(16) . '.' . $ext;

            $file->storeAs('faqs', $storedName, 'local');

            FaqAttachment::create([
                'faq_id'        => $faq->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $storedName,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }
    }
}
