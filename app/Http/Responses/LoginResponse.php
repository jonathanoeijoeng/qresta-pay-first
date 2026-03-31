<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        // Logika redirect sesuai role
        $url = match ($user->role) {
            'kitchen'      => route('kitchen.index'),
            'cashier'      => route('cashier.index'),
            'admin_cabang' => route('dashboard'),
            default        => config('fortify.home'),
        };

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended($url);
    }
}