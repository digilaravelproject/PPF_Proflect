<x-admin-layout title="QR Code">
    <section class="form-page-heading">
        <span class="eyebrow">MASTERS</span>
        <h2>Customer QR access</h2>
        <p>Display the permanent entry QR code and control whether customers must scan it before opening the site.</p>
    </section>

    <div class="admin-qr-grid">
        <section class="panel admin-qr-preview" aria-labelledby="admin-qr-title">
            <div>
                <span class="eyebrow">PERMANENT QR CODE</span>
                <h3 id="admin-qr-title">Scan to open Proflect</h3>
                <p>This QR remains the same while the application URL and key remain unchanged.</p>
            </div>
            <canvas
                class="admin-qr-code"
                data-qr-code
                data-qr-url="{{ $entryUrl }}"
                role="img"
                aria-label="Customer entry QR code"
            ></canvas>
            <p class="qr-access-error" data-qr-error hidden>QR code could not be displayed. Please refresh this page.</p>
        </section>

        <form class="admin-form panel admin-qr-settings" method="POST" action="{{ route('admin.masters.qr-code.update') }}">
            @csrf @method('PUT')
            <div>
                <span class="eyebrow">ACCESS CONTROL</span>
                <h3>Require QR scan</h3>
                <p>When enabled, direct customer URLs return to the QR screen until the QR is scanned. Admin pages remain available.</p>
            </div>
            <input type="hidden" name="qr_access_enabled" value="0">
            <label class="qr-toggle" for="qr-access-enabled">
                <span>
                    <b>QR-only access</b>
                    <small data-qr-toggle-label>{{ $qrAccessEnabled ? 'Enabled' : 'Disabled' }}</small>
                </span>
                <input id="qr-access-enabled" type="checkbox" name="qr_access_enabled" value="1" @checked($qrAccessEnabled) data-qr-access-toggle>
                <i aria-hidden="true"></i>
            </label>
            <button class="button button--dark">Save QR access setting <span>→</span></button>
        </form>
    </div>
</x-admin-layout>
