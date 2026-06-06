<?php

namespace App\Plugins\WebSSH;

use App\Models\Device;
use App\Models\Plugin;
use App\Models\User;
use App\Plugins\Hooks\DeviceOverviewHook;
use Illuminate\Support\Facades\Crypt;

class DeviceOverview extends DeviceOverviewHook
{
    public string $view = 'resources.views.device-overview';

    public function authorize(User $user, Device $device): bool
    {
        // Deshabilitado: no mostrar en la visión general del dispositivo
        return false;
    }

    public function data(Device $device): array
    {
        $secretFile = base_path('.webssh_secret');
        $secret = file_exists($secretFile) ? trim(file_get_contents($secretFile)) : '';
        $wsUrl  = str_replace(['https://', 'http://'], ['wss://', 'ws://'], config('app.url'));

        // Credenciales globales del plugin
        $pluginSettings = Plugin::where('plugin_name', 'WebSSH')->value('settings') ?? '{}';
        $global = is_string($pluginSettings) ? json_decode($pluginSettings, true) : ($pluginSettings ?? []);
        $globalUsername = $global['ssh_username'] ?? '';
        $globalPort     = $global['ssh_port_mikrotik'] ?? $global['ssh_port'] ?? 22;

        // Credenciales por dispositivo (desde devices_attribs)
        $attribs = $device->attribs()
            ->whereIn('attrib_type', ['webssh_username', 'webssh_password', 'webssh_port'])
            ->pluck('attrib_value', 'attrib_type');

        $deviceUsername = $attribs->get('webssh_username', '');
        $devicePort     = $attribs->get('webssh_port', '');
        $devicePwd      = $attribs->get('webssh_password', '');
        $hasDeviceCreds = !empty($deviceUsername) || !empty($devicePwd);

        return [
            'title'           => 'Terminal SSH — ' . ($device->sysName ?: $device->hostname),
            'device'          => $device,
            'wsUrl'           => $wsUrl . '/ws/ssh',
            'secret'          => $secret,
            // Para el formulario de credenciales por dispositivo
            'has_device_creds' => $hasDeviceCreds,
            'device_username'  => $deviceUsername,
            'device_port'      => $devicePort,
            'global_username'  => $globalUsername,
            'global_port'      => $globalPort,
        ];
    }
}
