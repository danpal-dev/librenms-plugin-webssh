# WebSSH — Plugin para LibreNMS

![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)
![LibreNMS](https://img.shields.io/badge/LibreNMS-compatible-green)
![License](https://img.shields.io/badge/license-MIT-lightgrey)

Plugin que integra un **cliente SSH web** directamente en LibreNMS, permitiendo abrir sesiones SSH a los dispositivos monitoreados desde el navegador sin necesidad de un cliente SSH externo. **No modifica ningún archivo del núcleo de LibreNMS.**

## Screenshots

> Agrega tus capturas de pantalla aquí.
> Puedes subir imágenes a la carpeta `screenshots/` del repositorio y referenciarlas así:
> `![WebSSH](screenshots/webssh.png)`

---

## Características

- **Terminal SSH en el navegador** para conectarse a dispositivos de red.
- Lista de dispositivos accesibles filtrada por los permisos del usuario autenticado.
- Prioriza dispositivos con sistemas operativos conocidos (RouterOS, FortiGate, FortiOS) y muestra todos los activos si no hay coincidencia.
- Conexión mediante WebSocket (`wss://`) para comunicación segura.
- Autenticación con clave secreta para el servidor WebSocket integrado.

---

## Instalación

### 1. Clonar el repositorio

```bash
cd /opt/librenms/app/Plugins
git clone https://github.com/danpal-dev/librenms-plugin-webssh.git WebSSH
```

### 2. Corregir permisos

```bash
chown -R librenms:librenms /opt/librenms/app/Plugins/WebSSH
```

### 3. Activar el plugin en LibreNMS

1. Inicia sesión en LibreNMS como administrador.
2. Ve a **Configuración → Plugins** (o accede a `/plugins`).
3. Busca **WebSSH** en la lista y haz clic en **Enable**.

### Actualizar

> Repositorio: https://github.com/danpal-dev/librenms-plugin-webssh

```bash
cd /opt/librenms/app/Plugins/WebSSH
git pull
chown -R librenms:librenms .
sudo -u librenms php artisan view:cache
sudo -u librenms php artisan cache:clear
```

### Desinstalar

1. Desactiva el plugin desde **Configuración → Plugins**.
2. Elimina la carpeta:

```bash
rm -rf /opt/librenms/app/Plugins/WebSSH
```

---

## Requisitos

- LibreNMS con soporte de plugins (sistema de hooks `app/Plugins`).
- PHP 8.1+
- Servidor WebSocket SSH accesible en la URL de LibreNMS en la ruta `/ws/ssh`.

## Base de datos

El plugin **no crea tablas nuevas**. Usa tablas estándar de LibreNMS:

| Tabla | Uso | Columnas escritas |
|---|---|---|
| `devices_attribs` | Credenciales SSH por dispositivo | `attrib_type` = `webssh_username` · `webssh_password` · `webssh_port` |
| `plugins` | Configuración global (usuario, puerto, rol) | `settings` (JSON, contraseña cifrada con `APP_KEY`) |

Para eliminar todos los datos del plugin al desinstalar:

```sql
DELETE FROM devices_attribs WHERE attrib_type IN ('webssh_username','webssh_password','webssh_port');
```

---

## Autor

**danpal-dev**
- GitHub: [@danpal-dev](https://github.com/danpal-dev)

---

## Licencia

MIT
