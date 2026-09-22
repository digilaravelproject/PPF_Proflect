<?php

namespace App\Http\Controllers;

use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Http\JsonResponse;

class VehicleCatalogController extends Controller
{
    public function models(VehicleMake $make): JsonResponse
    {
        abort_unless($make->is_active, 404);

        return response()->json($make->models()->where('is_active', true)
            ->orderBy('name')->get(['id', 'vehicle_make_id', 'name', 'kind'])
            ->map(fn (VehicleModel $model) => ['id' => $model->id, 'name' => $model->name, 'kind' => $model->kind]));
    }

    public function show(VehicleModel $model): JsonResponse
    {
        abort_unless($model->is_active && $model->make->is_active, 404);

        return response()->json([
            'id' => $model->id, 'name' => $model->name, 'kind' => $model->kind,
            'make_name' => $model->make->name, 'photo_url' => $model->photo_url,
            'panels' => $model->panels()->orderBy('id')->get(['key', 'name', 'min_sqm', 'max_sqm', 'photo_polygon']),
        ]);
    }
}
