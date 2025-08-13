<?php

namespace Netauratech\MultiTenancy\Models;

use Illuminate\Support\Facades\File;
use Netauratech\CoreCms\Models\Option;
use Stancl\Tenancy\Database\Concerns\MaintenanceMode;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, MaintenanceMode;

    protected static function booted(): void
    {
        static::created(function (Tenant $tenant) {
            $tenantId = $tenant->id;

            $envPath = storage_path("tenant_{$tenantId}/.env");

            if (!File::exists($envPath)) {
                $envContent = [
                    'FACEBOOK_CLIENT_ID=',
                    'FACEBOOK_CLIENT_SECRET=',
                    'GOOGLE_CLIENT_ID=',
                    'GOOGLE_CLIENT_SECRET=',
                    'GITHUB_CLIENT_ID=',
                    'GITHUB_CLIENT_SECRET=',
                ];

                $envFileContent = implode("\n", $envContent);

                File::put($envPath, $envFileContent);
            }

            $tenant->run(function () {
                $option = Option::where('key', 'site_name');
                $option->update([
                    'value' => tenant()->name
                ]);
            });
        });
    }
}
