# 🏗 Arquitectura técnica · WebSSH LibreNMS

Documento interno para desarrolladores y ops avanzadas. Explica cómo se compone el plugin internamente, el flujo request/response y sus medidas de seguridad.

---

## 🏗 Estructura de carpetas (autocontenida 100%)

Todo el plugin vive **solo** dentro de `app/Plugins/WebSSH/` (regla AGENTS.md):

```
WebSSH/
├── Page.php               # Hook PageHook: /plugin/WebSSH → página terminal principal
├── Settings.php           # Hook SettingsHook: Ajustes (REST + min_role) + constructor rutas credenciales
├── Menu.php               # Hook MenuEntryHook: menú lateral respetando min_role
├── DeviceOverview.php     # Hook DeviceOverviewHook: tarjeta terminal en ficha dispositivo (OFF default)
├── Http/
│   └── WebSSHDeviceCredController.php  # Endpoints CRUD credenciales por dispositivo (JSON)
├── Support/
│   └── PluginObserver.php  # Observer Plugin: cifra AES el password global al guardar settings
├── resources/views/
│   ├── page.blade.php             # Terminal principal Flowbite UI (fragment)
│   ├── settings.blade.php         # Ajustes + tabla credenciales por dispositivo
│   ├── device-overview.blade.php  # Tarjeta en cada DeviceOverview
│   ├── menu.blade.php             # Custom menu item (si fuera necesario)
│   └── markdown-lines.blade.php   # Renderer Markdown custom autocontenido (sin Composer)
├── webssh-server.py        # Daemon Python WebSocket + Paramiko (WSGI async server)
├── librenms-webssh.service # Unit systemd para el daemon Python
├── GUÍA.md | FAQ.md | INSTALACIÓN.md | ARQUITECTURA.md  # 4 docs integrados UI
├── .gitignore              # Ignora __pycache__/logs/screenshots/.env
└── .git/                   # Sub-repositorio independiente (sobrevive daily.sh)
```

---

## 🔗 4 hooks y su ciclo de vida en LibreNMS PluginManager

| Hook | Se ejecuta... | Método `authorize()` | Devuelve |
|---|---|---|---|
| `Page.php (PageHook)` | GET `/plugin/WebSSH` | `?Authenticatable $user` → usuario autenticado | `['devices', 'wsUrl', 'secret', 'plugin_version', 'docs']` |
| `Settings.php (SettingsHook)` | GET/POST `/plugin/settings/WebSSH` | `?Authenticatable $user` → `can('admin')` | Credenciales globales + tabla devices overrides |
| `Menu.php (MenuEntryHook)` | Cada página del sidebar | `?Authenticatable $user` → role ≥ `min_role` (settings) | Entrada menú si pasa |
| `DeviceOverview.php (DeviceOverviewHook)` | Vista `/device/device=ID` | `?User $user + Device $device` → `false` (por defecto OFF) | Datos terminal por dispositivo |

### ⚠️ Patrón authorize nullable
Todos los hooks usan **tipado `?Authenticatable` o `?User` + guard `$user!==null`**. Esto evita TypeError cuando el `PluginManager` evalúa hooks en contexto sin sesión. Un error aquí actualiza `plugins.plugin_active=0` → la URL `/plugin/WebSSH` devuelve **404** hasta que se reactiva manualmente en BD.

### `Page.php::wrap(array $base)` + `readDoc()`
Patrón idéntico a FlowbiteTheme/MigrateRrdRetention: añade `plugin_version` + `docs[]` (4 documentos parseados línea a línea) sin romper keys antiguas (`devices/wsUrl/secret`). La UI lo consume con `@include('WebSSH::resources.views.markdown-lines', ['lines' => $docs['guia']['lines']])`.

---

## 🌐 Endpoints REST (Settings::__construct registra rutas)

El **constructor de Settings.php** registra 3 rutas bajo `web, auth, can:admin`:

| Método | URI | Acción | Parámetros | Respuesta |
|---|---|---|---|---|
| GET | `webssh/device/{device}/creds` | `WebSSHDeviceCredController@show` | `{device}` | `{ok:true, username, port, has_password}` |
| POST | `webssh/device/{device}/creds` | `WebSSHDeviceCredController@update` | JSON `{username, password?, port?}` | `{ok:true}` o `{ok:false, message}` |
| DELETE | `webssh/device/{device}/creds` | `WebSSHDeviceCredController@destroy` | - | `{ok:true}` |

