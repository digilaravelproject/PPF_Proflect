<?php

namespace App\Support;

use App\Models\WarrantyCodeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class QrAccess
{
    public static function enabled(): bool
    {
        if (! config('qr_access.enabled')) {
            return false;
        }

        if (! Schema::hasTable('warranty_code_settings')
            || ! Schema::hasColumn('warranty_code_settings', 'qr_access_enabled')) {
            return true;
        }

        return (bool) (WarrantyCodeSetting::query()->whereKey(1)->value('qr_access_enabled') ?? true);
    }

    public static function token(): string
    {
        $configuredToken = trim((string) config('qr_access.token'));

        if ($configuredToken !== '') {
            return $configuredToken;
        }

        $appKey = (string) config('app.key');

        if ($appKey === '') {
            throw new RuntimeException('APP_KEY or QR_ACCESS_TOKEN must be configured to use QR access.');
        }

        return hash_hmac('sha256', 'proflect-qr-entry', $appKey);
    }

    public static function entryUrl(): string
    {
        $entryPath = route('qr-access.enter', ['token' => self::token()], false);

        return rtrim((string) config('app.url'), '/').$entryPath;
    }

    public static function grant(Request $request): void
    {
        $request->session()->put((string) config('qr_access.session_key'), true);
        $request->session()->regenerate();
    }
}
