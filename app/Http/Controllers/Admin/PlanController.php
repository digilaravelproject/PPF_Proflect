<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', ['plans' => Plan::orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan]);
    }

    public function store(Request $request): RedirectResponse
    {
        Plan::create($this->validated($request));

        return redirect()->route('admin.plans.index')->with('status', 'Plan created successfully.');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validated($request, $plan));

        return redirect()->route('admin.plans.index')->with('status', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return back()->withErrors(['plan' => 'This plan has subscriptions and cannot be deleted. Deactivate it instead.']);
        }
        $plan->delete();

        return back()->with('status', 'Plan deleted.');
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $request->merge(['price_aud' => $request->input('price_aud', $request->input('price_dollars'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'slug' => ['nullable', 'string', 'max:120', Rule::unique('plans')->ignore($plan)],
            'description' => ['nullable', 'string', 'max:1000'], 'price_aud' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'duration_years' => ['required', 'integer', 'min:0', 'max:20'], 'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'], 'coverage_sqm' => ['required', 'numeric', 'min:0', 'max:9999'],
            'features_text' => ['nullable', 'string', 'max:3000'], 'accent' => ['required', Rule::in(['silver', 'gold', 'black'])],
            'is_active' => ['nullable', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);
        $slug = ($data['slug'] ?? null) ?: Str::slug($data['name']);
        if (Plan::query()->where('slug', $slug)->when($plan, fn ($query) => $query->where('id', '!=', $plan->id))->exists()) {
            throw ValidationException::withMessages(['slug' => 'This plan slug is already in use.']);
        }
        if ((int) $data['duration_years'] === 0 && empty($data['duration_days'])) {
            throw ValidationException::withMessages(['duration_days' => 'Enter a duration in days when duration in years is zero.']);
        }
        if ((float) $data['price_aud'] === 0.0 && empty($data['duration_days'])) {
            throw ValidationException::withMessages(['duration_days' => 'Free plans must have a duration in days.']);
        }

        return ['name' => $data['name'], 'slug' => $slug, 'description' => $data['description'] ?? null,
            'price' => (int) round($data['price_aud'] * 100), 'currency' => 'AUD', 'duration_years' => $data['duration_years'],
            'duration_days' => $data['duration_days'] ?? null,
            'coverage_sqm' => $data['coverage_sqm'], 'features' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['features_text'] ?? '')))),
            'accent' => $data['accent'], 'is_active' => $request->boolean('is_active'), 'sort_order' => $data['sort_order']];
    }
}
