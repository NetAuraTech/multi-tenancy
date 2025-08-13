<?php

namespace Netauratech\MultiTenancy\Http\Middlewares;

use Closure;
use Dotenv\Dotenv;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class LoadTenantEnvMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant =  tenant();

        if($tenant) {
            $tenantEnvPath = storage_path(".env");
            if (file_exists($tenantEnvPath)) {
                $dotenv = Dotenv::createMutable(storage_path("/"), '.env');
                $env = $dotenv->load();

                foreach ($env as $key => $value) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;

                    foreach ($env as $key => $value) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;

                        $this->injectServiceConfig($key, $value);
                    }
                }
            }
        }



        return $next($request);
    }

    /**
     * Dynamically injects variables into Laravel config for services.
     *
     * @param string $key
     * @param string $value
     */
    protected function injectServiceConfig(string $key, string $value): void
    {
        if ($key === 'APP_KEY') {
            return;
        }

        if (preg_match('/^([A-Z]+)_/', $key, $matches)) {
            $service = strtolower($matches[1]);

            $configKey = strtolower(Str::after($key, $matches[0]));

            Config::set("services.$service.$configKey", $value);
        }
    }
}
