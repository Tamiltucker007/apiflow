<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Merchant;
use App\Models\User;
use App\Services\UserService;
use App\Traits\VerifiesTenantOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

// Team/user management — merchant_admin only, both to view and to manage.
// Unlike Plans/Customers/Subscriptions, this isn't business data staff needs
// read access to; it's who can log in and with what access.
class UserController extends Controller
{
    use VerifiesTenantOwnership;

    public function __construct(private UserService $users) {}

    public function index(Merchant $merchant): View
    {
        return view('users.index', ['merchant' => $merchant]);
    }

    public function data(Merchant $merchant): JsonResponse
    {
        $actingUser = auth()->user();

        return DataTables::of(User::query()->where('merchant_id', $merchant->id))
            ->addColumn('name_display', function (User $user) use ($actingUser) {
                $you = $user->id === $actingUser->id ? ' <span class="text-xs text-gray-400">(you)</span>' : '';

                return e($user->name).$you;
            })
            ->addColumn('role_label', fn (User $user) => $user->role->label())
            ->addColumn('status_badge', function (User $user) {
                $classes = $user->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500';

                return '<span class="px-2 py-0.5 rounded text-xs '.$classes.'">'.($user->is_active ? 'Active' : 'Inactive').'</span>';
            })
            ->addColumn('actions', function (User $user) use ($merchant) {
                $editUrl = route('merchants.users.edit', [$merchant, $user]);
                $toggleUrl = route('merchants.users.toggle', [$merchant, $user]);
                $toggleLabel = $user->is_active ? 'Deactivate' : 'Activate';

                // Only deactivating needs a confirmation — reactivating is
                // harmless and reversible with the same click.
                $toggleConfirm = $user->is_active
                    ? ' data-confirm="'.e("Deactivate {$user->name}? They won't be able to log in again until reactivated.").'"'
                        .' data-confirm-title="Deactivate User" data-confirm-variant="warning" data-confirm-action="Deactivate"'
                    : '';

                return '<div class="flex items-center justify-end gap-1">'
                    .'<a href="'.$editUrl.'" class="action-link action-edit">Edit</a>'
                    .'<form method="POST" action="'.$toggleUrl.'"'.$toggleConfirm.'>'.csrf_field().method_field('PUT')
                    .'<button class="action-link'.($user->is_active ? ' action-danger' : '').'">'.$toggleLabel.'</button></form>'
                    .'</div>';
            })
            ->filterColumn('name_display', fn ($query, $keyword) => $query->where('users.name', 'like', "%{$keyword}%"))
            ->orderColumn('name_display', 'name $1')
            ->rawColumns(['name_display', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create(Merchant $merchant): View
    {
        return view('users.create', ['merchant' => $merchant]);
    }

    public function store(StoreUserRequest $request, Merchant $merchant): RedirectResponse
    {
        $this->users->create($merchant, $request->validated());

        return redirect()->route('merchants.users.index', $merchant)
            ->with('status', 'User created.');
    }

    public function edit(Merchant $merchant, User $user): View
    {
        $this->ensureBelongsToMerchant($user, $merchant);

        return view('users.edit', ['merchant' => $merchant, 'targetUser' => $user]);
    }

    public function update(UpdateUserRequest $request, Merchant $merchant, User $user): RedirectResponse
    {
        $this->ensureBelongsToMerchant($user, $merchant);

        $this->users->update($user, $request->validated());

        return redirect()->route('merchants.users.index', $merchant)
            ->with('status', 'User updated.');
    }

    public function toggle(Merchant $merchant, User $user): RedirectResponse
    {
        $this->ensureBelongsToMerchant($user, $merchant);

        $this->users->toggleActive($user, auth()->user());

        return redirect()->route('merchants.users.index', $merchant)
            ->with('status', $user->is_active ? 'User activated.' : 'User deactivated.');
    }
}
