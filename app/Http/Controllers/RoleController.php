<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response|JsonResponse
    {
        $roles = Role::withCount('users')
            ->with('permissions:id,name,slug,group')
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        if (request()->is('api/*')) {
            return response()->json(['roles' => $roles]);
        }
        return Inertia::render('Roles/Index', ['roles' => $roles]);
    }

    public function show(Role $role): JsonResponse
    {
        $role->loadCount('users')->load('permissions:id,name,slug,group');
        return response()->json(['role' => $role]);
    }

    public function create(): Response|JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        if (request()->is('api/*')) {
            return response()->json(['permissions' => $permissions]);
        }
        return Inertia::render('Roles/Create', ['permissions' => $permissions]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                Rule::notIn(['admin']),
                'unique:roles,slug',
            ],
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'exists:mysql.permissions,id',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_system' => false,
        ]);
        $role->permissions()->sync($request->permissions ?? []);

        if (request()->is('api/*')) {
            $role->load('permissions:id,name,slug,group');
            return response()->json(['message' => 'Role created.', 'role' => $role], 201);
        }
        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): Response|RedirectResponse|JsonResponse
    {
        if ($role->isSystem()) {
            if (request()->is('api/*')) {
                return response()->json(['message' => 'System role cannot be modified.'], 403);
            }
            return redirect()->route('roles.index');
        }

        $role->load('permissions:id,name,slug,group');
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        $payload = ['role' => $role, 'permissions' => $permissions];

        if (request()->is('api/*')) {
            return response()->json($payload);
        }
        return Inertia::render('Roles/Edit', $payload);
    }

    public function update(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        if ($role->isSystem()) {
            if (request()->is('api/*')) {
                return response()->json(['message' => 'System role cannot be modified.'], 403);
            }
            abort(403, 'System role cannot be modified.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                Rule::notIn(['admin']),
                Rule::unique('roles', 'slug')->ignore($role->id),
            ],
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'exists:mysql.permissions,id',
        ]);

        $role->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
        ]);
        $role->permissions()->sync($request->permissions ?? []);

        if (request()->is('api/*')) {
            $role->load('permissions:id,name,slug,group');
            return response()->json(['message' => 'Role updated.', 'role' => $role]);
        }
        return redirect()->route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse|JsonResponse
    {
        if ($role->isSystem()) {
            if (request()->is('api/*')) {
                return response()->json(['message' => 'System role cannot be deleted.'], 403);
            }
            abort(403, 'System role cannot be deleted.');
        }

        $role->delete();

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Role deleted.']);
        }
        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }
}
