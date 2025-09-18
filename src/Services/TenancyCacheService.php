<?php

namespace Netauratech\MultiTenancy\Services;

use Illuminate\Support\Facades\Artisan;
use Litespeed\LSCache\LSCache;
use Netauratech\CoreCms\Contracts\CacheServiceInterface;
use Netauratech\CoreCms\Events\CacheCleared;

class TenancyCacheService implements CacheServiceInterface
{
    public function clear(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        $tenant = tenant();

        if ($tenant) {
            $tags = ['tenant_' . $tenant->id];

            foreach ($tenant->domains as $domain) {
                $tags[] = $domain['domain'] . '_CSS';
                $tags[] = $domain['domain'] . '_JS';
            }

            LSCache::purgeTags($tags);
        } else {
            LSCache::purgeAll();
        }

        CacheCleared::dispatch();
    }

    public function purgeItems(mixed $items): void
    {
        LSCache::purgeItems($items);
    }
}