# Multi-Tenancy Package

A comprehensive Laravel package that extends the core-cms system with multi-tenancy capabilities, enabling multiple isolated tenant environments within a single application instance.

## Description

This package provides complete multi-tenancy functionality for the core-cms system, allowing you to host multiple separate websites or applications within a single Laravel installation. Each tenant has its own isolated database, file storage, cache, and configuration while sharing the same codebase. Built on top of Stancl's Tenancy package.

## Features

- ✅ Complete tenant isolation (database, storage, cache)
- ✅ Domain-based tenant identification
- ✅ Automated tenant database creation and migration
- ✅ Per-tenant file system isolation
- ✅ Tenant-specific environment configuration
- ✅ Maintenance mode per tenant
- ✅ Admin interface for tenant management
- ✅ Multi-domain support per tenant
- ✅ LiteSpeed cache integration
- ✅ Automated tenant backup system
- ✅ Multi-language support (English/French)

## Requirements

- PHP ^8.1
- Laravel ^12.0
- netauratech/core-cms ^1.0
- stancl/tenancy ^3.9

## Installation

### Via Composer

```bash
composer require netauratech/multi-tenancy
```

### Manual Installation

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:NetAuraTech/multi-tenancy.git"
        }
    ]
}
```

```bash
composer require netauratech/multi-tenancy
```

## Configuration

### 1. Publishing Files

```bash
# Configuration
php artisan vendor:publish --tag=multi-tenancy-config

# Migrations
php artisan vendor:publish --tag=multi-tenancy-migrations
php artisan migrate

# Seeders
php artisan vendor:publish --tag=multi-tenancy-seeders

# Translations
php artisan vendor:publish --tag=multi-tenancy-translations
```

### 2. Environment Variables

```env
# Database Configuration
DB_CONNECTION=central
TENANT_DB_PREFIX=tenant_

# App URL for central domains
APP_URL=yourdomain.com

# Backup Configuration (optional)
BACKUP_LOCATION_FOLDER=backup
```

## Usage

### Admin Interface

Navigate to `/admin/tenants` to:
- View all tenants with their domains
- Create/edit tenants with owner information
- Add/remove domains for each tenant
- Toggle maintenance mode per tenant
- Delete tenants with automatic cleanup

### Creating Tenants Programmatically

```php
use Netauratech\MultiTenancy\Models\Tenant;

$tenant = new Tenant();
$tenant->id = 'client-abc';
$tenant->name = 'Client ABC Company';
$tenant->owner = [
    'name' => 'John Doe',
    'email' => 'john@clientabc.com',
    'status' => 'Active',
    'address' => '123 Business Street',
    'siret' => '12345678901234'
];
$tenant->save();

// Add domains
$tenant->domains()->create(['domain' => 'clientabc.com']);
```

### Tenant Context

```php
// Check if in tenant context
if (tenant()) {
    $tenantId = tenant('id');
    $tenantName = tenant('name');
}

// Execute code in tenant context
$tenant->run(function () {
    Option::where('key', 'setting')->update(['value' => 'new_value']);
});
```

### Maintenance Mode

```php
// Enable maintenance mode
$tenant->putDownForMaintenance([
    'allowed' => ['192.168.1.1'] // IP whitelist
]);

// Disable maintenance mode
$tenant->update(['maintenance_mode' => null]);
```

## Key Features

### Automatic Tenant Lifecycle

**When a tenant is created:**
- New isolated database is created (`tenant_{id}`)
- All CMS migrations are run
- Database is seeded with default data
- Tenant-specific storage directories are created
- Environment file is generated
- Default subdomain is created

**When a tenant is deleted:**
- Database and all data is removed
- Storage files are cleaned up
- Cache entries are purged
- All domains are removed

### Database & Storage Isolation

```php
// Each tenant gets isolated:
// - Database: tenant_{tenant_id}
// - Storage: storage/tenant_{tenant_id}/
// - Cache: prefixed with tenant_{tenant_id}_
```

### Environment Configuration

Each tenant gets its own `.env` file at `storage/tenant_{tenant_id}/.env`:

```env
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
# Custom tenant-specific variables
```

## API Endpoints

### Tenant Management
```
GET    /admin/tenants              # List tenants
GET    /admin/tenants/create       # Create form
POST   /admin/tenants              # Store tenant
GET    /admin/tenants/{id}/edit    # Edit form  
PUT    /admin/tenants/{id}         # Update tenant
DELETE /admin/tenants/{id}         # Delete tenant
```

### Domain Management
```
POST   /admin/tenants/{id}/domain     # Add domain
DELETE /admin/tenants/{id}/domain     # Remove domain
```

### Maintenance Mode
```
POST   /admin/tenants/{id}/maintenance  # Toggle maintenance
```

## Form Validation

### Tenant Form
```php
'id' => ['nullable'],                    // Auto-generated if empty
'name' => ['required', 'min:3'],         
'owner.name' => ['required', 'min:3'],   
'owner.email' => ['required', 'email'],
'owner.status' => ['required', 'min:3'],
'owner.address' => ['required', 'min:3'],
'owner.siret' => ['required', 'min:3'],
```

### Domain Form
```php
'domain' => ['required', 'min:3'],
```

## Permissions

```php
$permissions = [
    'tenant-list',      // View tenants
    'tenant-create',    // Create tenants
    'tenant-edit',      // Edit tenants  
    'tenant-delete',    // Delete tenants
];
```

## Configuration Files

### Main Configuration (`config/tenancy.php`)

```php
return [
    'tenant_model' => Tenant::class,
    'domain_model' => Domain::class,
    'central_domains' => [env('APP_URL')],
    
    'database' => [
        'prefix' => env('TENANT_DB_PREFIX', 'tenant_'),
    ],
    
    'cache' => [
        'tag_base' => 'tenant_',
    ],
    
    'filesystem' => [
        'suffix_base' => 'tenant_',
        'disks' => ['local', 'public'],
    ],
];
```

## Backup System

The package automatically handles multi-tenant backups:
- Backs up central application
- Iterates through all tenants
- Creates individual backups for each tenant database and files
- Maintains separate retention policies

## Translations

Available in English and French:

```php
// English
'tenant' => [
    'created' => 'The tenant has been created.',
    'value' => '{0}Tenants|[1,1]tenant|[2,*]tenants',
];

// French  
'tenant' => [
    'created' => 'Le locataire a bien été créé.',
    'value' => '{0}Locataires|[1,1]un locataire|[2,*]les locataires',
];
```

## File Structure

```
src/
├── Http/Controllers/Admin/TenantController.php
├── Http/Middlewares/                      # Tenant isolation middleware
├── Http/Requests/Admin/                   # Form validation
├── Models/Tenant.php                      # Extended tenant model
├── Services/BackupProvider.php            # Multi-tenant backup
├── database/migrations/                   # Database schema
├── resources/views/admin/tenants/         # Admin templates
├── lang/                                  # Translations
└── MultiTenancyServiceProvider.php        # Main service provider
```

## License

This package is open-source software licensed under the [MIT license](LICENSE).

## Support

For support, questions, or bug reports:
- Email: contact@netauratech.fr
- GitHub Issues: Create an issue on the repository

## Changelog

### v1.0.0
- Initial release with complete multi-tenant architecture
- Admin interface for tenant management
- Domain management and maintenance mode
- LiteSpeed cache integration
- Automated backup system

## Authors

**NetAuraTech** - [contact@netauratech.fr](mailto:contact@netauratech.fr)

---

© 2025 NetAuraTech. All rights reserved.