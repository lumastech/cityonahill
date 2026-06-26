<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuRoleController extends Controller
{
    public function __construct(private readonly MenuService $menuService) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id'    => ['required', 'integer', 'exists:roles,id'],
            'menu_ids'   => ['present', 'array'],
            'menu_ids.*' => ['integer', 'exists:menus,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $role->menus()->sync($validated['menu_ids']);

        $this->menuService->clearAllCache();

        return back()->with('success', "Menu access updated for role \"{$role->name}\".");
    }
}
