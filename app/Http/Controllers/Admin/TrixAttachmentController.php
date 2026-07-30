<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Recibe imágenes pegadas/soltadas en el editor Trix (base de conocimientos)
 * y devuelve una URL pública para incrustarlas como <img>. Evita que Trix
 * guarde las imágenes como data-URI base64 dentro del contenido.
 */
class TrixAttachmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->canAny(['kb.create', 'kb.edit']),
            403
        );

        $request->validate([
            'file' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $path = $request->file('file')->store('kb/attachments', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ]);
    }
}
