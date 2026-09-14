<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\FerryTicket;
use App\Models\HotelBooking;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $role = trim($request->string('role')->toString());

        $filters = [
            'search' => $search !== '' ? $search : null,
            'role' => $role !== '' ? $role : null,
        ];

        $users = User::query()
            ->with('role')
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'], function (Builder $query, string $role): void {
                $query->whereHas('role', function (Builder $query) use ($role): void {
                    $query->where('name', $role);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::query()->orderBy('label')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User,
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = new User;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->password = Hash::make($validated['password']);
        $user->role_id = $validated['role_id'];
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        $user->load('role');

        $activityCounts = [
            'hotel_bookings' => HotelBooking::query()->where('user_id', $user->id)->count(),
            'ferry_tickets' => FerryTicket::query()->where('user_id', $user->id)->count(),
            'payments' => Payment::query()->where('user_id', $user->id)->count(),
        ];

        return view('admin.users.show', [
            'user' => $user,
            'activityCounts' => $activityCounts,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $newRoleId = (int) $validated['role_id'];

        if ($user->role_id !== $newRoleId && $this->isLastActiveAdmin($user)) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'The last active admin cannot be assigned another role.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->role_id = $newRoleId;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account updated.');
    }

    /**
     * Deactivate an account while preserving the last active administrator.
     */
    public function deactivate(User $user): RedirectResponse
    {
        if ($this->isLastActiveAdmin($user)) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'The last active admin cannot be deactivated.');
        }

        $user->is_active = false;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account deactivated.');
    }

    /**
     * Reactivate a previously deactivated account.
     */
    public function activate(User $user): RedirectResponse
    {
        $user->is_active = true;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User account activated.');
    }

    /**
     * Return the fixed roles used by user account forms.
     */
    private function roles(): Collection
    {
        return Role::query()->orderBy('label')->get();
    }

    /**
     * Determine whether this account is the sole active administrator.
     */
    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->is_active || ! $user->role()->where('name', 'admin')->exists()) {
            return false;
        }

        return User::query()
            ->where('is_active', true)
            ->whereHas('role', fn (Builder $query) => $query->where('name', 'admin'))
            ->count() === 1;
    }

    /*
     * destroy() is deliberately absent, and no destroy route is registered.
     * hotel_bookings.user_id, payments.user_id and ferry_tickets.user_id are all
     * ON DELETE RESTRICT, so MySQL refuses to delete any user who has ever booked
     * or paid. Deactivation through users.is_active is the mechanism instead - it
     * is why the column was added, per UC-17. BUILD_CONTRACT.md §3 still ticks
     * destroy for users; that tick contradicts its own rationale and the built
     * database, and is being corrected. The replacement is a named POST,
     * /admin/users/{user}/deactivate -> deactivate(), written in Sprint 3.
     */
}
