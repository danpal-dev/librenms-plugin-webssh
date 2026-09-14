<?php

namespace App\Plugins\WebSSH\Http;

use App\Models\Device;
use App\Models\DeviceAttrib;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;

class WebSSHDeviceCredController extends Controller
{
    private const ATTRIB_TYPES = ['webssh_username', 'webssh_password', 'webssh_port'];

    public function show(Device $device): JsonResponse
    {
        $attribs = $device->attribs()
            ->whereIn('attrib_type', self::ATTRIB_TYPES)
            ->pluck('attrib_value', 'attrib_type');

        return response()->json([
            'username' => $attribs->get('webssh_username', ''),
            'port'     => $attribs->get('webssh_port', ''),
            'has_password' => $attribs->has('webssh_password'),
        ]);
    }

    public function update(Request $request, Device $device): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'port'     => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);

        $map = [
            'webssh_username' => $validated['username'] ?? null,
            'webssh_port'     => isset($validated['port']) ? (string) $validated['port'] : null,
        ];

        foreach ($map as $type => $value) {
            if ($value !== null && $value !== '') {
                DeviceAttrib::updateOrCreate(
                    ['device_id' => $device->device_id, 'attrib_type' => $type],
                    ['attrib_value' => $value]
                );
            } else {
                $device->attribs()->where('attrib_type', $type)->delete();
            }
        }

        if (isset($validated['password']) && $validated['password'] !== '') {
            DeviceAttrib::updateOrCreate(
                ['device_id' => $device->device_id, 'attrib_type' => 'webssh_password'],
                ['attrib_value' => Crypt::encryptString($validated['password'])]
            );
        } elseif (array_key_exists('password', $validated) && $validated['password'] === '') {
            $device->attribs()->where('attrib_type', 'webssh_password')->delete();
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(Device $device): JsonResponse
    {
        $device->attribs()->whereIn('attrib_type', self::ATTRIB_TYPES)->delete();

        return response()->json(['ok' => true]);
    }
}
