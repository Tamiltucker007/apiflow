<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\View\View;

// Public entry point for a customer who doesn't already have a merchant's
// direct signup link (see routes/customer.php) — lets them pick which
// merchant they're signing up under before landing on that merchant's own
// /register/{slug} form. A merchant's own direct link still works too.
class RegistrationLandingController extends Controller
{
    public function index(): View
    {
        return view('portal.register-landing', [
            'merchants' => Merchant::active()->orderBy('name')->get(),
        ]);
    }
}
