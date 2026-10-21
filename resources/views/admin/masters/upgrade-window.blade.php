<x-admin-layout title="Upgrade Window">
    <section class="form-page-heading">
        <span class="eyebrow">MASTERS</span>
        <h2>Plan upgrade window</h2>
        <p>Choose how long customers may upgrade after their warranty card activation date.</p>
    </section>
    <form class="admin-form panel" method="POST" action="{{ route('admin.masters.upgrade-window.update') }}">
        @csrf @method('PUT')
        <div class="field">
            <label for="upgrade-window-months">Upgradation time</label>
            <div class="field__control"><select id="upgrade-window-months" name="upgrade_window_months" required>
                @foreach(range(1, 12) as $month)<option value="{{ $month }}" @selected((int) old('upgrade_window_months', $upgradeWindowMonths) === $month)>{{ $month }} {{ Str::plural('month', $month) }}</option>@endforeach
            </select></div>
            @error('upgrade_window_months')<p class="field__error">{{ $message }}</p>@enderror
        </div>
        <p>Example: a card activated on 21 August with a 2-month window can be upgraded through 21 October.</p>
        <button class="button button--dark">Save upgrade window <span>→</span></button>
    </form>
</x-admin-layout>
