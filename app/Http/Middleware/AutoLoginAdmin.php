<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Producto de propiedad exclusiva de LA COMITENTE (Edutalento) - Clausula Octava del contrato.
 *
 * Sistema de uso privado y unipersonal: autentica automaticamente sin
 * pantalla de login, segun lo solicitado para este entorno.
 */
class AutoLoginAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            Auth::login(User::query()->firstOrFail());
        }

        return $next($request);
    }
}
