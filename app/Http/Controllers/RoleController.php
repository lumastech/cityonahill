<?php

namespace App\Http\Controllers;

use App\Data\CreateRoleData;
use App\Data\UpdateRoleData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Roles/Index', [
            'roles' => Role::withCount('permissions')->with('permissions')->orderBy('level')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create', [
            'permissions' => Permission::orderBy('name')->get()->groupBy(fn ($p) => explode('.', $p->name)[0]),
        ]);
    }

    public function store(CreateRoleData $data): RedirectResponse
    {
        $role = Role::create([
            'name'  => $data->name,
            'level' => $data->level,
            'group' => $data->group,
        ]);

        if ($data->permission_ids) {
            $role->syncPermissions($data->permission_ids);
        }

        return to_route('roles.index')->with('success', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role): Response
    {
        return Inertia::render('Roles/Edit', [
            'role'        => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get()->groupBy(fn ($p) => explode('.', $p->name)[0]),
        ]);
    }

    public function update(UpdateRoleData $data, Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 403, 'The super-admin role cannot be modified.');

        $role->update([
            'name'  => $data->name,
            'level' => $data->level,
            'group' => $data->group,
        ]);

        $role->syncPermissions($data->permission_ids);

        return to_route('roles.index')->with('success', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 403, 'The super-admin role cannot be deleted.');

        $role->delete();

        return to_route('roles.index')->with('success', 'Role deleted.');
    }
}
