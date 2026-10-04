<?php

namespace ME\Ecom\Support;

use Illuminate\Support\Facades\Crypt;
use ME\Models\Setting;
use Throwable;

/**
 * Shop settings stored in metheme's `settings` table under keys prefixed with "ecom_".
 * Secret values (gateway passwords, API keys) are stored encrypted with APP_KEY.
 */
class EcomSettings
{
    private const PREFIX = 'ecom_';

    /** @var array<string, string|null>|null */
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[self::PREFIX.$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function secret(string $key): ?string
    {
        $value = $this->get($key);

        if (blank($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return null; // saved with a different APP_KEY
        }
    }

    public function bool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /**
     * Save values; keys listed in $secretKeys are encrypted, and a blank secret keeps the old value.
     *
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $secretKeys
     */
    public function set(array $values, array $secretKeys = []): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, $secretKeys, true)) {
                if (blank($value)) {
                    continue;
                }
                $value = Crypt::encryptString((string) $value);
            }

            Setting::set(self::PREFIX.$key, $value);
        }

        $this->values = null;
    }

    /**
     * Current raw values of the given keys, for me_change_log()->record().
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function snapshot(array $keys): array
    {
        return Setting::snapshot(array_map(fn ($key) => self::PREFIX.$key, $keys));
    }

    /**
     * @return array<string, string|null>
     */
    private function all(): array
    {
        if ($this->values === null) {
            try {
                $this->values = Setting::where('key', 'like', self::PREFIX.'%')->pluck('value', 'key')->all();
            } catch (Throwable) {
                $this->values = []; // settings table not migrated yet
            }
        }

        return $this->values;
    }
}
