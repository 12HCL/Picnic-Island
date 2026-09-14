<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
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
