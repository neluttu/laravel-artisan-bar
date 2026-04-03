<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Neluttu\ArtisanBar\ArtisanBarAuth;
use Symfony\Component\HttpFoundation\Response;

class ArtisanBarEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! ArtisanBarAuth::isEnabled()) {
            abort(404);
        }

        return $next($request);
    }
}
