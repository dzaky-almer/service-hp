<?php

namespace App\Http\Controllers;

use App\Models\FotoServis;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penyaji foto servis. Berkas tersimpan di disk privat; setiap permintaan
 * diperiksa lewat `ServisPolicy` (SECURITY.md §4).
 */
class FotoServisController extends Controller
{
    public function show(FotoServis $foto): Response
    {
        $this->authorize('lihat', $foto->servis);

        abort_unless(Storage::disk('local')->exists($foto->path_file), 404);

        return Storage::disk('local')->response($foto->path_file);
    }
}
