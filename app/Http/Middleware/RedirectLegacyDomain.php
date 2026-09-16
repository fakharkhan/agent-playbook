<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLegacyDomain
{
    /**
     * Redirect legacy hostnames to the canonical SEO domain (301).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        if (in_array($host, config('seo.legacy_hosts', []), true)) {
            $canonicalHost = config('seo.canonical_host');
            $target = 'https://'.$canonicalHost.$request->getRequestUri();

            return redirect()->away($target, 301);
        }

        return $next($request);
    }
}
