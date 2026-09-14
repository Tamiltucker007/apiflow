<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdatePasswordRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        return view('portal.profile', ['customer' => $customer]);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        // Customer::password casts as 'hashed' — the model hashes this itself.
        $customer->update(['password' => $request->validated('password')]);

        return redirect()->route('profile')->with('status', 'Password updated.');
    }
}
