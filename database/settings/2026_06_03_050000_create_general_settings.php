<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.brand_name', 'OmniReply');
        $this->migrator->add('general.support_email', 'support@omnireply.test');
        $this->migrator->add('general.default_locale', 'en');
        $this->migrator->add('general.default_timezone', 'Asia/Dhaka');
        $this->migrator->add('general.maintenance_mode', false);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('general.brand_name');
        $this->migrator->deleteIfExists('general.support_email');
        $this->migrator->deleteIfExists('general.default_locale');
        $this->migrator->deleteIfExists('general.default_timezone');
        $this->migrator->deleteIfExists('general.maintenance_mode');
    }
};
