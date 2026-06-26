<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class MenuService
{
    /**
     * Resolve the visible menu tree for a user.
     * Output shape matches the existing NavGroup format so AppSidebar needs no changes:
     *   [{ label, icon, items: [{ label, url }] }]
     *
     * @return Collection<int, array{label: string, icon: string, items: list<array{label: string, url: string|null}>}>
     */
    public function forUser(User $user): Collection
    {
        $version  = Cache::get('menu.version', 0);
        $cacheKey = "menu.user.{$user->id}.v{$version}";

        $cached = Cache::get($cacheKey);

        if ($cached instanceof Collection) {
            return $cached;
        }

        $result = $this->resolve($user);
        Cache::put($cacheKey, $result, now()->addMinutes(30));

        return $result;
    }

    public function clearCache(int $userId): void
    {
        Cache::forget("menu.user.{$userId}");
    }

    /** Bust all per-user caches by incrementing a global version counter. */
    public function clearAllCache(): void
    {
        Cache::increment('menu.version');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function resolve(User $user): Collection
    {
        $roleIds = $user->roles()->pluck('id');

        // Menus granted via role
        $roleGrantedIds = Menu::whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))
            ->active()
            ->pluck('id');

        // Per-user overrides
        $userOverrides = $user->menuOverrides()->get()->keyBy('id');
        $userGranted   = $userOverrides->filter(fn ($m) => (bool) $m->pivot->granted)->pluck('id');
        $userDenied    = $userOverrides->filter(fn ($m) => ! $m->pivot->granted)->pluck('id');

        $visibleIds = $roleGrantedIds
            ->merge($userGranted)
            ->unique()
            ->diff($userDenied);

        // Load all visible items ordered by position
        $items = Menu::whereIn('id', $visibleIds)
            ->active()
            ->orderBy('position')
            ->get();

        // Load parent groups for visible items
        $parentIds = $items->pluck('parent_id')->filter()->unique();
        $groups    = Menu::whereIn('id', $parentIds)
            ->orWhereIn('id', $items->whereNull('parent_id')->pluck('id'))
            ->orderBy('position')
            ->get()
            ->keyBy('id');

        // Build tree in NavGroup format
        $tree = collect();

        foreach ($groups->whereNull('parent_id') as $group) {
            $children = $items
                ->where('parent_id', $group->id)
                ->map(fn (Menu $m) => [
                    'label' => $m->name,
                    'url'   => $this->resolveUrl($m->route),
                ]);

            if ($children->isNotEmpty()) {
                $tree->push([
                    'label' => $group->name,
                    'icon'  => $group->icon,
                    'items' => $children->values()->all(),
                ]);
            }
        }

        // Top-level items (no parent) that are themselves visible
        foreach ($items->whereNull('parent_id') as $item) {
            if (! $tree->contains('label', $item->name)) {
                $tree->prepend([
                    'label' => $item->name,
                    'icon'  => $item->icon,
                    'items' => [['label' => $item->name, 'url' => $this->resolveUrl($item->route)]],
                ]);
            }
        }

        return $tree->values();
    }

    private function resolveUrl(?string $route): ?string
    {
        if (! $route) {
            return null;
        }

        if (Route::has($route)) {
            return route($route);
        }

        return $route;
    }
}
