<x-customer-layout title="My Vehicles">
    <section class="form-page-heading vehicle-page-heading"><span class="eyebrow">GARAGE</span><h2>Your protected vehicles</h2><p>Vehicles are added automatically when you submit a claim.</p></section>

    @if($vehicles->isEmpty())
        <section class="panel vehicle-empty"><img src="{{ asset('images/premium-sedan.png') }}" alt="Premium vehicle"><div><span class="eyebrow">NO VEHICLE DETAILS YET</span><h3>Your garage is ready.</h3><p>Your first claimed vehicle will appear here with its registration and model information.</p><a href="{{ route('claims.create') }}" class="button button--dark">Make a claim <span>→</span></a></div></section>
    @else
        <section class="vehicle-list">@foreach($vehicles as $vehicle)<article class="panel vehicle-card"><img src="{{ asset('images/premium-sedan.png') }}" alt="{{ $vehicle->vehicle_make }} {{ $vehicle->vehicle_model }}"><div><span class="status-pill"><i></i> {{ $subscription ? 'PROGRAM ACTIVE' : 'VEHICLE ON FILE' }}</span><h3>{{ $vehicle->vehicle_make }} {{ $vehicle->vehicle_model }}</h3><p>{{ $vehicle->registration_number }}@if($vehicle->vehicle_year) · {{ $vehicle->vehicle_year }}@endif</p><a href="{{ route('claims.show',$vehicle) }}">View latest claim →</a></div></article>@endforeach</section>
    @endif
</x-customer-layout>
