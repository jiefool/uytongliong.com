<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTreePinAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('tree_pin_access')) return $next($request);

        return redirect()->route('pin.entry');
    }
}
