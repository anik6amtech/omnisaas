<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate();

        if (! Auth::guard('web')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            $this->addError('email', 'These credentials do not match our records.');

            return null;
        }

        session()->regenerate();

        return $this->redirect(route('app.inbox'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
