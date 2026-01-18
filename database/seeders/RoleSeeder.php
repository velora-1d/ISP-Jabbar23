<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Permissions
        $permissions = [
            // Dashboard
            'view dashboard',
            'view stats',

            // CRM (Leads & Customers)
            'view leads', 'create leads', 'edit leads', 'delete leads',
            'view customers', 'create customers', 'edit customers', 'delete customers',

            // Finance (Invoices & Payments)
            'view financial reports',
            'view invoices', 'create invoices', 'edit invoices', 'delete invoices',
            'view payments', 'create payments', 'verify payments',

            // Inventory
            'view inventory', 'create items', 'adjust stock', 'view stock movements',

            // Field Ops (Work Orders)
            'view work orders', 'create work orders', 'edit work orders', 'update work order status',
            'view schedule',

            // HR & Users
            'view users', 'create users', 'edit users', 'manage roles',
            'view technicians',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Define Roles & Assign Permissions

        // A. SUPER ADMIN (God Mode)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        // Super admin gets all permissions via Gate::before rule usually, 
        // but we can also give all permissions explicitely if validation needs it.
        $superAdmin->givePermissionTo(Permission::all());

        // B. ADMIN (Operational Manager)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'view dashboard', 'view stats',
            'view leads', 'create leads', 'edit leads', 'delete leads',
            'view customers', 'create customers', 'edit customers',
            'view invoices', 'create invoices', 'edit invoices',
            'view payments', 'create payments', 'verify payments',
            'view inventory', 'create items', 'adjust stock', 'view stock movements',
            'view work orders', 'create work orders', 'edit work orders', 'update work order status',
            'view schedule',
            'view technicians',
            'view users', // Can view staff but generally not manage root roles
        ]);

        // C. FINANCE (Bendahara)
        $finance = Role::firstOrCreate(['name' => 'finance']);
        $finance->givePermissionTo([
            'view dashboard',
            'view financial reports',
            'view invoices', 'create invoices', 'edit invoices', 'delete invoices',
            'view payments', 'create payments', 'verify payments',
            'view customers', // View only for billing context
        ]);

        // D. TECHNICIAN (Teknisi Lapangan)
        $technician = Role::firstOrCreate(['name' => 'technician']);
        $technician->givePermissionTo([
            'view work orders', 'update work order status', 
            'view schedule',
            'view inventory', // Check stock only
            'view stock movements', // See own history
        ]);

        // E. SALES (Sales Marketing)
        $sales = Role::firstOrCreate(['name' => 'sales']);
        $sales->givePermissionTo([
            'view dashboard',
            'view leads', 'create leads', 'edit leads',
            'view customers', 'create customers',
            'view invoices', // View price/packages for quotation
        ]);

        // 3. Create Demo Users
        // Admin
        $userAdmin = User::firstOrCreate(
            ['email' => 'admin@isp.local'],
            [
                'name' => 'Operational Manager',
                'password' => bcrypt('password'),
            ]
        );
        $userAdmin->assignRole('admin');

        // Finance
        $userFinance = User::firstOrCreate(
            ['email' => 'finance@isp.local'],
            [
                'name' => 'Finance Staff',
                'password' => bcrypt('password'),
            ]
        );
        $userFinance->assignRole('finance');

        // Technician 1
        $userTech1 = User::firstOrCreate(
            ['email' => 'tech1@isp.local'],
            [
                'name' => 'Ahmad Teknisi',
                'password' => bcrypt('password'),
            ]
        );
        $userTech1->assignRole('technician');

        // Sales
        $userSales = User::firstOrCreate(
            ['email' => 'sales@isp.local'],
            [
                'name' => 'Siti Sales',
                'password' => bcrypt('password'),
            ]
        );
        $userSales->assignRole('sales');
        
        // Ensure current user (v@v.com or similar if exists) is super-admin
        // Super Admin (Dedicated)
        $userSuper = User::firstOrCreate(
            ['email' => 'super@isp.local'],
            [
                'name' => 'Real Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $userSuper->assignRole('super-admin');
    }
}
