<?php

namespace App\Plugins\WebSSH;

use App\Plugins\Hooks\MenuEntryHook;
use Illuminate\Contracts\Auth\Authenticatable;

class Menu extends MenuEntryHook
{
    public function authorize(?Authenticatable $user, array $settings = []): bool
    {
        if ($user === null) {
            return false;
        }
        $minRole = $settings['min_role'] ?? 'user';
        if ($minRole === 'admin')       return $user->can('admin');
        if ($minRole === 'global-read') return $user->can('global-read');
        return true;
    }

    public function data(array $settings = []): array
    {
        return [];
    }
}
