<?php

namespace App\Plugins\WebSSH;

use App\Models\Device;
use App\Plugins\Hooks\PageHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class Page extends PageHook
{
    public const PLUGIN_VERSION = '1.2.0';
    public string $view = 'resources.views.page';

    public function authorize(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function data(array $settings = []): array
    {
        $u = auth()->user();
        if ($u === null) {
            return $this->wrap([
                'devices' => collect(),
                'wsUrl'   => '',
                'secret'  => '',
            ]);
        }
        $devices = Device::hasAccess($u)
            ->select('device_id', 'hostname', 'sysName', 'os', 'ip')
            ->whereIn('os', ['routeros', 'fortigate', 'fortios'])
            ->get();

        // Si no hay dispositivos con esos OS, mostrar activos permitidos
        if ($devices->isEmpty()) {
            $devices = Device::hasAccess($u)
                ->select('device_id', 'hostname', 'sysName', 'os', 'ip')
                ->where('status', 1)
                ->get();
        }

        $secretFile = base_path('.webssh_secret');
        $secret = file_exists($secretFile) ? trim((string) file_get_contents($secretFile)) : '';
        $wsUrl  = str_replace(['https://', 'http://'], ['wss://', 'ws://'], (string) config('app.url'));

        return $this->wrap([
            'devices' => $devices,
            'wsUrl'   => $wsUrl . '/ws/ssh',
            'secret'  => $secret,
        ]);
    }

    private function wrap(array $base): array
    {
        return array_merge($base, [
            'plugin_version' => self::PLUGIN_VERSION,
            'docs'           => [
                'guia'        => self::readDoc(__DIR__ . '/GUÍA.md'),
                'faq'         => self::readDoc(__DIR__ . '/FAQ.md'),
                'instalacion' => self::readDoc(__DIR__ . '/INSTALACIÓN.md'),
                'arquitectura'=> self::readDoc(__DIR__ . '/ARQUITECTURA.md'),
            ],
        ]);
    }

    private static function readDoc(string $filePath): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            return ['title' => basename($filePath), 'lines' => [], 'empty' => true];
        }

        $raw   = (string) file_get_contents($filePath);
        $lines = preg_split("/\r\n|\n|\r/", $raw === '' ? '' : $raw);
        if ($lines === false) { $lines = []; }

        $title = basename($filePath);
        if (isset($lines[0]) && preg_match('/^#\s+(.+)$/', trim((string) $lines[0]), $m)) {
            $title = trim($m[1]);
            array_shift($lines);
        }

        $lines = array_values(array_filter($lines, static fn ($l) => $l !== null));

        return [
            'title' => $title,
            'lines' => $lines,
            'empty' => count(array_filter($lines, static fn ($l) => trim((string) $l) !== '')) === 0,
        ];
    }
}