Las credenciales **por dispositivo** se guardan en `devices_attribs` con keys:
- `webssh_username` (texto plano)
- `webssh_port` (texto plano)
- `webssh_password` (cifrada con `Crypt::encryptString()` → AES-256-CBC + APP_KEY).

Credenciales **globales**: guardadas en columna JSON `plugins.settings`:
- `ssh_username` / `ssh_port_mikrotik` / `ssh_port_fortigate` / `ws_port` / `min_role` → texto plano.
- `ssh_password` → encriptada con **PluginObserver** (siguiente sección).

---

## 🔒 PluginObserver: cifrado transparente password global

```
Support/PluginObserver.php
    ↓ (Plugin::observe() en Settings::__construct)
saved() / updating()
    ↓
Si settings[ssh_password] es NO vacío:
    Crypt::encryptString($password)  → AES-256-CBC via APP_KEY
    Se escribe en plugins.settings JSON.
Si settings[ssh_password] está VACÍO:
    Se preserva el valor ya cifrado existente (no cambias la pass al guardar otros campos).
```

⚠️ **Cambio de APP_KEY = perder contraseñas.** Documentado en FAQ 08. Siempre guarda APP_KEY en tu 1Password / Vault.

---

## ⚙️ webssh-server.py (backend WebSocket)

Server **async Python** (`aiohttp http` + `websockets / paramiko`). No usa shell intermedio; escribe en PTY paramiko por cada conexión.

### Inicio y opciones CLI
```bash
python3 /opt/librenms/app/Plugins/WebSSH/webssh-server.py \
  --host 127.0.0.1 \
  --port 8765 \
  --idle 900 \
  --secret-file /opt/librenms/.webssh_secret \
  --librenms-path /opt/librenms
```

### Ciclo de vida conexión
```
Browser                                   Server Python                           Dispositivo
   │                                            │                                        │
   │── ws://127.0.0.1:8765/ws/ssh ────────────▶│                                        │
   │   msg {type:connect, device_id, token}     │                                        │
   │                                            │ 1) Validar token:                        │
   │                                            │    · split device_id:ts:sig            │
   │                                            │    · hmac(device_id+ts,secret)==sig    │
   │                                            │    · abs(now-ts) < 60s                 │
   │                                            │ 2) Cargar credencial:                    │
   │                                            │    · devices_attribs override?          │
   │                                            │      si NO → plugins.settings global    │
   │                                            │ 3) Paramiko: SSHClient.connect(IP,port, │
   │                                            │      username, password,                │
   │                                            │      timeout=10, auth_timeout=15)       │
   │                                            │──── open shell, allocate PTY ───────────▶│
   │                                            │◀── stdout/stderr stream ────────────────│
   │◀── {type:output, data: bytes} ─────────────│                                        │
   │                                            │                                        │
   │── {type:input, data: keystroke} ──────────▶│──────── write channel ────────────────▶│
   │                                            │                                        │
   │── WS close ───────────────────────────────▶│ channel.close() + SSH.close()           │
```

### Medidas seguridad server
- **Nunca acepta credenciales en claro por WebSocket**: solo token HMAC firmado server-side.
- **Escucha en loopback `127.0.0.1` por defecto**: sólo Nginx puede hablar con él. Nginx fuerza HTTPS + WSS forward.
- **Tiempo máximo idle (sin bytes)** configurable; cierre automático en caso de navegador colgado.
- **Sin subprocess con `shell=True`**: Paramiko gestiona el canal SSH; `proc_open` no se usa para comandos user-provided.
- **Rate limit por IP** integrado en `webssh-server.py` (max 5 conexiones concurrentes / cliente IP).

---

## 🎨 Frontend xterm.js + Flowbite UI

### Dependencias CDN (ninguna vía npm / Composer):
- `xterm@5.3.0` (css + lib)
- `xterm-addon-fit@0.8.0` (ajuste terminal al contenedor)

