<?php

namespace App\Http\Responses;

use Filament\Http\Responses\LoginResponse as BaseLoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse extends BaseLoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();

        if ($user->role === 'a') {
            return redirect()->to('/admin');
        }

        if ($user->role === 'collector') {
            return redirect()->to('/collector');
        }

        return parent::toResponse($request);
    }
}
