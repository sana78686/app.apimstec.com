<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private function userToArray(User $user): array
    {
        $user->loadMissing('roles:id,name,slug');
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'roles' => $user->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'slug' => $r->slug]),
        ];
    }

    public function index(): Response|JsonResponse
    {
        $users = User::with('roles:id,name,slug')
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => $this->userToArray($user));

        if (request()->is('api/*')) {
            return response()->json(['users' => $users]);
        }
        return Inertia::render('Users/Index', ['users' => $users]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['user' => $this->userToArray($user)]);
    }

    public function create(): Response|JsonResponse
    {
        $roles = Role::orderBy('name')
            ->where('slug', '!=', 'admin')
            ->get(['id', 'name', 'slug', 'is_system']);

        if (request()->is('api/*')) {
            return response()->json(['roles' => $roles]);
        }
        return Inertia::render('Users/Create', ['roles' => $roles]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ], [
            'roles.required' => 'The user must have at least one role.',
            'roles.min' => 'The user must have at least one role.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $roleIds = $request->roles ?? [];
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $roleIds = array_values(array_filter($roleIds, fn ($id) => (int) $id !== (int) $adminRole->id));
        }
        $user->roles()->sync($roleIds);

        if (request()->is('api/*')) {
            return response()->json(['message' => 'User created.', 'user' => $this->userToArray($user)], 201);
        }
        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user): Response|JsonResponse
    {
        $user->load('roles:id,name,slug,is_system');
        $isDesignatedAdmin = $this->isDesignatedAdmin($user);
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug', 'is_system']);
        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn ($r) => $r->id),
            ],
            'roles' => $roles,
            'is_designated_admin' => $isDesignatedAdmin,
        ];
        if (request()->is('api/*')) {
            return response()->json($payload);
        }
        return Inertia::render('Users/Edit', $payload);
    }

    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|'.Rule::unique('users', 'email')->ignore($user->id),
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
        ], [
            'roles.required' => 'The user must have at least one role.',
            'roles.min' => 'The user must have at least one role.',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);

        $adminRole = Role::where('slug', 'admin')->first();
        $newRoleIds = $request->roles ?? [];
        if ($this->isDesignatedAdmin($user)) {
            if ($adminRole) {
                $newRoleIds = array_values(array_unique(array_merge($newRoleIds, [$adminRole->id])));
            }
        } else {
            if ($adminRole) {
                $newRoleIds = array_values(array_filter($newRoleIds, fn ($id) => (int) $id !== (int) $adminRole->id));
            }
        }
        $user->roles()->sync($newRoleIds);

        if (request()->is('api/*')) {
            return response()->json(['message' => 'User updated.', 'user' => $this->userToArray($user)]);
        }
        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->id === $request->user()->id) {
            if (request()->is('api/*')) {
                return response()->json(['message' => 'You cannot delete your own account.'], 422);
            }
            return redirect()->back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && $user->roles->contains('slug', 'admin')) {
            $adminUserCount = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->count();
            if ($adminUserCount <= 1) {
                if (request()->is('api/*')) {
                    return response()->json(['message' => 'Cannot delete the last admin user.'], 422);
                }
                return redirect()->back()->withErrors(['user' => 'Cannot delete the last admin user.']);
            }
        }

        $user->delete();

        if (request()->is('api/*')) {
            return response()->json(['message' => 'User deleted.']);
        }
        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    private function isDesignatedAdmin(User $user): bool
    {
        return strtolower((string) $user->email) === strtolower((string) config('auth.admin_email', 'admin@gmail.com'));
    }
}
