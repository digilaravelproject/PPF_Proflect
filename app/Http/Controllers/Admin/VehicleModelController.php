<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Support\VehiclePanelPresets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehicleModelController extends Controller
{
    public function create(VehicleMake $vehicle): View
    {
        $kind = request()->query('kind', 'car');
        if (! isset(VehiclePanelPresets::NAMES[$kind])) $kind = 'car';

        return view('admin.vehicles.model-form', ['vehicle' => $vehicle, 'model' => new VehicleModel, 'selectedKind' => $kind, 'defaultPanels' => VehiclePanelPresets::NAMES[$kind]]);
    }

    public function store(Request $request, VehicleMake $vehicle): RedirectResponse
    {
        $data = $this->validated($request, $vehicle);
        $path = $request->hasFile('photo') ? $request->file('photo')->store('vehicle-models', 'public') : null;
        try {
            DB::transaction(function () use ($vehicle, $data, $request, $path): void {
                $model = $vehicle->models()->create(['name' => $data['name'], 'kind' => $data['kind'], 'photo_path' => $path, 'is_active' => $request->boolean('is_active')]);
                $this->savePanels($model, $data['panels']);
            });
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('public')->delete($path);
            throw $exception;
        }

        return redirect()->route('admin.vehicles.show', $vehicle)->with('status', 'Vehicle model added.');
    }

    public function show(VehicleMake $vehicle, VehicleModel $model): View
    {
        $this->assertParent($vehicle, $model);

        return view('admin.vehicles.model-show', ['vehicle' => $vehicle, 'model' => $model->load('panels')]);
    }

    public function edit(VehicleMake $vehicle, VehicleModel $model): View
    {
        $this->assertParent($vehicle, $model);

        return view('admin.vehicles.model-form', ['vehicle' => $vehicle, 'model' => $model->load('panels'), 'selectedKind' => $model->kind, 'defaultPanels' => VehiclePanelPresets::NAMES[$model->kind] ?? Claim::PANELS]);
    }

    public function update(Request $request, VehicleMake $vehicle, VehicleModel $model): RedirectResponse
    {
        $this->assertParent($vehicle, $model);
        $data = $this->validated($request, $vehicle, $model);
        $newPath = $request->hasFile('photo') && ! $request->boolean('remove_photo') ? $request->file('photo')->store('vehicle-models', 'public') : null;
        $oldPath = $model->photo_path;
        try {
            DB::transaction(function () use ($model, $data, $request, $newPath): void {
                $model->update(['name' => $data['name'], 'photo_path' => $request->boolean('remove_photo') ? null : ($newPath ?? $model->photo_path), 'is_active' => $request->boolean('is_active')]);
                $model->panels()->delete();
                $this->savePanels($model, $data['panels']);
            });
        } catch (\Throwable $exception) {
            if ($newPath) Storage::disk('public')->delete($newPath);
            throw $exception;
        }
        if (($newPath || $request->boolean('remove_photo')) && $oldPath) Storage::disk('public')->delete($oldPath);

        return redirect()->route('admin.vehicles.models.show', [$vehicle, $model])->with('status', 'Vehicle model updated.');
    }

    public function toggle(VehicleMake $vehicle, VehicleModel $model): RedirectResponse
    {
        $this->assertParent($vehicle, $model);
        $model->update(['is_active' => ! $model->is_active]);

        return back()->with('status', 'Vehicle model status updated.');
    }

    public function destroy(VehicleMake $vehicle, VehicleModel $model): RedirectResponse
    {
        $this->assertParent($vehicle, $model);
        $path = $model->photo_path;
        $model->delete();
        if ($path) Storage::disk('public')->delete($path);

        return redirect()->route('admin.vehicles.show', $vehicle)->with('status', 'Vehicle model deleted.');
    }

    private function validated(Request $request, VehicleMake $vehicle, ?VehicleModel $model = null): array
    {
        $request->merge(['kind' => $request->input('kind', $model?->kind ?? 'car')]);
        $kind = $request->input('kind', $model?->kind ?? 'car');
        $nameRule = Rule::unique('vehicle_models', 'name')->where('vehicle_make_id', $vehicle->id)->where('kind', $kind);
        if ($model) $nameRule->ignore($model->id);

        return $request->validate([
            'name' => ['required', 'string', 'max:100', $nameRule],
            'kind' => ['required', Rule::in($model ? [$model->kind] : array_keys(VehiclePanelPresets::NAMES))],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
            'panels' => ['required', 'array', 'min:1'],
            'panels.*.name' => ['required', 'string', 'max:100'],
            'panels.*.min_sqm' => ['required', 'numeric', 'min:0', 'max:999999'],
            'panels.*.max_sqm' => ['required', 'numeric', 'gte:panels.*.min_sqm', 'max:999999'],
            'panels.*.photo_polygon' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function savePanels(VehicleModel $model, array $panels): void
    {
        $keys = [];
        foreach ($panels as $panel) {
            $key = Str::slug($panel['name'], '_');
            if (! $key || in_array($key, $keys, true)) {
                throw ValidationException::withMessages(['panels' => 'Panel names must be unique.']);
            }
            $keys[] = $key;
            $polygon = $this->parsePolygon($panel['photo_polygon'] ?? null);
            $model->panels()->create(['key' => $key, 'name' => $panel['name'], 'min_sqm' => $panel['min_sqm'], 'max_sqm' => $panel['max_sqm'], 'photo_polygon' => $polygon]);
        }
    }

    private function parsePolygon(?string $value): ?array
    {
        if ($value === null || $value === '') return null;
        $points = json_decode($value, true);
        if (! is_array($points) || count($points) !== 4) {
            throw ValidationException::withMessages(['panels' => 'Mark exactly four corners for each photo area.']);
        }
        foreach ($points as $point) {
            if (! is_array($point) || ! isset($point['x'], $point['y']) || ! is_numeric($point['x']) || ! is_numeric($point['y'])
                || $point['x'] < 0 || $point['x'] > 100 || $point['y'] < 0 || $point['y'] > 100) {
                throw ValidationException::withMessages(['panels' => 'Photo area corners must lie within the photo.']);
            }
        }
        $cross = fn ($a, $b, $c) => ($b['x'] - $a['x']) * ($c['y'] - $a['y']) - ($b['y'] - $a['y']) * ($c['x'] - $a['x']);
        $area = 0;
        for ($i = 0; $i < 4; $i++) $area += $points[$i]['x'] * $points[($i + 1) % 4]['y'] - $points[($i + 1) % 4]['x'] * $points[$i]['y'];
        $intersects = fn ($a, $b, $c, $d) => $cross($a, $b, $c) * $cross($a, $b, $d) < 0
            && $cross($c, $d, $a) * $cross($c, $d, $b) < 0;
        if (abs($area) < 1 || $intersects($points[0], $points[1], $points[2], $points[3])
            || $intersects($points[1], $points[2], $points[3], $points[0])) {
            throw ValidationException::withMessages(['panels' => 'Click the four corners around the panel in order to form one area.']);
        }
        return array_map(fn ($point) => ['x' => round((float) $point['x'], 2), 'y' => round((float) $point['y'], 2)], $points);
    }

    private function assertParent(VehicleMake $vehicle, VehicleModel $model): void
    {
        abort_unless($model->vehicle_make_id === $vehicle->id, 404);
    }
}
