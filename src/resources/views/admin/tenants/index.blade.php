@extends('core-cms::admin.base')

@section('title')
    {{  __('core-cms::admin.manage') }} {{ trans_choice('multi-tenancy::admin.tenant.value', 2) }}
@endsection

@section('body')
    <section class="grid">
        <div class="flex-group justify-content-space-between align-items-center" style="width: initial">
            <h2 class="heading-2 flex-group align-items-center">{!! icon('tenant', 'small') !!} {{  __('core-cms::admin.manage') }} {{ trans_choice('multi-tenancy::admin.tenant.value', 2) }}</h2>
            <a class="button" href="{{ route('admin.tenants.create') }}"
               data-type="primary">{{ __('core-cms::admin.add') }} {{ trans_choice('multi-tenancy::admin.tenant.value', 1) }}</a>
        </div>
        <div class="card">
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('multi-tenancy::admin.tenant.id') }}</th>
                    <th>{{ __('multi-tenancy::admin.tenant.name') }}</th>
                    <th>{{ __('multi-tenancy::admin.tenant.domain.value') }}</th>
                    <th>{{ __('core-cms::admin.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tenants as $item)
                    <tr>
                        <td>
                            <a href="{{ route('admin.tenants.edit', $item) }}">{{ $item->id }}</a>
                        </td>
                        <td>
                            <a href="{{ route('admin.tenants.edit', $item) }}">{{ $item->name }}</a>
                        </td>
                        <td class="grid" style="width: fit-content">
                            @foreach($item->domains as $domain)
                                <div class="flex-group align-items-center">
                                    <a
                                        href="https://{{ $domain['domain'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ $domain['domain'] }}
                                    </a>
                                    <form
                                        class="clr-red-300"
                                        action="{{ route('admin.tenants.domain.destroy', $domain) }}"
                                        method="post"
                                        onsubmit="return confirm('{{ __('core-cms::admin.delete.confirm') }}')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="button padding-0" data-type="transparent"
                                                title="{{ __('core-cms::admin.delete.value') }} {{ $domain['domain'] }}">
                                            {!! icon('trash', 'small') !!}
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </td>
                        <td>
                            <div class="flex-group align-items-center justify-content-flex-end" style="width: initial">
                                <form id="addDomainForm"
                                      action="{{ route('admin.tenants.domain.store', $item) }}"
                                      method="post">
                                    @csrf
                                    <input type="hidden" id="domain" name="domain">
                                    <button
                                        onclick="handleSubmit()"
                                        class="button padding-0"
                                        data-type="transparent"
                                        title="{{ __('multi-tenancy::admin.tenant.domain.add.value') }}"
                                    >
                                        {!! icon('plus', 'small') !!}
                                    </button>
                                </form>
                                <form action="{{ route('admin.tenants.maintenance', $item) }}"
                                      method="post"
                                      class="@if($item->maintenance_mode)clr-red-300 @endif"
                                >
                                    @csrf
                                    <button
                                        class="button padding-0"
                                        data-type="transparent"
                                        title="{{ $item->maintenance_mode ? __('multi-tenancy::admin.tenant.maintenance.end') : __('multi-tenancy::admin.tenant.maintenance.start')}}"
                                    >
                                        {!! icon('maintenance', 'small') !!}
                                    </button>
                                </form>
                                <a href="{{ route('admin.tenants.edit', $item) }}" class="button padding-0"
                                   data-type="transparent"
                                   title="{{ __('core-cms::admin.edit') }} {{ $item->title }}">{!! icon('edit', 'small') !!}</a>
                                <form
                                    class="clr-red-300"
                                    action="{{ route('admin.tenants.destroy', $item) }}"
                                    method="post"
                                    onsubmit="return confirm('{{ __('core-cms::admin.delete.confirm') }}')">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="button padding-0" data-type="transparent"
                                            title="{{ __('core-cms::admin.delete.value') }} {{ $item->title }}">
                                        {!! icon('trash', 'small') !!}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{$tenants->links()}}
        </div>
        <script>
            function handleSubmit() {

                let domain = prompt("{{ __('multi-tenancy::admin.tenant.domain.add.help') }}");

                if (domain !== null && domain.trim() !== "") {
                    document.getElementById('domain').value = domain;
                    document.getElementById('addDomainForm').submit();
                }
            }
        </script>
    </section>
@endsection
