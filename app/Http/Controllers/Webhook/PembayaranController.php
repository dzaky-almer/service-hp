<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\PembayaranService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint webhook gateway (fitur 42). Tanpa sesi & tanpa CSRF; keaslian
 * dijamin HMAC-SHA256 atas body mentah (SECURITY.md §5). Selalu 401 bila
 * tanda tangan tidak cocok, dan seluruh payload tetap dicatat.
 */
class PembayaranController extends Controller
{
    public function __invoke(Request $request, PembayaranService $pembayaran): JsonResponse
    {
        $log = $pembayaran->prosesWebhook(
            $request->getContent(),
            $request->header('X-Signature'),
        );

        if (! $log->tanda_tangan_valid) {
            return response()->json(['message' => 'Tanda tangan tidak valid.'], 401);
        }

        return response()->json(['ok' => true, 'id_log' => $log->id_log]);
    }
}
