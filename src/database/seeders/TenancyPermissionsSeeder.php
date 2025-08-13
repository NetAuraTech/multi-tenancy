<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TenancyPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if(!tenant()) {
            $permissions = [
                'tenant-list',
                'tenant-create',
                'tenant-edit',
                'tenant-delete',
            ];

            foreach ($permissions as $permission) {
                Permission::create(['name' => $permission]);
            }
        }
    }
}
