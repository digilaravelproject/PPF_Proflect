<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        $claims = $request->user()->claims()->with('subscription.plan')->latest()->paginate(10);

        return view('claims.index', compact('claims'));
    }

    public function create(Request $request): View
    {
        $subscription = $this->activeSubscription($request);

        return view('claims.create', ['subscription' => $subscription, 'panels' => Claim::PANELS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $subscription = $this->activeSubscription($request);
        $validated = $request->validate([
            'vehicle_make' => ['required', 'string', 'max:100'],
            'vehicle_model' => ['required', 'string', 'max:100'],
            'registration_number' => ['required', 'string', 'max:30'],
            'vehicle_year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'panels' => ['required', 'array', 'min:1'],
            'panels.*' => ['required', Rule::in(array_keys(Claim::PANELS))],
            'photos' => ['required', 'array', 'min:1', 'max:6'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $paths = [];
        try {
            foreach ($request->file('photos') as $photo) {
                $paths[] = $photo->store('claims/'.$request->user()->id, 'public');
            }

            $claim = Claim::create([
                'claim_number' => $this->claimNumber(),
                'user_id' => $request->user()->id,
                'subscription_id' => $subscription->id,
                'vehicle_make' => $validated['vehicle_make'],
                'vehicle_model' => $validated['vehicle_model'],
                'registration_number' => strtoupper($validated['registration_number']),
                'vehicle_year' => $validated['vehicle_year'] ?? null,
                'panels' => array_values(array_unique($validated['panels'])),
                'photos' => $paths,
                'description' => $validated['description'] ?? null,
                'status' => 'pending',
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($paths);
            throw $exception;
        }

        return redirect()->route('claims.show', $claim)->with('status', 'Your claim was submitted successfully.');
    }

    public function show(Request $request, Claim $claim): View
    {
        abort_unless($claim->user_id === $request->user()->id, 404);

        return view('claims.show', ['claim' => $claim->load('subscription.plan'), 'panels' => Claim::PANELS]);
    }

    public function photo(Request $request, Claim $claim, int $index): StreamedResponse
    {
        abort_unless($claim->user_id === $request->user()->id && isset($claim->photos[$index]), 404);

        return Storage::disk('public')->response($claim->photos[$index]);
    }

    private function activeSubscription(Request $request): Subscription
    {
        return $request->user()->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latest()
            ->firstOrFail();
    }

    private function claimNumber(): string
    {
        do {
            $number = 'CLM-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Claim::where('claim_number', $number)->exists());

        return $number;
    }
}
