<x-admin-layout :title="$vehicle->exists ? 'Edit vehicle make' : 'Add vehicle make'">
    <div class="form-page-heading"><a href="{{ route('admin.vehicles.index') }}">← Manage vehicles</a><h2>{{ $vehicle->exists ? 'Edit vehicle make' : 'Add vehicle make' }}</h2><p>Add the manufacturer, then add its models and panels.</p></div>
    <form class="panel admin-form" method="POST" action="{{ $vehicle->exists ? route('admin.vehicles.update', $vehicle) : route('admin.vehicles.store') }}">@csrf @if($vehicle->exists)@method('PUT')@endif
        <div class="field"><label for="name">Vehicle make</label><div class="field__control"><input id="name" name="name" value="{{ old('name', $vehicle->name) }}" placeholder="e.g. Toyota" required></div>@error('name')<p class="field__error">{{ $message }}</p>@enderror</div>
        <label class="check-row"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $vehicle->exists ? $vehicle->is_active : true) ? 'checked' : '' }}>Active and available to customers</label>
        <div class="form-actions"><button class="button button--primary">{{ $vehicle->exists ? 'Save changes' : 'Add vehicle make' }}</button><a class="button button--cancel" href="{{ route('admin.vehicles.index') }}">Cancel</a></div>
    </form>
</x-admin-layout>
