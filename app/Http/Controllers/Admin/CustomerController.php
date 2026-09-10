<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()
            ->with(['subscriptions' => fn ($query) => $query->with('plan')->latest()])
            ->withCount('claims')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('admin.customers.form', ['customer' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request));

        return redirect()->route('admin.customers.index')->with('status', 'Customer created successfully.');
    }

    public function edit(User $customer): View
    {
        return view('admin.customers.form', compact('customer'));
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));

        return redirect()->route('admin.customers.index')->with('status', 'Customer updated successfully.');
    }

    public function destroy(User $customer): RedirectResponse
    {
        DB::transaction(function () use ($customer): void {
            foreach ($customer->claims as $claim) {
                Storage::disk('public')->delete($claim->photos ?? []);
            }
            $customer->claims()->delete();
            $customer->subscriptions()->delete();
            $customer->payments()->delete();
            $customer->delete();
        });

        return back()->with('status', 'Customer deleted.');
    }

    private function validated(Request $request, ?User $customer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($customer)],
            'phone' => ['required', 'string', 'max:20'],
            'password' => [$customer ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'onboarding_completed' => ['nullable', 'boolean'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['onboarding_completed_at'] = $request->boolean('onboarding_completed') ? ($customer?->onboarding_completed_at ?? now()) : null;
        unset($data['onboarding_completed']);

        return $data;
    }
}
