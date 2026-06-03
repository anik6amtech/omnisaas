<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('renders the login page', function () {
    $this->get('/login')->assertOk()->assertSeeLivewire(Login::class);
});

it('authenticates a seller on the web guard and redirects to the inbox', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-pass')]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'secret-pass')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('app.inbox'));

    $this->assertAuthenticatedAs($user, 'web');
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-pass')]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('web');
});
