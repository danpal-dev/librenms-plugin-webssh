<?php

namespace App\Plugins\WebSSH\Support;

use App\Models\Plugin;
use Illuminate\Support\Facades\Crypt;

class PluginObserver
{
    private const PLUGIN = 'WebSSH';
    private const MARKER = 'enc:';

    /**
     * Antes de guardar: cifra si viene en claro; conserva si viene vacío.
     */
    public function saving(Plugin $plugin): void
    {
        if ($plugin->plugin_name !== self::PLUGIN) {
            return;
        }

        $settings    = $plugin->settings ?? [];
        $newPassword = $settings['ssh_password'] ?? '';

        if (empty($newPassword)) {
            // Vacío en el form → conservar la contraseña guardada en DB
            $stored = Plugin::where('plugin_name', self::PLUGIN)->value('settings');
            $stored = is_string($stored) ? json_decode($stored, true) : ($stored ?? []);
            if (!empty($stored['ssh_password'])) {
                $settings['ssh_password'] = $stored['ssh_password'];
                $plugin->settings = $settings;
            }
            return;
        }

        // Cifrar solo si no está ya cifrado
        if (!str_starts_with($newPassword, self::MARKER)) {
            $settings['ssh_password'] = self::MARKER . Crypt::encryptString($newPassword);
            $plugin->settings = $settings;
        }
    }

    /**
     * Después de recuperar de DB: descifra para uso interno (Python la lee descifrada).
     */
    public function retrieved(Plugin $plugin): void
    {
        if ($plugin->plugin_name !== self::PLUGIN) {
            return;
        }

        $settings = $plugin->settings ?? [];

        if (!empty($settings['ssh_password']) && str_starts_with($settings['ssh_password'], self::MARKER)) {
            try {
                $settings['ssh_password'] = Crypt::decryptString(
                    substr($settings['ssh_password'], strlen(self::MARKER))
                );
                $plugin->settings = $settings;
            } catch (\Exception) {
                // Si falla el descifrado, dejar tal cual
            }
        }
    }
}