### Funciones JS clave en `page.blade.php`
| Función | Propósito |
|---|---|
| `initTerminal()` | Instancia `Terminal` con cursorBlink + tema oscuro, registra `onData` enviar websocket. |
| `generateToken(deviceId)` | HMAC-SHA256 via **WebCrypto API Subtle** (no JS library). Firma `device_id:timestamp` con secret. |
| `connect()` | Construye WebSocket, `onopen` envía `{type:connect, device_id, token}`. |
| `isMikrotik()` | `os` en `dataset.os` del `<option>` elegido. Cambia comandos rápidos. |
| `sendCmd(cmd)` / `btn-ping` / `btn-ping-ext` | Wrappers para enviar inputs prefabricados. |

### DeviceOverview `@once xterm`
Dado que varios DeviceOverview pueden existir en la misma página (rara vez), el tag `@once` evita cargar CDN xterm duplicado. Cada tarjeta tiene su propio `<div id="ssh-term-{{DEVICE_ID}}">` y su propio `DEVICE_ID` JS en un IIFE aislado.

---

## 🧱 Persistencia & almacenamiento (sin migraciones BD)

Agente AGENTS.md prohibe migraciones de base de datos. Todo se guarda en tablas existentes:

| Persistencia | Ubicación | Formato |
|---|---|---|
| Config global plugin | `plugins.settings` | JSON (`ssh_username/ssh_password_enc/puertos/min_role`) |
| Credenciales por dispositivo | `devices_attribs.attrib_type IN(...)` | 3 filas por device (plain + cifrada) |
| Secret HMAC | `{librenms}/.webssh_secret` | Archivo 64 caracteres hex `chmod 640 librenms` |
| Logs Python servidor | `journalctl -u librenms-webssh.service` | systemd journal |
| Logs error Laravel | `/opt/librenms/logs/librenms.log` | Log estándar de errores PluginManager |

---

## ♻️ Renderer Markdown sin dependencias Composer

Cada plugin (WebSSH, FlowbiteTheme, MigrateRrdRetention, RrdFix) tiene **su propia copia** de `markdown-lines.blade.php` (265 líneas). Regla AGENTS.md: **no compartir archivos entre plugins** (porque `daily.sh` no borra los plugins, pero compartir un archivo sería un coupling invisible si uno es actualizado y los otros no).

**Sintaxis soportada**: fenced code, headings ATX/Setext, listas ul/ol, tablas `| col1 | col2 |`, blockquote `>`, hr `---`, inline code, bold/italic, links. No HTML interno permitido por seguridad.

---

## 🧪 Checks antes de cada release

- [ ] `php -l Page.php` + `Settings.php` + `Menu.php` + `DeviceOverview.php` → 4/4 PASS.
- [ ] Browser `/plugin/WebSSH` → status 200, xterm inicia, click Conectar no produce TypeError JS.
- [ ] Browser `/plugin/settings/WebSSH` → guardar credenciales no rompe cifrado Observer.
- [ ] `webssh-server.py --help` exit code 0.
- [ ] `systemd-analyze security librenms-webssh.service` → nivel UNSAFE ≤ 8.0.
- [ ] Tras `daily.sh` del repositorio padre: `plugins.plugin_active=1` y carpeta WebSSH intacta.

---

## 🧭 Flujo completo request desde click

```
Usuario click "Web SSH Terminal" (menú)
  └─▶ GET /plugin/WebSSH
      └─▶ PluginProvider → PageHook.authorize() nullable (✓)
          └─▶ Page::data() → devices hasAccess + wsUrl + secret
              └─▶ Page::wrap() → injecta plugin_version + docs[]
                  └─▶ view('plugins.settings') (layout LibreNMSv1)
                      @include('WebSSH::resources.views.page') (FRAGMENTO)
                          pills + banner + selector device + terminal + accordeon 4 tabs
                          JS init xterm + click handlers

Usuario click "Conectar"
  └─▶ generateToken HMAC (browser)
      └─▶ new WebSocket(wss://DOMINIO/ws/ssh)
          └─▶ Nginx location /ws/ (Upgrade)
              └─▶ webssh-server.py aiohttp handler (valida token)
                  └─▶ Paramiko open(IP del dispositivo, creds global o device_attribs)
                      └─▶ bidireccional stdout ↔ websocket send {output} / onData {input}
```

**Fin de documento. Para guía operativa vea GUÍA.md; para resolución de problemas vea FAQ.md; para instalación paso a paso vea INSTALACIÓN.md.**
