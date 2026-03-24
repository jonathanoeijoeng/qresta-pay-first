<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTableSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Jika tidak ada data meja di session, arahkan ke halaman error
        if (!session()->has('customer_table_id')) {
            return redirect()->route('invalid-access');
        }

        return $next($request);
    }
}
