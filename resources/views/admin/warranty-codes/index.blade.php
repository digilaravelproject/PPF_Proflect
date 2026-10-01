<x-admin-layout title="Warranty Codes">
    <section class="welcome-row warranty-admin-heading">
        <div>
            <h2>Warranty codes</h2>
            <p>Generate five-digit registration codes, add a specific code, or import a prepared warranty code sheet.</p>
            <details class="code-create-disclosure" @if($errors->has('manual_code')) open @endif>
                <summary>Add a code manually</summary>
                <form method="POST" action="{{ route('admin.warranty-codes.store-manual') }}" class="manual-code-form">
                    @csrf
                    <div class="field">
                        <label for="manual-code">5-digit code</label>
                        <div class="field__control">
                            <input id="manual-code" name="manual_code" value="{{ old('manual_code') }}" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="22005" required>
                        </div>
                        @error('manual_code')<p class="field__error">{{ $message }}</p>@enderror
                    </div>
                    <button class="button button--dark">Add code <span>+</span></button>
                </form>
            </details>
        </div>

        <div class="warranty-code-controls">
            <form method="POST" action="{{ route('admin.warranty-codes.expiration') }}" class="expiration-setting">
                @csrf
                @method('PATCH')
                <div class="field">
                    <label for="validity-months">Code expiration <small>Applies to all codes</small></label>
                    <div class="field__control">
                        <select id="validity-months" name="validity_months" required onchange="this.form.submit()" aria-label="Code expiration for all warranty codes">
                            @foreach(range(1, 12) as $month)
                                <option value="{{ $month }}" @selected((int) old('validity_months', $validityMonths) === $month)>{{ $month }} {{ Str::plural('month', $month) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('validity_months')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <noscript><button class="button button--dark">Update expiration</button></noscript>
            </form>

            <form method="POST" action="{{ route('admin.warranty-codes.store') }}" class="code-generator">
                @csrf
                <div class="field">
                    <label for="code-count">Generate</label>
                    <div class="field__control">
                        <input id="code-count" type="number" name="count" min="1" max="100" value="{{ old('count', 1) }}" required>
                    </div>
                    @error('count')<p class="field__error">{{ $message }}</p>@enderror
                </div>
                <button class="button button--dark">Add codes <span>+</span></button>
            </form>
        </div>
    </section>

    <section class="panel warranty-sheet-panel">
        <div class="warranty-sheet-panel__copy">
            <span class="eyebrow">BULK IMPORT</span>
            <h3>Warranty code upload sheet</h3>
            <p>Download the sample, keep the <b>Warranty Code</b> heading in cell A1, and enter one five-digit code per row.</p>
            <a class="button button--cancel" href="{{ route('admin.warranty-codes.sample') }}">Download sample sheet</a>
        </div>
        <form method="POST" action="{{ route('admin.warranty-codes.import') }}" enctype="multipart/form-data" class="warranty-sheet-upload">
            @csrf
            <div class="field">
                <label for="warranty-sheet">Upload completed sheet</label>
                <div class="field__control field__control--file">
                    <input id="warranty-sheet" type="file" name="warranty_sheet" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </div>
                <small>Excel (.xlsx), up to 2 MB and 5,000 codes.</small>
                @error('warranty_sheet')<p class="field__error">{{ $message }}</p>@enderror
            </div>
            <button class="button button--dark">Upload codes <span>↑</span></button>
        </form>
    </section>

    <form class="admin-filters" method="GET">
        <div class="field"><label>Search</label><div class="field__control"><input name="search" value="{{ request('search') }}" placeholder="Code, customer or email"></div></div>
        <div class="field"><label>Status</label><div class="field__control"><select name="status"><option value="">All statuses</option>@foreach(['available','used','expired','inactive','deleted'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div></div>
        <div class="field"><label>From</label><div class="field__control"><input type="date" name="from" value="{{ request('from') }}"></div></div>
        <div class="field"><label>To</label><div class="field__control"><input type="date" name="to" value="{{ request('to') }}"></div></div>
        <button class="button button--dark">Filter</button>
        <a href="{{ route('admin.warranty-codes.index') }}">Clear</a>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Warranty code</th><th>Status</th><th>Customer</th><th>Setting</th><th>Expires</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($codes as $code)
                    <tr>
                        <td><b class="code-value">{{ $code->code }}</b></td>
                        <td><span class="claim-status claim-status--{{ $code->status }}">{{ ucfirst($code->status) }}</span></td>
                        <td>@if($code->usedBy)<span><b>{{ $code->usedBy->name }}</b><small>{{ $code->usedBy->email }}</small></span>@else — @endif</td>
                        <td>@unless($code->trashed() || $code->used_at)<form method="POST" action="{{ route('admin.warranty-codes.toggle', $code->id) }}">@csrf @method('PATCH')<button class="code-toggle {{ $code->is_active ? 'on' : '' }}" title="{{ $code->is_active ? 'Deactivate' : 'Activate' }} code" aria-label="{{ $code->is_active ? 'Deactivate' : 'Activate' }} code"><i></i></button></form>@else — @endunless</td>
                        <td>{{ $code->expires_at?->format('d M Y') ?: 'Starts on activation' }}</td>
                        <td>{{ $code->created_at->format('d M Y') }}</td>
                        <td><div class="table-actions"><a class="icon-action" href="{{ route('admin.warranty-codes.show', $code->id) }}" title="View code" aria-label="View code"><x-eye-icon/></a>@unless($code->trashed())<form method="POST" action="{{ route('admin.warranty-codes.destroy', $code->id) }}" onsubmit="return confirm('Delete this warranty code?')">@csrf @method('DELETE')<button class="icon-action icon-action--danger" title="Delete code" aria-label="Delete code">×</button></form>@endunless</div></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No warranty codes match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($codes->hasPages())<div class="admin-pagination" style="margin-top: 2% !important;">{{ $codes->links() }}</div>@endif
</x-admin-layout>
