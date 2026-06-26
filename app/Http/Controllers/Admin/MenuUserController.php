<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MenuUserController extends Controller
{
    public function __construct(private readonly MenuService $menuService) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id'              => ['required', 'integer', 'exists:users,id'],
            'overrides'            => ['present', 'array'],
            'overrides.*.menu_id'  => ['required', 'integer', 'exists:menus,id'],
            'overrides.*.granted'  => ['required', 'boolean'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        $syncData = collect($validated['overrides'])
            ->mapWithKeys(fn ($o) => [$o['menu_id'] => ['granted' => $o['granted']]]);

        $user->menuOverrides()->sync($syncData);

        $this->menuService->clearCache($user->id);

        return back()->with('success', 'User menu overrides saved.');
    }
}
