<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Claim;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WarrantyCode;
use Database\Seeders\VehicleCatalogSeeder;
use App\Notifications\AdminResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_manages_catalog_and_customer_claim_uses_model_panels(): void
    {
        Storage::fake('public');
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Password123']);
        $this->actingAs($admin, 'admin')->post(route('admin.vehicles.store'), ['name' => 'Toyota', 'is_active' => 1])->assertRedirect();
        $make = VehicleMake::firstOrFail();
        $this->actingAs($admin, 'admin')->post(route('admin.vehicles.models.store', $make), [
            'name' => 'Fortuner', 'is_active' => 1, 'coverage_sqm' => 4.5, 'photo' => UploadedFile::fake()->image('fortuner.jpg'),
            'panels' => [
                ['name' => 'Front Bumper', 'min_sqm' => 2, 'max_sqm' => 4, 'photo_polygon' => '[{"x":45,"y":55},{"x":70,"y":55},{"x":68,"y":75},{"x":43,"y":75}]'],
                ['name' => 'Roof', 'min_sqm' => 1, 'max_sqm' => 3],
            ],
        ])->assertRedirect(route('admin.vehicles.show', $make));
        $model = VehicleModel::with('panels')->firstOrFail();
        $this->actingAs($admin, 'admin')->get(route('admin.vehicles.index'))->assertOk()
            ->assertSee('role="switch"', false)->assertSee('aria-label="View Toyota"', false)
            ->assertSee('aria-label="Edit Toyota"', false)->assertSee('aria-label="Delete Toyota"', false);
        $this->actingAs($admin, 'admin')->get(route('admin.vehicles.show', $make))->assertOk()
            ->assertSee('aria-label="Deactivate Toyota Fortuner"', false)
            ->assertSee('aria-label="View Fortuner"', false);
        Storage::disk('public')->assertExists($model->photo_path);
        $this->actingAs($admin, 'admin')->get(route('admin.vehicles.models.show', [$make, $model]))->assertOk()->assertSee('Front Bumper')->assertSee('4.50');
        $this->actingAs($admin, 'admin')->get(route('admin.vehicles.models.edit', [$make, $model]))->assertOk()
            ->assertSee('data-photo-preview', false)->assertSee('data-remove-model-photo', false)->assertSee('data-draw-panel-area', false)
            ->assertSee('Model area limit')->assertDontSee('Min m²')->assertDontSee('Max m²');
        $this->get(route('catalog.models.photo', $model))->assertOk();

        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_years' => 1, 'coverage_sqm' => 5, 'is_active' => true]);
        Subscription::create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addYear()]);
        $code = WarrantyCode::where('is_active', true)->whereNull('used_at')->firstOrFail();
        $this->actingAs($user)->get(route('claims.create'))->assertOk()->assertSee('Toyota');
        $this->actingAs($user)->get(route('catalog.make.models', $make))->assertOk()->assertSee('Fortuner');
        $this->actingAs($user)->get(route('catalog.model.show', $model))->assertOk()->assertSee('Front Bumper')
            ->assertJsonPath('coverage_sqm', 4.5)
            ->assertJsonPath('panels.0.photo_polygon.0.x', 45)->assertJsonPath('panels.0.photo_polygon.3.y', 75);
        $this->actingAs($user)->post(route('claims.store'), [
            'warranty_code' => $code->code, 'vehicle_make_id' => $make->id, 'vehicle_model_id' => $model->id,
            'registration_number' => 'MH12AB1234', 'panels' => ['unknown_panel'],
            'photos' => [UploadedFile::fake()->image('damage.jpg')],
        ])->assertSessionHasErrors('panels');
        $this->actingAs($user)->post(route('claims.store'), [
            'warranty_code' => $code->code, 'vehicle_make_id' => $make->id, 'vehicle_model_id' => $model->id,
            'registration_number' => 'MH12AB1234', 'panels' => ['front_bumper'],
            'photos' => [UploadedFile::fake()->image('damage.jpg')],
        ])->assertRedirect();
        $claim = Claim::firstOrFail();
        $this->assertSame($model->id, $claim->vehicle_model_id);
        $this->assertSame('Toyota', $claim->vehicle_make);
        $this->assertSame('Fortuner', $claim->vehicle_model);
        $this->assertSame('4.50', $claim->model_coverage_sqm);
        $this->assertSame(4, count($claim->panel_details[0]['photo_polygon']));
        $this->assertNotNull($claim->vehicle_model_photo_path);
        Storage::disk('public')->assertExists($claim->vehicle_model_photo_path);
        $this->actingAs($admin, 'admin')->get(route('admin.claims.show', $claim))->assertOk()
            ->assertSee(route('admin.claims.model-photo', $claim), false)
            ->assertSee('45,55 70,55 68,75 43,75', false)
            ->assertDontSee('claim-car-top-light.png');
        $this->actingAs($admin, 'admin')->get(route('admin.claims.model-photo', $claim))->assertOk();
        $snapshotPath = $claim->vehicle_model_photo_path;
        $claim->update(['vehicle_model_photo_path' => null]);
        $this->actingAs($admin, 'admin')->get(route('admin.claims.show', $claim))->assertOk()
            ->assertSee(route('catalog.models.photo', $model), false)
            ->assertSee('45,55 70,55 68,75 43,75', false);
        $claim->update(['vehicle_model_photo_path' => $snapshotPath]);

        $this->actingAs($admin, 'admin')->patch(route('admin.vehicles.models.toggle', [$make, $model]))->assertRedirect();
        $this->actingAs($user)->get(route('catalog.make.models', $make))->assertOk()->assertDontSee('Fortuner');
        $this->actingAs($admin, 'admin')->put(route('admin.vehicles.models.update', [$make, $model]), [
            'name' => 'Fortuner 4x4', 'is_active' => 1, 'coverage_sqm' => 5,
            'panels' => [['name' => 'Front Bumper', 'min_sqm' => 2.5, 'max_sqm' => 4.5]],
        ])->assertRedirect(route('admin.vehicles.models.show', [$make, $model]));
        $this->assertDatabaseHas('vehicle_models', ['id' => $model->id, 'name' => 'Fortuner 4x4', 'is_active' => true]);
        $this->assertDatabaseHas('vehicle_model_panels', ['vehicle_model_id' => $model->id, 'name' => 'Front Bumper', 'min_sqm' => null]);
        $this->actingAs($admin, 'admin')->delete(route('admin.vehicles.models.destroy', [$make, $model]))->assertRedirect(route('admin.vehicles.show', $make));
        Storage::disk('public')->assertMissing($model->photo_path);
        $this->assertSame('Fortuner', $claim->fresh()->vehicle_model);
        $this->assertNull($claim->fresh()->vehicle_model_id);
        Storage::disk('public')->assertExists($claim->vehicle_model_photo_path);
        $this->actingAs($admin, 'admin')->get(route('admin.claims.show', $claim))->assertOk()
            ->assertSee(route('admin.claims.model-photo', $claim), false);
        $this->actingAs($admin, 'admin')->delete(route('admin.claims.destroy', $claim))->assertRedirect();
        Storage::disk('public')->assertMissing($snapshotPath);
    }

    public function test_admin_can_remove_a_saved_model_photo(): void
    {
        Storage::fake('public');
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Password123']);
        $make = VehicleMake::create(['name' => 'Abarth', 'is_active' => true]);
        $model = $make->models()->create(['name' => '500', 'kind' => 'car', 'is_active' => true,
            'photo_path' => UploadedFile::fake()->image('car.jpg')->store('vehicle-models', 'public')]);
        $oldPath = $model->photo_path;
        $this->actingAs($admin, 'admin')->put(route('admin.vehicles.models.update', [$make, $model]), [
            'name' => '500', 'kind' => 'car', 'is_active' => 1, 'remove_photo' => 1, 'coverage_sqm' => 5,
            'panels' => [['name' => 'Bonnet', 'min_sqm' => 1, 'max_sqm' => 2, 'photo_polygon' => '[{"x":30,"y":20},{"x":60,"y":20},{"x":65,"y":45},{"x":25,"y":45}]']],
        ])->assertRedirect();
        $this->assertNull($model->fresh()->photo_path);
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertSame(4, count($model->panels()->firstOrFail()->photo_polygon));
        $this->get(route('catalog.models.photo', $model))->assertNotFound();
    }

    public function test_panel_photo_area_requires_four_ordered_corners(): void
    {
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Password123']);
        $make = VehicleMake::create(['name' => 'Abarth', 'is_active' => true]);
        $this->actingAs($admin, 'admin')->post(route('admin.vehicles.models.store', $make), [
            'name' => '500', 'kind' => 'car', 'is_active' => 1, 'coverage_sqm' => 5,
            'panels' => [['name' => 'Bonnet', 'min_sqm' => 1, 'max_sqm' => 2,
                'photo_polygon' => '[{"x":20,"y":20},{"x":80,"y":80},{"x":80,"y":20},{"x":20,"y":80}]']],
        ])->assertSessionHasErrors('panels');
        $this->assertDatabaseMissing('vehicle_models', ['name' => '500']);
    }

    public function test_admin_reset_link_uses_admin_broker_and_legal_pages_are_available(): void
    {
        Notification::fake();
        $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Password123']);
        $this->get(route('admin.password.request'))->assertOk();
        $this->post(route('admin.password.email'), ['email' => $admin->email])->assertSessionHas('status');
        Notification::assertSentTo($admin, AdminResetPasswordNotification::class);
        $token = Password::broker('admins')->createToken($admin);
        $this->post(route('admin.password.update'), [
            'token' => $token, 'email' => $admin->email,
            'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('admin.login'));
        $this->get(route('terms'))->assertOk()->assertSee('Terms of Service');
        $this->get(route('privacy'))->assertOk()->assertSee('Privacy Policy');
    }

    public function test_bundled_global_catalog_import_is_repeatable_and_preserves_unknown_measurements(): void
    {
        $this->seed(VehicleCatalogSeeder::class);
        $this->assertGreaterThan(14000, VehicleModel::count());
        $this->assertGreaterThan(900, VehicleMake::count());
        $fortuner = VehicleModel::where('catalog_key', 'car/toyota/fortuner')->firstOrFail();
        $this->assertSame('Toyota', $fortuner->make->name);
        $this->assertNull($fortuner->photo_path);
        $this->assertNull($fortuner->panels()->firstOrFail()->min_sqm);
        $count = VehicleModel::count();
        $this->seed(VehicleCatalogSeeder::class);
        $this->assertSame($count, VehicleModel::count());
        $this->get(route('vehicle-data.credits'))->assertOk()->assertSee('VehiclesDB');
    }

    public function test_literal_route_links_in_views_and_app_resolve(): void
    {
        $missing = [];
        foreach (array_merge(File::allFiles(resource_path('views')), File::allFiles(app_path())) as $file) {
            preg_match_all('/\broute\(\s*[\'\"]([^\'\"]+)[\'\"]/', File::get($file->getPathname()), $matches);
            foreach ($matches[1] as $name) {
                if (! Route::has($name)) $missing[] = $file->getRelativePathname().': '.$name;
            }
        }
        $this->assertSame([], $missing);
    }
}
