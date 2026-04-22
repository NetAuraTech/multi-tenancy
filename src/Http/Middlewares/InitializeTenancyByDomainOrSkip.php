<?php

namespace Netauratech\MultiTenancy\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;

class InitializeTenancyByDomainOrSkip
{
    /**
     * @throws TenantCouldNotBeIdentifiedById
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {
        $hostname = $request->getHost();
        $centralDomains = config('tenancy.central_domains');

        if (in_array($hostname, $centralDomains)) {
            return $next($request);
        }

        $tenantId = Cache::remember("tenant_id_for_{$hostname}", 3600, function () use ($request, $next, $hostname) {
            $tenant = app(DomainTenantResolver::class)->resolve($hostname);

            return $tenant ? $tenant->getInternal('id') : null;
        });

        if($tenantId) {
            tenancy()->initialize($tenantId);
            return $next($request);
        }

        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}