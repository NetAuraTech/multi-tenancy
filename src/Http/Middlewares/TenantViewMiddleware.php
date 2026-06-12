<?php

namespace Netauratech\MultiTenancy\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Netauratech\CoreCms\Contracts\ContentProviderInterface;
use Netauratech\CoreCms\Contracts\MediaProviderInterface;
use Netauratech\CoreCms\Models\Option;
use Symfony\Component\HttpFoundation\Response;

class TenantViewMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant()) {
            $hasOptionsTable = Cache::rememberForever('schema_has_options', function () {
                return Schema::hasTable('options');
            });

            if ($hasOptionsTable) {
                View::composer(['core-cms::base', 'core-cms::front/page', 'portfolio-manager::front/portfolio.show', 'core-cms::auth/*', 'core-cms::profile/*', 'core-cms::admin.base', 'core-cms::admin.contents.preview', 'theme::*'], function ($view) {
                    $cache = Cache::getFacadeRoot();
                    $ret = $cache->remember('options_optimized', 3600, function () {
                        $opts = Option::all();
                        $data = [];
                        $contentProvider = app()->make(ContentProviderInterface::class);
                        $mediaProvider = app()->make(MediaProviderInterface::class);
                        $theme = null;

                        foreach ($opts as $option) {
                            $valueToStore = $option->value ?? '';

                            if (($option->type === 'content' || $option->type === 'template') && $option->value !== "") {
                                $valueToStore = $contentProvider->getContentById($option->value);
                            }

                            if ($option->type === 'theme') {
                                $theme = $option;
                            }

                            $data[$option->key] = $valueToStore;
                        }

                        $favicon = (isset($data['favicon']) && $data['favicon']) ? image_url($data['favicon'], 128) : null;
                        $ogLogo = (isset($data['logo']) && $data['logo']) ? $mediaProvider->get($data['logo']) : null;
                        $cacheBuster = isset($theme->updated_at) ? substr(md5(json_encode($theme->updated_at)), 0, 8) : 'dev';

                        return [
                            "options"        => $data,
                            "theme"          => $theme,
                            "favicon"        => $favicon,
                            "openGraphLogo"  => $ogLogo,
                            "cacheBuster"    => $cacheBuster
                        ];
                    });

                    $view->with('options', $ret['options']);
                    $view->with('favicon', $ret['favicon']);
                    $view->with('openGraphLogo', $ret['openGraphLogo']);
                    $view->with('cacheBuster', $ret['cacheBuster']);
                });
            }
        }

        return $next($request);
    }
}
