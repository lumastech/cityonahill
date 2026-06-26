<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Role;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function __construct(private readonly MenuService $menuService) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Menus', [
            'menus' => Menu::with(['children' => fn ($q) => $q->orderBy('position')])
                ->topLevel()
                ->orderBy('position')
                ->get(),
            'roles'      => Role::orderBy('name')->get(['id', 'name']),
            'roleMenuMap' => Role::with('menus:id')->get()
                ->mapWithKeys(fn ($r) => [$r->id => $r->menus->pluck('id')]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:64'],
            'icon'      => ['required', 'string', 'max:64'],
            'route'     => ['nullable', 'string', 'max:128'],
            'parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'position'  => ['integer', 'min:0'],
        ]);

        Menu::create($validated);

        return back()->with('success', 'Menu item created.');
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'string', 'max:64'],
            'icon'      => ['sometimes', 'string', 'max:64'],
            'route'     => ['nullable', 'string', 'max:128'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $menu->update($validated);
        $this->menuService->clearAllCache();

        return back()->with('success', 'Menu item updated.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();
        $this->menuService->clearAllCache();

        return back()->with('success', 'Menu item deleted.');
    }
}
