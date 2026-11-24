<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeranMiddleware
{
    /**
     * Batasi route ke peran tertentu, mis. `peran:admin,owner`.
     */
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $aktif = $request->user()?->peran;

        if (! in_array($aktif, $peran, true)) {
            abort(403);
        }

        return $next($request);
    }
}
