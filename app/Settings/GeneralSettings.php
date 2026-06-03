<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Platform-wide operational settings, editable from the control plane and read
 * by the tenant plane at runtime — part of the "total dynamic control" engine.
 * One example group for the foundation; provider secrets (encrypted) and more
 * groups (branding, AI defaults, quotas) land in E9.
 */
class GeneralSettings extends Settings
{
    public string $brand_name;

    public string $support_email;

    public string $default_locale;

    public string $default_timezone;

    public bool $maintenance_mode;

    public static function group(): string
    {
        return 'general';
    }
}
