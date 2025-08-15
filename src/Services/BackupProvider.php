<?php

namespace Netauratech\MultiTenancy\Services;

use Illuminate\Support\Facades\Artisan;
use Netauratech\CoreCms\Contracts\BackupProviderInterface;
use Netauratech\MultiTenancy\Models\Tenant;

class BackupProvider implements BackupProviderInterface
{
    /**
     * Executes the backup process.
     *
     * This method should perform the complete backup workflow,
     * including creating the backup files and optionally
     * performing cleanup of existing backups.
     *
     * @param array $optionsBackup  Array of options related to backup creation.
     * @param array $optionsCleanup Array of options related to backup cleanup.
     *
     * @return void
     */
    public function run(array $optionsBackup, array $optionsCleanup): void
    {
        Artisan::call('core-cms:backup-run', $optionsBackup);
        Artisan::call('core-cms:backup-clean', $optionsCleanup);

        foreach (Tenant::cursor() as $tenant) {
            config(['backup.backup.name' => env('BACKUP_LOCATION_FOLDER', 'backup') . "/" . $tenant->id]);

            $monitorConfigs = config('backup.monitor_backups');

            foreach ($monitorConfigs as $index => $monitor) {
                $monitorConfigs[$index]['name'] = env('BACKUP_LOCATION_FOLDER', 'backup') . "/" . $tenant->id;
            }

            config(['backup.monitor_backups' => $monitorConfigs]);
            config(['backup.backup.source.files.include' => [storage_path('tenant_' . $tenant->id)]]);
            config(['database.connections.' . env('DB_CONNECTION') . '.database' => config('tenancy.database.prefix') . $tenant->id]);

            Artisan::call('core-cms:backup-run', $optionsBackup);
            Artisan::call('core-cms:backup-clean', $optionsCleanup);
        }

        config(['database.connections.' . env('DB_CONNECTION') . '.database' => env('DB_DATABASE')]);
    }
}