<x-admin-layout title="Manage Vehicles">
    <section class="welcome-row">
        <div><h2>Vehicle catalog</h2><p>Manage makes, models, photos and available panels for customer claims.</p><p class="catalog-help">Imported make and model names are broad, but worldwide 2015–2026 coverage is unverified. Photos and measured panel areas must be supplied per model. <a href="{{ route('vehicle-data.credits') }}">Catalog source and limits</a></p></div>
        <a class="button button--primary" href="{{ route('admin.vehicles.create') }}">Add vehicle make +</a>
    </section>
    <form class="customer-search" method="GET">
        <div class="field__control"><input name="search" value="{{ request('search') }}" placeholder="Search make or model"></div>
        <button class="button button--dark">Search</button>
        @if(request('search'))<a href="{{ route('admin.vehicles.index') }}">Clear</a>@endif
    </form>
    <div class="admin-table-wrap"><table class="admin-table vehicle-admin-table catalog-table">
        <thead><tr><th>Vehicle make</th><th>Models</th><th>Active / Inactive</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($vehicles as $vehicle)
            <tr>
                <td><b>{{ $vehicle->name }}</b></td>
                <td><b>{{ $vehicle->models_count }}</b></td>
                <td><form method="POST" action="{{ route('admin.vehicles.toggle', $vehicle) }}">@csrf @method('PATCH')<button class="catalog-switch" type="submit" role="switch" aria-checked="{{ $vehicle->is_active ? 'true' : 'false' }}" aria-label="{{ $vehicle->is_active ? 'Deactivate' : 'Activate' }} {{ $vehicle->name }}" title="{{ $vehicle->is_active ? 'Deactivate' : 'Activate' }} {{ $vehicle->name }}"><span></span></button></form></td>
                <td><span class="table-status {{ $vehicle->is_active ? 'active' : 'inactive' }}">{{ $vehicle->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td><div class="catalog-actions">
                    <a class="catalog-action" href="{{ route('admin.vehicles.show', $vehicle) }}" title="View {{ $vehicle->name }}" aria-label="View {{ $vehicle->name }}"><x-catalog-action-icon name="view"/></a>
                    <a class="catalog-action" href="{{ route('admin.vehicles.edit', $vehicle) }}" title="Edit {{ $vehicle->name }}" aria-label="Edit {{ $vehicle->name }}"><x-catalog-action-icon name="edit"/></a>
                    <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Delete this make and all its models?')">@csrf @method('DELETE')<button class="catalog-action catalog-action--danger" type="submit" title="Delete {{ $vehicle->name }}" aria-label="Delete {{ $vehicle->name }}"><x-catalog-action-icon name="delete"/></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5">No vehicle makes yet. Add the first one to populate the claim form.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="mt-4">
        {{ $vehicles->links() }}
    </div>
</x-admin-layout>
