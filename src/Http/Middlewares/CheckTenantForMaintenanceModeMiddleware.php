<?php

namespace Netauratech\MultiTenancy\Http\Middlewares;

use Closure;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\IpUtils;

class CheckTenantForMaintenanceModeMiddleware extends CheckForMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(\Illuminate\Http\Request): (Response) $next
     */
    public function handle($request, Closure $next)
    {
        if (!tenant()) {
            return $next($request);
        }

        if (tenant('maintenance_mode')) {
            $data = tenant('maintenance_mode');

            if (isset($data['allowed']) && IpUtils::checkIp($request->ip(), (array)$data['allowed'])) {
                $response = $next($request);

                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
                $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');

                return $response;
            }

            if ($this->inExceptArray($request)) {
                return $next($request);
            }

            throw new HttpException(
                503,
                __('cms.maintenance'),
                null,
                isset($data['retry']) ? ['Retry-After' => $data['retry']] : []
            );
        }

        return $next($request);
    }
}
