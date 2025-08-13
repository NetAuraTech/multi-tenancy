<?php

namespace Netauratech\MultiTenancy\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Netauratech\CoreCms\Contracts\ContentProviderInterface;
use Netauratech\CoreCms\Models\Option;
use Symfony\Component\HttpFoundation\Response;

class TenantViewMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (tenant()) {
            if (Schema::hasTable('options')) {
                $cache = Cache::store('database');
                $ret = $cache->remember('options', 60 * 60, function () {
                    $opts = Option::all();
                    $data = [];
                    $contentProvider = app()->make(ContentProviderInterface::class);

                    foreach ($opts as $option) {
                        $valueToStore = $option->value ?? '';

                        if ($option->type === 'content') {
                            $contentItem = $contentProvider->getContentById($option->value);
                            $valueToStore = $contentItem;
                        }
                        $data[$option->key] = $valueToStore;
                    }
                    return $data;
                });

                View::composer('*', function ($view) use ($ret) {
                    $view->with('options', $ret);
                    $view->with('favicon', $ret['favicon'] ? image_url($ret['favicon'], 128) : "");
                    $view->with('openGraphLogo', $ret['logo'] ? image_url($ret['logo']) : "");
                });
            }
        }

        return $next($request);
    }
}
