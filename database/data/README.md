# Vehicle catalog source

`vehiclesdb-2026-09-1.csv` is the open VehiclesDB 2026.09.1 snapshot from
https://github.com/vehiclesdb/vehiclesdb/blob/main/dist/vehicles.csv
(SHA-256 `5F2347D25FCCC0937B2C4D142B3EFAC8EA5DBDDAB162C48F2FA51455542519A8`).
The source license is CC-BY 4.0. Its required upstream notices are in
`VEHICLE_DATA_ATTRIBUTION.md`.

The catalog contains make and model names from official sources in 14 countries.
It is broad, but it is not a complete worldwide list. The source has no verified
per-model year span, photograph, or paint-panel area measurements. The import
therefore leaves years, photo paths, and min/max m² empty rather than inventing
facts. Admins can supply those details for individual models.

Run `php artisan db:seed --class=VehicleCatalogSeeder` to import or safely rerun
the snapshot. Existing manually entered models and their photos/panels are kept.
