<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFamilyTreeAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() || $request->session()->has('tree_pin_access')) return $next($request);

        return redirect()->route('pin.entry');
    }
}
