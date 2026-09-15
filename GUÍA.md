# 📘 Guía de usuario · WebSSH LibreNMS

Plugin administrador para abrir **sesiones SSH web** directamente desde LibreNMS a tus dispositivos RouterOS / FortiGate / FortiOS, sin cliente SSH de escritorio. La comunicación es por **WebSocket seguro (wss://)** end-to-end pasando por el mismo dominio HTTPS de LibreNMS.

## 🧭 Acceso

| Canal | Ruta / Secuencia |
|---|---|
| **Página principal** | `https://tu-librenms/plugin/WebSSH/` |
| **Menú lateral** | `Plugins → Web SSH Terminal` 🖥️ (requiere rol ≥ `min_role` configurado) |
| **Ajustes admin** | `https://tu-librenms/plugin/settings/WebSSH` |
| **Permiso mínimo** | Configurable en Ajustes: `user` / `global-read` / `admin` |

---

## 🚨 Antes de empezar — prerrequisitos operativos

1. **Servidor Python WebSSH arrancado**: `sudo systemctl status librenms-webssh.service` debe estar `active (running)`. El server escucha en `127.0.0.1:8765` (puerto configurable).
2. **Proxy reverso Nginx**: la ruta `/ws/ssh` del dominio debe pasar con **Upgrade WebSocket** al puerto 8765 local (ejemplo completo en `ARQUITECTURA.md`). Sin este paso el navegador recibe **404 / Connection Refused**.
3. **Clave `.webssh_secret`** idéntica entre frontend (Laravel) y backend (Python) — `base_path('.webssh_secret')` compartido. El plugin la lee y el server `webssh-server.py` también. Si la regeneras, **reinicia `librenms-webssh`**.
4. **Dispositivos en LibreNMS**: al menos 1 dispositivo con `os=routeros|fortigate|fortios` y `status=up` visible por el usuario autenticado (respeta `Device::hasAccess()`).
5. **Credenciales SSH**: configurar credenciales **globales** en Ajustes o **por dispositivo** en la tabla de ajustes o en la propia ficha del dispositivo.

---

## 📋 Flujo estándar (copiar-pegar para NOC / HelpDesk)

### Paso 1 — Abrir terminal
1. Entra en `Plugins → Web SSH Terminal` o `/plugin/WebSSH/`.
2. En el desplegable **Dispositivo** aparecerán solo los dispositivos a los que tu usuario tenga acceso (por grupo/rol), priorizando `RouterOS` y `FortiGate`.
3. Elige dispositivo. El botón **Conectar** se habilita automáticamente. Haz clic.

### Paso 2 — Esperar conexión
- Estado pasa de `Desconectado → Conectando → Conectado`.
- El servidor WebSSH valida el **token HMAC-SHA256** (`device_id + timestamp + firma`) — caduca rápidamente para evitar replay attacks.
- Si falla:
  - Aparece en la terminal `[ERROR] mensaje`.
  - Consulta la tabla FAQ 01-04 según el texto.

### Paso 3 — Ejecutar comandos
Hay **3 formas** de enviar comandos:

| Método | Uso típico |
|---|---|
| ⌨️ **Teclado directo sobre terminal** | Tareas interactivas, edición de ficheros, `:if` en RouterOS |
| 🎯 **Botones Comandos Rápidos** (fila superior) | Diagnósticos habituales — cambian según `RouterOS` vs `FortiGate` |
| 🌐 **Ping a destino personalizado** | Input `IP/hostname` + Ping (5 paquetes) / Ping x20 (diagnóstico intermitencia) |

#### Comandos rápidos según sistema operativo

| Botón | RouterOS | FortiGate |
|---|---|---|
| Interfaces | `/ip/address/print` | `get system interface` |
| Rutas | `/ip/route/print` | `get router info routing-table all` |
| Estado sistema | `/system/resource/print` | `get system status` |
| Logs | `/log/print` | `get system event` |
| Errores interfaz | `/interface/ethernet/print stats` | `get hardware nic all` |
| Sesiones / estado | `/ip/firewall/connection/print count-only` | `get system session info` |

#### Ping personalizado
Escribe destino (IP o hostname), pulsa **Ping** para 5 paquetes o **Ping x20** para intermitencia.
- RouterOS: usa `/tool/ping address=X count=N [src-address=IP_DISP]`.
- FortiGate: usa `execute ping [options count N]`; `ping x20` configura `ping-options` antes.

### Paso 4 — Cerrar sesión
Pulsa **Desconectar** (botón rojo). El server cierra el PTY y envía `EOF` al shell SSH. Si cierras la pestaña sin desconectar el cierre del WebSocket dispara limpieza automática al cabo de ~30s en el Python (heartbeat).

---

## 🎛 Pestaña de Ajustes (solo `admin`)

### Credenciales globales
| Campo | Valor recomendado |
|---|---|
| **Usuario SSH** | Usuario de **solo lectura** (`monitor` / `read` en MikroTik, `prof_admin read-only` en FortiGate). **NUNCA uses admin.** |
| **Contraseña SSH** | Almacenada cifrada con AES-256-CBC + APP_KEY en `plugins.settings` JSON. |
| **Puerto MikroTik** | 22 o el puerto custom del dispositivo |
| **Puerto FortiGate** | 22 o puerto dedicado |
| **Puerto WebSocket** | 8765 (solo loopback — NUNCA exponer a internet) |
| **Acceso mínimo** | `user` (por defecto) / `global-read` / `admin`. Recomendado: `global-read` para NOC. |

### Credenciales por dispositivo (tabla inferior)
Si un equipo usa credenciales distintas a la global, rellena **Usuario / Contraseña / Puerto** en su fila y pulsa 💾 Guardar. Se almacenan en `devices_attribs`.
- Si dejas campo vacío → usa la **credencial global** de arriba.
- Botón 🗑 **Eliminar**: borra el override y vuelve a credencial global.

---

## 🔐 Acceso de solo lectura — buenas prácticas

### MikroTik RouterOS
Crea un grupo `read` y un usuario `monitor` asignado:
```
/user group add name=read policy=read,local,telnet,ssh,winbox,api,test,!local,!telnet,!ssh,!ftp,!reboot,!write,!policy,!test,!winbox,!password,!web,!sniff,!sensitive,!romon comment="Solo lectura monitoreo"
/user add name=monitor group=read password="CAMBIA_ESTA_CLAVE" comment="WebSSH LibreNMS"
```

### FortiGate
Crea perfil de administrador `read_only` y asigna:
```
config system accprofile
    edit "read_only"
        set mntgrp read
        set admingrp read
        set updategrp none
        set authgrp read
        set sysgrp read
        set netgrp read
        set loggrp read
        set routegrp read
        set fwgrp read
        set vpngrp read
        set utmgrp read
        set wifi read
    next
end
config system admin
    edit "monitor"
        set accprofile "read_only"
        set vdom "root"
        set password CAMBIA_ESTA_CLAVE
    next
end
```

---

## 🧩 Flujo de seguridad técnico (resumen)

```
Usuario autenticado LibreNMS
  ↓ (Laravel Session + CSRF)
Click "Conectar" → Browser genera token HMAC-SHA256(device+ts, secret)
  ↓ (WebSocket wss:// dominio/librenms/ws/ssh)
Nginx proxy_pass http://127.0.0.1:8765  [Upgrade: websocket]
  ↓
webssh-server.py valida: caducidad ts + firma HMAC + pertenencia device
  ↓ (Paramiko exec / SSHClient)
Abre sesión SSH al dispositivo con credencial (global o devices_attribs)
  ↓ bidireccional
xterm.js ↔ WebSocket ↔ PTY remoto
```

Más detalles técnicos completos en `ARQUITECTURA.md`.

---

## 🖥 Modo Dispositivo (DeviceOverview)

Este plugin incluye el hook **DeviceOverview** (`DeviceOverview.php`) que renderiza la tarjeta de terminal + credenciales directamente en la vista de un dispositivo. **Por defecto viene desactivado** (`return false;` en `authorize()`) para no saturar la página del equipo.

Para activarlo edita [DeviceOverview.php](DeviceOverview.php#L15-L25) y sustituye `return false;` por:
```php
return $user->can('view', $device);
```
Después de activar, aparece una tarjeta "Terminal SSH — `sysName`" arriba en `/device/device=ID/`. Permite:
- Override credenciales del equipo (admin).
- Botón **Abrir Terminal SSH** dedicado a ese solo dispositivo sin selector.
- Comandos rápidos precargados para el sistema operativo del equipo.

---

## 🧪 Trucos NOC y resolución inmediata

1. **Ping rápido a todos los gateways** del circuito: abre 2 pestañas y usa Ping x20 en paralelo para diagnosticar rutas asimétricas.
2. **Copia de seguridad rápida RouterOS**: `/export file=backup-$(date +%F)` → lo generas y después te descargas el `.rsc` por Winbox/SCP por si en la sesión haces algo que debes deshacer.
3. **Terminal sin WebSocket** (fallback): si `/ws/ssh` falla, siempre puedes ir a `Configuración → WebSSH → ver credencial guardada` y abrir Putty/Termius con la IP del dispositivo (aunque esto anula el beneficio del SSO).
4. **Ver sesiones abiertas** en el server WebSSH: `journalctl -u librenms-webssh.service --lines 500 | grep "opened\|closed"`.
