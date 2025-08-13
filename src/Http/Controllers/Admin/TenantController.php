<?php

namespace Netauratech\MultiTenancy\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Litespeed\LSCache\LSCache;
use Netauratech\CoreCms\Http\Controllers\AdminController;
use Netauratech\MultiTenancy\Http\Requests\Admin\DomainFormRequest;
use Netauratech\MultiTenancy\Http\Requests\Admin\TenantFormRequest;
use Netauratech\MultiTenancy\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\Tenancy;

class TenantController extends AdminController
{
    protected array $permissions = [
        'tenant-list'   => ['index'],
        'tenant-create' => ['create', 'store', 'domain_store', 'toggle_maintenance'],
        'tenant-edit'   => ['edit', 'update', 'domain_store', 'toggle_maintenance'],
        'tenant-delete' => ['destroy', 'domain_destroy'],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('multi-tenancy::admin.tenants.index', [
            'tenants' => Tenant::orderBy('created_at', 'desc')->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $tenant = new Tenant();
        $tenant->created_at = new Carbon();

        return view('multi-tenancy::admin.tenants.form', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TenantFormRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tenant = new Tenant();
        $tenant->id = $validated['id'];
        $tenant->name = $validated['name'];
        $tenant->owner = $validated['owner'];
        $tenant->save();

        $tenant->domains()->create([
            'domain' => $tenant->id . '.' . env('APP_URL')
        ]);

        return to_route('admin.tenants.index')->with('success', __('multi-tenancy::admin.tenant.created'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant): View
    {
        return view('multi-tenancy::admin.tenants.form', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TenantFormRequest $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validated();
        $tenant->update($data);

        return to_route('admin.tenants.index')->with('success', __('multi-tenancy::admin.tenant.updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $tenant = Tenant::find($id);
        if (!$tenant) {
            return redirect()->route('admin.tenants.index')
                ->with('error', __('multi-tenancy::admin.tenant.not_found'));
        }

        $tenant->delete();
        return to_route('admin.tenants.index')->with('success', __('multi-tenancy::admin.tenant.deleted'));
    }

    public function domain_store(DomainFormRequest $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validated();
        $tenant->domains()->create([
            'domain' => $data['domain']
        ]);

        return to_route('admin.tenants.index')->with('success', __('multi-tenancy::admin.tenant.domain.created'));
    }

    public function domain_destroy(string $id): RedirectResponse
    {
        $domain = Domain::find($id);
        if (!$domain) {
            return redirect()->route('admin.tenants.index')
                ->with('error', __('multi-tenancy::admin.tenant.domain.not_found'));
        }

        $domain->delete();
        return to_route('admin.tenants.index')->with('success', __('multi-tenancy::admin.tenant.domain.deleted'));
    }

    public function toggle_maintenance(Tenant $tenant, Request $request): RedirectResponse
    {
        if($tenant->maintenance_mode) {
            $tenant->update(['maintenance_mode' => null]);
            return to_route('admin.tenants.index')->with('success', __('multi-tenancy::admin.tenant.maintenance.ended'));
        }

        $tenant->putDownForMaintenance([
            "allowed" => [$request->getClientIp()]
        ]);

        Tenancy::initialize($tenant);
        $tags = ['tenant_' . $tenant->id];

        foreach ($tenant->domains as $domain) {
            $tags[] = $domain['domain'] . '_CSS';
            $tags[] = $domain['domain'] . '_JS';
        }

        LSCache::purgeTags($tags);
        Tenancy::end();

        return to_route('admin.tenants.index')->with('success', __('multi-tenancy::admin.tenant.maintenance.started'));
    }
}
