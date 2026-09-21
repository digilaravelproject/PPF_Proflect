<x-admin-layout :title="$vehicle->name.' models'">
    <section class="welcome-row">
        <div><a class="back-link" href="{{ route('admin.vehicles.index') }}">← Manage vehicles</a><h2>{{ $vehicle->name }}</h2><p>{{ $vehicle->is_active ? 'Active make' : 'Inactive make' }} · {{ $models->total() }} models</p></div>
        <a class="button button--primary" href="{{ route('admin.vehicles.models.create', $vehicle) }}">Add model +</a>
    </section>
    <form class="customer-search" method="GET"><div class="field__control"><input name="search" value="{{ request('search') }}" placeholder="Search {{ $vehicle->name }} models"></div><button class="button button--dark">Search</button>@if(request('search'))<a href="{{ route('admin.vehicles.show', $vehicle) }}">Clear</a>@endif</form>
    <div class="admin-table-wrap"><table class="admin-table catalog-table">
        <thead><tr><th>Model</th><th>Photo</th><th>Panels</th><th>Active / Inactive</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($models as $model)
            <tr>
                <td><b>{{ $model->name }}</b><small>{{ ucfirst($model->kind) }}</small></td>
                <td>@if($model->photo_path)<img class="catalog-thumb" src="{{ route('catalog.models.photo', $model) }}" alt="{{ $vehicle->name }} {{ $model->name }}">@else<span class="catalog-help">Pending</span>@endif</td>
                <td>{{ $model->panels_count }}</td>
                <td><form method="POST" action="{{ route('admin.vehicles.models.toggle', [$vehicle, $model]) }}">@csrf @method('PATCH')<button class="catalog-switch" type="submit" role="switch" aria-checked="{{ $model->is_active ? 'true' : 'false' }}" aria-label="{{ $model->is_active ? 'Deactivate' : 'Activate' }} {{ $vehicle->name }} {{ $model->name }}" title="{{ $model->is_active ? 'Deactivate' : 'Activate' }} {{ $model->name }}"><span></span></button></form></td>
                <td><span class="table-status {{ $model->is_active ? 'active' : 'inactive' }}">{{ $model->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td><div class="catalog-actions">
                    <a class="catalog-action" href="{{ route('admin.vehicles.models.show', [$vehicle, $model]) }}" title="View {{ $model->name }}" aria-label="View {{ $model->name }}"><x-catalog-action-icon name="view"/></a>
                    <a class="catalog-action" href="{{ route('admin.vehicles.models.edit', [$vehicle, $model]) }}" title="Edit {{ $model->name }}" aria-label="Edit {{ $model->name }}"><x-catalog-action-icon name="edit"/></a>
                    <form method="POST" action="{{ route('admin.vehicles.models.destroy', [$vehicle, $model]) }}" onsubmit="return confirm('Delete this model?')">@csrf @method('DELETE')<button class="catalog-action catalog-action--danger" type="submit" title="Delete {{ $model->name }}" aria-label="Delete {{ $model->name }}"><x-catalog-action-icon name="delete"/></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6">No models yet. Add a model with its photo and panels.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="mt-4">
        {{ $models->links() }}
    </div>
</x-admin-layout>
