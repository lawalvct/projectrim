<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'text'): void
    {
        Setting::setValue($key, $value, $group, $type);
    }

    public function getGroup(string $group): array
    {
        return Setting::getGroup($group);
    }

    public function all(): array
    {
        return Cache::rememberForever('settings', function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });
    }
}
