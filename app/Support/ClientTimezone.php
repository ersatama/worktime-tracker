<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The viewer's time zone: the page stores the browser's zone in the "tz" cookie,
 * so "today" and the report period mean the viewer's days, not the server's.
 */
class ClientTimezone
{
    public const COOKIE = 'tz';

    public static function from(Request $request): string
    {
        $tz = $request->cookie(self::COOKIE);

        return is_string($tz) && in_array($tz, timezone_identifiers_list(), true)
            ? $tz
            : config('app.timezone');
    }
}
