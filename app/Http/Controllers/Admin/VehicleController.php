<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $latestVehicleClaims = Claim::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('user_id', 'registration_number');

        $vehicles = Claim::query()
            ->with(['user', 'subscription.plan'])
            ->whereIn('id', $latestVehicleClaims)
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(function ($query) use ($search): void {
                    $query->where('registration_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_make', 'like', "%{$search}%")
                        ->orWhere('vehicle_model', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.vehicles.index', compact('vehicles'));
    }
}
