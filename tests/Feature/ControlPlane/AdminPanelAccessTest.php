<?php

use App\Models\AdminUser;

it('renders the operator login page', function () {
    $this->get('/admin/login')->assertOk();
});

it('lets an active operator reach the control panel', function () {
    $operator = AdminUser::factory()->create();

    $this->actingAs($operator, 'admin')
        ->get('/admin')
        ->assertOk();
});

it('denies panel access to inactive operators', function () {
    $operator = AdminUser::factory()->inactive()->create();

    expect($operator->canAccessPanel(Filament\Facades\Filament::getPanel('admin')))->toBeFalse();
});
