<x-admin-layout title="Vehicles">
    <section class="welcome-row"><div><h2>Customer vehicles</h2><p>View vehicles registered through customer claim requests.</p></div><span class="vehicle-count-badge">{{ $vehicles->total() }} {{ Str::plural('vehicle', $vehicles->total()) }}</span></section>

    <form class="customer-search" method="GET">
        <div class="field__control"><input name="search" value="{{ request('search') }}" placeholder="Search registration, make, model or customer"></div>
        <button class="button button--dark">Search</button>
        @if(request('search'))<a href="{{ route('admin.vehicles.index') }}">Clear</a>@endif
    </form>

    <div class="admin-table-wrap"><table class="admin-table vehicle-admin-table">
        <thead><tr><th>Vehicle</th><th>Registration</th><th>Customer</th><th>Protection plan</th><th>Latest claim</th><th>Added</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($vehicles as $vehicle)
            <tr>
                <td><span class="vehicle-table-icon">▱</span><span><b>{{ $vehicle->vehicle_make }} {{ $vehicle->vehicle_model }}</b><small>{{ $vehicle->vehicle_year ?: 'Model year not provided' }}</small></span></td>
                <td><b class="registration-plate">{{ $vehicle->registration_number }}</b></td>
                <td><span><b>{{ $vehicle->user->name }}</b><small>{{ $vehicle->user->email }}</small></span></td>
                <td>{{ $vehicle->subscription?->plan?->name ?: 'No active plan' }}</td>
                <td><span class="claim-status claim-status--{{ $vehicle->status }}">{{ ucfirst($vehicle->status) }}</span></td>
                <td>{{ $vehicle->created_at->format('d M Y') }}</td>
                <td><a class="icon-action" href="{{ route('admin.claims.show',$vehicle) }}" title="View latest claim" aria-label="View latest claim"><x-eye-icon/></a></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="vehicle-admin-empty"><b>No vehicles found</b><span>Vehicles appear here after customers submit their first claim.</span></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($vehicles->hasPages())<div class="admin-pagination">{{ $vehicles->links() }}</div>@endif
</x-admin-layout>
