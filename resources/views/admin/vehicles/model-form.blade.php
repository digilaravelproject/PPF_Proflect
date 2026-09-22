<x-admin-layout :title="$model->exists ? 'Edit vehicle model' : 'Add vehicle model'">
    <div class="form-page-heading"><a href="{{ route('admin.vehicles.show', $vehicle) }}">← {{ $vehicle->name }} models</a><h2>{{ $model->exists ? 'Edit' : 'Add' }} {{ $vehicle->name }} model</h2><p>Upload a vehicle photo and set the allowed area range for every panel.</p></div>
    <form class="panel admin-form catalog-form" method="POST" enctype="multipart/form-data" action="{{ $model->exists ? route('admin.vehicles.models.update', [$vehicle, $model]) : route('admin.vehicles.models.store', $vehicle) }}">@csrf @if($model->exists)@method('PUT')@endif
        <div class="field"><label for="name">Model name</label><div class="field__control"><input id="name" name="name" value="{{ old('name', $model->name) }}" placeholder="e.g. Fortuner" required></div>@error('name')<p class="field__error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="kind">Vehicle type</label>@if($model->exists)<div class="field__control"><input value="{{ ucfirst($model->kind) }}" readonly><input type="hidden" name="kind" value="{{ $model->kind }}"></div>@else<div class="field__control"><select id="kind" name="kind" onchange="window.location.search='?kind='+encodeURIComponent(this.value)">@foreach(array_keys(\App\Support\VehiclePanelPresets::NAMES) as $kind)<option value="{{ $kind }}" @selected(old('kind', $selectedKind) === $kind)>{{ ucfirst($kind) }}</option>@endforeach</select></div>@endif</div>
        <div class="field" data-catalog-photo><label for="photo">Vehicle model photo {{ $model->exists ? '(leave empty to keep current photo)' : '' }}</label><input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"><input type="hidden" name="remove_photo" value="{{ old('remove_photo', 0) }}" data-remove-photo>
            <div class="catalog-photo-preview" data-photo-preview @if(!$model->photo_path || old('remove_photo')) hidden @endif>
                <div class="catalog-photo-stage" data-photo-stage><img src="{{ $model->photo_path ? route('catalog.models.photo', $model) : '' }}" alt="Model photo preview"><svg data-photo-areas viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"></svg></div>
                <button class="catalog-photo-remove" type="button" data-remove-model-photo aria-label="Remove model photo" title="Remove model photo">×</button>
            </div>
            <p class="catalog-help" data-photo-removal-note hidden>Photo will be removed when you save the model.</p>
            <p class="catalog-photo-instruction" data-photo-instruction role="status" aria-live="polite">Choose “Draw area”, then press and drag across the photo. Drag the finished area to move it; drag its corners to adjust the shape.</p>
            <button type="button" class="catalog-photo-cancel" data-cancel-photo-draw hidden>Cancel drawing</button>
            @error('photo')<p class="field__error">{{ $message }}</p>@enderror
        </div>
        <label class="check-row"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $model->exists ? $model->is_active : true) ? 'checked' : '' }}>Active and available to customers</label>
        <hr><div><h3>Panels and area limits</h3><p class="catalog-help">Edit or remove any default panel, or add a custom panel. Values are in square metres. Choose “Draw area”, then press and drag across the photo above. Drag the area to move it or drag its corners to reshape it.</p></div>
        <div data-catalog-panels data-next-index="{{ count(old('panels', $model->exists ? $model->panels->toArray() : collect($defaultPanels)->map(fn ($label) => ['name' => $label, 'min_sqm' => '', 'max_sqm' => ''])->values()->all())) }}">
        @foreach(old('panels', $model->exists ? $model->panels->toArray() : collect($defaultPanels)->map(fn ($label) => ['name' => $label, 'min_sqm' => '', 'max_sqm' => ''])->values()->all()) as $index => $panel)
            <div class="catalog-panel-row" data-catalog-panel-row><div class="field"><label>Panel name</label><div class="field__control"><input name="panels[{{ $index }}][name]" value="{{ $panel['name'] }}" required></div></div><div class="field"><label>Min m²</label><div class="field__control"><input type="number" step="0.01" min="0" name="panels[{{ $index }}][min_sqm]" value="{{ $panel['min_sqm'] }}" required></div></div><div class="field"><label>Max m²</label><div class="field__control"><input type="number" step="0.01" min="0" name="panels[{{ $index }}][max_sqm]" value="{{ $panel['max_sqm'] }}" required></div></div><div class="catalog-panel-position"><input type="hidden" name="panels[{{ $index }}][photo_polygon]" value="{{ is_array($panel['photo_polygon'] ?? null) ? json_encode($panel['photo_polygon']) : ($panel['photo_polygon'] ?? '') }}" data-photo-polygon><button type="button" data-draw-panel-area>Draw area</button><button type="button" data-clear-panel-area aria-label="Clear panel area" title="Clear panel area">×</button></div><button type="button" class="icon-action icon-action--danger" data-remove-panel aria-label="Remove panel">×</button></div>
        @endforeach
        </div>
        @error('panels')<p class="field__error">{{ $message }}</p>@enderror
        <button type="button" class="button button--ghost" data-add-panel>+ Add panel</button>
        <div class="form-actions"><button class="button button--primary">{{ $model->exists ? 'Save model' : 'Add model' }}</button><a class="button button--cancel" href="{{ route('admin.vehicles.show', $vehicle) }}">Cancel</a></div>
    </form>
</x-admin-layout>

