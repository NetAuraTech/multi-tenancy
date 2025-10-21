<?php

namespace Netauratech\MultiTenancy\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class InitializeTenancyByDomainOrSkip
{
    public function handle(Request $request, Closure $next)
    {
        $centralDomains = config('tenancy.central_domains');

        if (in_array($request->getHost(), $centralDomains)) {
            return $next($request);
        }

        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}