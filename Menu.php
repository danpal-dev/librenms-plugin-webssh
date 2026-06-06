<?php

namespace App\Plugins\WebSSH;

use App\Plugins\Hooks\MenuEntryHook;

class Menu extends MenuEntryHook
{
    public function authorize(\Illuminate\Contracts\Auth\Authenticatable $user, array $settings = []): bool
    {
        return true;
    }

    public function data(array $settings = []): array
    {
        return [];
    }
}
