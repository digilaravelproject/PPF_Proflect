<?php

namespace Database\Seeders;

use App\Support\VehiclePanelPresets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class VehicleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/vehiclesdb-2026-09-1.csv');
        if (! is_file($path)) throw new RuntimeException('Vehicle catalog CSV is missing.');
        $manifest = json_decode(file_get_contents(database_path('data/vehiclesdb-manifest.json')), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['version'] ?? null) !== '2026.09.1') throw new RuntimeException('Vehicle catalog version does not match the bundled CSV.');

        $file = fopen($path, 'rb');
        if (! $file) throw new RuntimeException('Vehicle catalog CSV cannot be opened.');
        $headers = fgetcsv($file);
        $required = ['kind', 'make_slug', 'make_name', 'model_slug', 'model_name', 'body_types'];
        if (! $headers || array_diff($required, $headers)) throw new RuntimeException('Vehicle catalog CSV has unexpected columns.');

        $records = [];
        $makes = [];
        while (($values = fgetcsv($file)) !== false) {
            if (count($values) !== count($headers)) continue;
            $row = array_combine($headers, $values);
            if (! isset(VehiclePanelPresets::NAMES[$row['kind']]) || ! $row['make_name'] || ! $row['model_name']) continue;
            $records[] = $row;
            $makes[Str::lower($row['make_name'])] = $row['make_name'];
        }
        fclose($file);

        $now = now();
        foreach (array_chunk(array_values($makes), 400) as $chunk) {
            DB::table('vehicle_makes')->insertOrIgnore(array_map(fn ($name) => [
                'name' => $name, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
        }
        $makeIds = DB::table('vehicle_makes')->pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [Str::lower($name) => $id]);
        $batch = [];
        foreach ($records as $row) {
            $makeId = $makeIds[Str::lower($row['make_name'])] ?? null;
            if (! $makeId) continue;
            $batch[] = [
                'vehicle_make_id' => $makeId,
                'name' => $row['model_name'],
                'kind' => $row['kind'],
                'catalog_key' => $row['kind'].'/'.$row['make_slug'].'/'.$row['model_slug'],
                'body_types' => $row['body_types'] ?: null,
                'photo_path' => null,
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ];
            if (count($batch) >= 400) {
                DB::table('vehicle_models')->insertOrIgnore($batch);
                $batch = [];
            }
        }
        if ($batch) DB::table('vehicle_models')->insertOrIgnore($batch);

        $panelBatch = [];
        foreach (DB::table('vehicle_models')->whereNotNull('catalog_key')->select('id', 'kind')->cursor() as $model) {
            foreach (VehiclePanelPresets::NAMES[$model->kind] as $name) {
                $panelBatch[] = [
                    'vehicle_model_id' => $model->id, 'key' => Str::slug($name, '_'), 'name' => $name,
                    'min_sqm' => null, 'max_sqm' => null, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            if (count($panelBatch) >= 800) {
                DB::table('vehicle_model_panels')->insertOrIgnore($panelBatch);
                $panelBatch = [];
            }
        }
        if ($panelBatch) DB::table('vehicle_model_panels')->insertOrIgnore($panelBatch);

        $this->command?->info('Vehicle catalog imported: '.DB::table('vehicle_makes')->count().' makes, '.DB::table('vehicle_models')->count().' models.');
    }
}
