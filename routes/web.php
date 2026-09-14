<?php

use Illuminate\Support\Facades\Route;

// Two separate login systems: customer.php (guard "customer", unprefixed
// URLs) and admin.php (guard "web", "/admin" prefix + "admin." names).
require __DIR__.'/customer.php';
require __DIR__.'/admin.php';

// Not named 'home' — Laravel's RedirectIfAuthenticated would treat that as
// its default post-login target and could loop back into this redirect.
Route::redirect('/', '/login');
