<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = VehicleMake::withCount('models')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($query) use ($request) {
                $search = '%'.trim($request->search).'%';
                $query->where('name', 'like', $search)->orWhereHas('models', fn ($models) => $models->where('name', 'like', $search));
            }))->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create(): View { return view('admin.vehicles.form', ['vehicle' => new VehicleMake]); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:vehicle_makes,name']]);
        $vehicle = VehicleMake::create($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.vehicles.show', $vehicle)->with('status', 'Vehicle make added. Add its models next.');
    }

    public function show(VehicleMake $vehicle): View
    {
        $models = $vehicle->models()->withCount('panels')
            ->when(request()->filled('search'), fn ($query) => $query->where('name', 'like', '%'.trim(request('search')).'%'))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.vehicles.show', compact('vehicle', 'models'));
    }

    public function edit(VehicleMake $vehicle): View { return view('admin.vehicles.form', compact('vehicle')); }

    public function update(Request $request, VehicleMake $vehicle): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('vehicle_makes', 'name')->ignore($vehicle->id)]]);
        $vehicle->update($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.vehicles.show', $vehicle)->with('status', 'Vehicle make updated.');
    }

    public function toggle(VehicleMake $vehicle): RedirectResponse
    {
        $vehicle->update(['is_active' => ! $vehicle->is_active]);

        return back()->with('status', 'Vehicle make status updated.');
    }

    public function destroy(VehicleMake $vehicle): RedirectResponse
    {
        foreach ($vehicle->models as $model) {
            if ($model->photo_path) Storage::disk('public')->delete($model->photo_path);
        }
        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')->with('status', 'Vehicle make deleted.');
    }
}
