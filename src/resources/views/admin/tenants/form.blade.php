@extends('core-cms::admin.base')

@section('title')
    @if($tenant->exists)
        {{ __('core-cms::admin.edit') }} {{ trans_choice('multi-tenancy::admin.tenant.value', 1) }}
    @else
        {{ __('core-cms::admin.create') }} {{ trans_choice('multi-tenancy::admin.tenant.value', 1) }}
    @endif
@endsection

@section('meta')
    <meta name="turbolinks-visit-control" content="reload">
@endsection

@section('body')
    <section class="grid">
        <h2 class="heading-2 flex-group align-items-center">
            @if($tenant->exists)
                {!! icon('tenant', 'small') !!} {{ __('core-cms::admin.edit') }} {{ trans_choice('multi-tenancy::admin.tenant.value', 1) }}
            @else
                {!! icon('tenant', 'small') !!} {{ __('core-cms::admin.create') }} {{ trans_choice('multi-tenancy::admin.tenant.value', 1) }}
            @endif
        </h2>
        <div class="card">
            <form class="grid"
                  action="{{ route($tenant->exists ? 'admin.tenants.update' : 'admin.tenants.store', $tenant) }}"
                  method="POST">
                @csrf
                @method($tenant->exists ? 'put' : 'post')
                <div class="grid">
                    @include('core-cms::shared.input', ['label' => __('multi-tenancy::admin.tenant.id'), 'name' => 'id', 'value' => $tenant->id, 'disabled' => $tenant->exists])
                    @include('core-cms::shared.input', ['label' => __('multi-tenancy::admin.tenant.name'), 'name' => 'name', 'value' => $tenant->name])
                    @include('core-cms::shared.input', ['label' => __('multi-tenancy::admin.tenant.owner.name'), 'name' => 'owner[name]', 'value' => $tenant->owner['name'] ?? ''])
                    @include('core-cms::shared.input', ['label' => __('multi-tenancy::admin.tenant.owner.email'), 'name' => 'owner[email]', 'value' => $tenant->owner['email'] ?? ''])
                    @include('core-cms::shared.input', ['label' => __('multi-tenancy::admin.tenant.owner.status'), 'name' => 'owner[status]', 'value' => $tenant->owner['status'] ?? ''])
                    @include('core-cms::shared.input', ['label' => __('multi-tenancy::admin.tenant.owner.address'), 'name' => 'owner[address]', 'value' => $tenant->owner['address'] ?? ''])
                    @include('core-cms::shared.input', ['label' => __('multi-tenancy::admin.tenant.owner.siret'), 'name' => 'owner[siret]', 'value' => $tenant->owner['siret'] ?? ''])
                    <div class="text-center">
                        <button type="submit" class="button" data-type="primary">{{ __('core-cms::admin.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
