<x-admin-layout :title="$vehicle->name.' '.$model->name">
    <section class="welcome-row">
        <div><a class="back-link" href="{{ route('admin.vehicles.show', $vehicle) }}">← {{ $vehicle->name }} models</a><h2>{{ $vehicle->name }} {{ $model->name }}</h2><p>{{ $model->is_active ? 'Active' : 'Inactive' }} · {{ ucfirst($model->kind) }} · {{ $model->panels->count() }} panels</p></div>
        <a class="button button--primary" href="{{ route('admin.vehicles.models.edit', [$vehicle, $model]) }}">Edit model</a>
    </section>
    <section class="panel catalog-detail">
        <div>
            @if($model->photo_path)
                <img src="{{ route('catalog.models.photo', $model) }}" alt="{{ $vehicle->name }} {{ $model->name }}">
            @else
                <div class="model-photo__pending">Model photo pending</div>
                <p class="catalog-help">Upload a verified photo for this model in Edit model.</p>
            @endif
        </div>
        <div><h3>Available panels</h3><div class="catalog-panel-list">
            @foreach($model->panels as $panel)
                <div><b>{{ $panel->name }}</b>
                    @if($panel->min_sqm === null || $panel->max_sqm === null)
                        <span>Area range pending</span>
                    @else
                        <span>min {{ number_format($panel->min_sqm, 2) }} m² · max {{ number_format($panel->max_sqm, 2) }} m²</span>
                    @endif
                </div>
            @endforeach
        </div></div>
    </section>
</x-admin-layout>
