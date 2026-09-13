<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // Lists every merchant for the super admin landing page.
    public function admin(): View
    {
        return view('dashboard', [
            'title' => 'All Merchants ('.Merchant::count().')',
        ]);
    }

    // Renders the given merchant's dashboard shell.
    public function merchant(Merchant $merchant): View
    {
        return view('dashboard', [
            'title' => "Dashboard — {$merchant->name}",
        ]);
    }
}
