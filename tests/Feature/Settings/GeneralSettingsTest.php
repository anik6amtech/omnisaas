<?php

use App\Settings\GeneralSettings;

it('exposes platform-wide general settings with seeded defaults', function () {
    $settings = app(GeneralSettings::class);

    expect($settings->brand_name)->toBe('OmniReply')
        ->and($settings->default_timezone)->toBe('Asia/Dhaka')
        ->and($settings->maintenance_mode)->toBeFalse();
});

it('persists changes to settings', function () {
    $settings = app(GeneralSettings::class);
    $settings->brand_name = 'OmniReply BD';
    $settings->save();

    expect(app(GeneralSettings::class)->brand_name)->toBe('OmniReply BD');
});
