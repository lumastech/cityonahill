<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReorderMenuController extends Controller
{
    public function __construct(private readonly MenuService $menuService) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items'             => ['required', 'array'],
            'items.*.id'        => ['required', 'integer', 'exists:menus,id'],
            'items.*.position'  => ['required', 'integer', 'min:0'],
            'items.*.parent_id' => ['nullable', 'integer', 'exists:menus,id'],
        ]);

        foreach ($validated['items'] as $item) {
            Menu::where('id', $item['id'])->update([
                'position'  => $item['position'],
                'parent_id' => $item['parent_id'] ?? null,
            ]);
        }

        $this->menuService->clearAllCache();

        return back();
    }
}
