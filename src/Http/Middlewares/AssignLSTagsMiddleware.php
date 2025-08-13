<?php

namespace Netauratech\MultiTenancy\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignLSTagsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (tenant()) {
            $tenantId = tenant('id');
            $response->headers->set('X-LiteSpeed-Tag', 'tenant_' . $tenantId);
        }

        return $response;
    }
}
