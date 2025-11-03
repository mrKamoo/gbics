<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'users.approve',
            'users.manage',
            'users.view',

            // Assets
            'assets.create',
            'assets.update',
            'assets.delete',
            'assets.view',

            // Stock
            'stock.manage',
            'stock.movements',
            'stock.view',

            // Locations
            'locations.manage',
            'locations.view',

            // Assignments
            'assignments.manage',
            'assignments.view',

            // Reports
            'reports.generate',
            'reports.view',

            // Catalog
            'catalog.sync',
            'catalog.view',

            // Alerts
            'alerts.manage',
            'alerts.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - All permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - Most permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'users.approve',
            'users.manage',
            'users.view',
            'assets.create',
            'assets.update',
            'assets.delete',
            'assets.view',
            'stock.manage',
            'stock.view',
            'locations.manage',
            'locations.view',
            'assignments.manage',
            'assignments.view',
            'reports.generate',
            'reports.view',
            'catalog.sync',
            'catalog.view',
            'alerts.manage',
            'alerts.view',
        ]);

        // Technician - Limited permissions
        $technician = Role::create(['name' => 'technician']);
        $technician->givePermissionTo([
            'users.view',
            'assets.create',
            'assets.update',
            'assets.view',
            'stock.movements',
            'stock.view',
            'locations.view',
            'assignments.manage',
            'assignments.view',
            'reports.view',
            'catalog.view',
            'alerts.view',
        ]);

        // Reader - View only
        $reader = Role::create(['name' => 'reader']);
        $reader->givePermissionTo([
            'users.view',
            'assets.view',
            'stock.view',
            'locations.view',
            'assignments.view',
            'reports.view',
            'catalog.view',
            'alerts.view',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
