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
            $tenantDirectory = storage_path("tenant_{$tenantId}");

            $directories = [
                $tenantDirectory,
                "{$tenantDirectory}/app",
                "{$tenantDirectory}/app/public",
                "{$tenantDirectory}/app/public/css",
                "{$tenantDirectory}/app/private",
            ];

            foreach ($directories as $directory) {
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
            }

            $envPath = "{$tenantDirectory}/.env";

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

            $tenant->run(function () use ($tenant) {
                $option = Option::where('key', 'site_name')->first();

                if ($option) {
                    $option->update([
                        'value' => $tenant->name
                    ]);
                }
            });
        });
    }
}
