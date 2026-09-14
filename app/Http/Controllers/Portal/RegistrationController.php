<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\RegisterRequest;
use App\Models\Merchant;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private CustomerService $customers) {}

    public function create(Merchant $merchant): View
    {
        abort_unless($merchant->is_active, 404);

        return view('portal.register', ['merchant' => $merchant]);
    }

    // Deliberately does not log the customer in — sending them to /portal/login
    // to sign in with the credentials they just chose confirms the password
    // was actually set correctly, the same reason most sign-up flows do this
    // rather than trusting the just-submitted session.
    public function store(RegisterRequest $request, Merchant $merchant): RedirectResponse
    {
        abort_unless($merchant->is_active, 404);

        $this->customers->registerSelf(
            $merchant,
            $request->validated('email'),
            $request->validated('password'),
        );

        return redirect()->route('login')
            ->with('status', 'Account created! Sign in below to continue.');
    }
}
