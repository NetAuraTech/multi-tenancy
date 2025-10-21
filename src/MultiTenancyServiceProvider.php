<?php

namespace Netauratech\MultiTenancy;

use Database\Seeders\TenancyPermissionsSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Netauratech\CoreCms\Contracts\BackupProviderInterface;
use Netauratech\CoreCms\Contracts\CacheServiceInterface;
use Netauratech\CoreCms\Services\AbstractCmsServiceProvider;
use Netauratech\CoreCms\Services\Admin\MenuManager;
use Netauratech\CoreCms\Services\AssetManager;
use Netauratech\MultiTenancy\Http\Middlewares\AssignLSTagsMiddleware;
use Netauratech\MultiTenancy\Http\Middlewares\CheckTenantForMaintenanceModeMiddleware;
use Netauratech\MultiTenancy\Http\Middlewares\InitializeTenancyByDomainOrSkip;
use Netauratech\MultiTenancy\Http\Middlewares\LoadTenantEnvMiddleware;
use Netauratech\MultiTenancy\Http\Middlewares\TenantViewMiddleware;
use Netauratech\MultiTenancy\Services\BackupProvider;
use Netauratech\MultiTenancy\Services\TenancyCacheService;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;

class MultiTenancyServiceProvider extends AbstractCmsServiceProvider
{
    public static string $controllerNamespace = '';

    protected function getPackageName(): string
    {
        return 'multi-tenancy';
    }

    protected function getBootstrapConfig(): array
    {
        $config = parent::getBootstrapConfig();

        $config['assets'] = false;
        $config['routes']['web'] = false;
        $config['routes']['api'] = false;
        $config['routes']['auth'] = false;
        $config['publishes']['assets'] = false;

        return $config;
    }

    protected function getSeeders(): array
    {
        return [
            TenancyPermissionsSeeder::class,
        ];
    }

    public function events(): array
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\MigrateDatabase::class,
                    Jobs\SeedDatabase::class,
                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false),
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false),
            ],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],

            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }
    public function register(): void
    {
        $this->app->bind(BackupProviderInterface::class, BackupProvider::class);
        $this->app->bind(CacheServiceInterface::class, TenancyCacheService::class);
    }
    public function boot(MenuManager $menuManager, AssetManager $assetManager): void
    {
        $this->bootstrapPackage();

        $this->publishes([
            __DIR__.'/../config/tenancy.php' => config_path('tenancy.php'),
        ], 'core-cms-config');

        $this->bootEvents();
        $this->makeTenancyMiddlewareHighestPriority();

        $multiTenancyMiddlewares = [
            'universal',
            InitializeTenancyByDomainOrSkip::class,
            CheckTenantForMaintenanceModeMiddleware::class,
            AssignLSTagsMiddleware::class,
            TenantViewMiddleware::class,
            //DisableCacheForCaptchaMiddleware::class,
            LoadTenantEnvMiddleware::class
        ];

        $kernel = $this->app->make(Kernel::class);
        $kernel->setMiddlewareGroups(array_merge($kernel->getMiddlewareGroups(), ['universal' => []]));
        $currentWebMiddlewareGroup = $kernel->getMiddlewareGroups()['web'];
        $updatedWebMiddlewareGroup = array_merge($multiTenancyMiddlewares, $currentWebMiddlewareGroup);
        $kernel->setMiddlewareGroups(array_merge($kernel->getMiddlewareGroups(), ['web' => $updatedWebMiddlewareGroup]));

        $menuManager->registerMenuItem('tenant', [
            'label' => trans_choice('multi-tenancy::admin.tenant.value', 0),
            'icon' => 'tenant',
            'route' => 'admin.tenants.index',
            'can' => 'tenant-list'
        ]);

    }

    protected function bootEvents(): void
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        $tenancyMiddleware = [
            Middleware\PreventAccessFromCentralDomains::class,
            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }
}