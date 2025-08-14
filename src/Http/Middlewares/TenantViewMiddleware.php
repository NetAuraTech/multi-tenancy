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

                    $theme = null;

                    foreach ($opts as $option) {
                        $valueToStore = $option->value ?? '';

                        if (($option->type === 'content' || $option->type === 'template') && $option->value !== "") {
                            $contentItem = $contentProvider->getContentById($option->value);
                            $valueToStore = $contentItem;
                        }
                        if ($option->type === 'theme') {
                            $theme = $option;
                        }
                        $data[$option->key] = $valueToStore;
                    }
                    return ["options" => $data, "theme" => $theme];
                });

                View::composer('*', function ($view) use ($ret) {
                    $view->with('options', $ret['options']);
                    $view->with('favicon', $ret['options']['favicon'] ? image_url($ret['options']['favicon'], 128) : "");
                    $view->with('openGraphLogo', $ret['options']['logo'] ? image_url($ret['options']['logo']) : "");
                    $view->with('cacheBuster', substr(md5(json_encode($ret['theme']->updated_at)), 0, 8));
                });
            }
        }

        return $next($request);
    }
}
