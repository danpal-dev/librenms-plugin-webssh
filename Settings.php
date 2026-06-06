<?php

namespace App\Plugins\WebSSH;

use App\Models\Plugin;
use App\Plugins\Hooks\SettingsHook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class Settings extends SettingsHook
{
    public string $view = 'resources.views.settings';

    public function authorize(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return $user->can('admin');
    }

    public function __construct()
    {
        // Rutas REST de credenciales por dispositivo
        if (! app()->routesAreCached()) {
            Route::middleware(['web', 'auth', 'can:admin'])
                ->group(function (): void {
                    Route::get('webssh/device/{device}/creds',
                        [\App\Http\Controllers\WebSSHDeviceCredController::class, 'show'])
                        ->name('webssh.device.creds.show');

                    Route::post('webssh/device/{device}/creds',
                        [\App\Http\Controllers\WebSSHDeviceCredController::class, 'update'])
                        ->name('webssh.device.creds.update');

                    Route::delete('webssh/device/{device}/creds',
                        [\App\Http\Controllers\WebSSHDeviceCredController::class, 'destroy'])
                        ->name('webssh.device.creds.destroy');
                });
        }

        // Observer que cifra/descifra la contraseña global al guardar los settings
        Plugin::observe(\App\Plugins\WebSSH\Support\PluginObserver::class);
    }

    public function data(array $settings = []): array
    {
        // Dispositivos con sus credenciales SSH actuales
        $devices = DB::select(
            "SELECT d.device_id, d.hostname, d.sysName, d.os,
                    MAX(CASE WHEN da.attrib_type = 'webssh_username' THEN da.attrib_value END) AS ssh_username,
                    MAX(CASE WHEN da.attrib_type = 'webssh_port'     THEN da.attrib_value END) AS ssh_port,
                    MAX(CASE WHEN da.attrib_type = 'webssh_password' THEN 1 END) AS has_password
             FROM devices d
             LEFT JOIN devices_attribs da ON da.device_id = d.device_id
                 AND da.attrib_type IN ('webssh_username','webssh_password','webssh_port')
             WHERE d.os IN ('routeros','fortigate','fortios')
             GROUP BY d.device_id, d.hostname, d.sysName, d.os
             ORDER BY d.hostname ASC"
        );

        return [
            'ssh_username'       => $settings['ssh_username'] ?? '',
            'ssh_password'       => !empty($settings['ssh_password']),
            'ssh_port_mikrotik'  => $settings['ssh_port_mikrotik'] ?? 22,
            'ssh_port_fortigate' => $settings['ssh_port_fortigate'] ?? 22,
            'ws_port'            => $settings['ws_port'] ?? 8765,
            'min_role'           => $settings['min_role'] ?? 'user',
            'devices'            => $devices,
        ];
    }
}
