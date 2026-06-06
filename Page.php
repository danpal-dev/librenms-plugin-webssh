<?php

namespace App\Plugins\WebSSH;

use App\Models\Device;
use App\Plugins\Hooks\PageHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class Page extends PageHook
{
    public string $view = 'resources.views.page';

    public function authorize(Authenticatable $user): bool
    {
        return true;
    }

    public function data(array $settings = []): array
    {
        $devices = Device::hasAccess(auth()->user())
            ->select('device_id', 'hostname', 'sysName', 'os', 'ip')
            ->whereIn('os', ['routeros', 'fortigate', 'fortios'])
            ->get();

        // Si no hay dispositivos con esos OS, mostrar activos permitidos
        if ($devices->isEmpty()) {
            $devices = Device::hasAccess(auth()->user())
                ->select('device_id', 'hostname', 'sysName', 'os', 'ip')
                ->where('status', 1)
                ->get();
        }

        $secretFile = base_path('.webssh_secret');
        $secret = file_exists($secretFile) ? trim(file_get_contents($secretFile)) : '';
        $wsUrl  = str_replace(['https://', 'http://'], ['wss://', 'ws://'], config('app.url'));

        return [
            'devices' => $devices,
            'wsUrl'   => $wsUrl . '/ws/ssh',
            'secret'  => $secret,
        ];
    }
}
