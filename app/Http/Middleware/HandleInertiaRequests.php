<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            // Running timers tick from the server's clock, not the viewer's (which may be off).
            'serverNow' => fn () => now()->utc()->format(DATE_RFC3339_EXTENDED),
        ];
    }
}
