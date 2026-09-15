# ❓ FAQ & Problemas · WebSSH LibreNMS

Lista de los errores más frecuentes reportados con su **causa identificada** y **solución paso a paso**.

---

## 01 · Al pulsar "Conectar" terminal muestra `[WS ERROR] No se pudo conectar al servidor WebSSH`

### Causa
El navegador **no llega** a `wss://TU_DOMINIO/ws/ssh`. Casi siempre falta el bloque de **proxy WebSocket en Nginx**.

### Solución
Edita tu site-available Nginx de LibreNMS y **antes del bloque `location /`** agrega:
```nginx
location /ws/ {
    proxy_pass         http://127.0.0.1:8765;
    proxy_http_version 1.1;
    proxy_set_header   Upgrade           $http_upgrade;
    proxy_set_header   Connection        "Upgrade";
    proxy_set_header   Host              $host;
    proxy_set_header   X-Real-IP         $remote_addr;
    proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
    proxy_set_header   X-Forwarded-Proto $scheme;
    proxy_read_timeout 3600; # Sesiones largas > 1h
    proxy_send_timeout 3600;
}
```
Guarda, valida sintaxis y recarga:
```bash
nginx -t && sudo systemctl reload nginx
```
Verifica: `curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" -H "Host: $(hostname -f)" -H "Origin: https://$(hostname -f)" https://$(hostname -f)/ws/ssh` → debe devolver `HTTP/1.1 101 Switching Protocols` o error 401 del servidor Python (nunca 404 de Nginx).

---

## 02 · Estado se queda en "Conectando..." >30s y luego Error

### Causa
`librenms-webssh.service` no está corriendo, está en puerto distinto, o **firewall** del loopback está filtrando 8765.

### Solución
```bash
sudo systemctl status librenms-webssh.service
# Si no está:
sudo systemctl enable librenms-webssh.service
sudo systemctl start  librenms-webssh.service
# Verifica escucha en 8765:
ss -tlnp | grep 8765
# Si falla el puerto: ver en Ajustes WebSSH > "Puerto WebSocket interno" coincide con el argumento --port del .service.
```

---

## 03 · `[ERROR] Invalid or expired token`

### Causa
Desincronía entre el **`.webssh_secret` del frontend (Laravel)** y el que lee `webssh-server.py`. O bien:
- Regeneraste el archivo secret y **no reiniciaste `librenms-webssh`**; o
- Hora del servidor desincronizada > ±30 s (skew NTP).

### Solución
1. Regenera secret y sincroniza ambos:
```bash
sudo -u librenms php -r 'echo bin2hex(random_bytes(32)).PHP_EOL;' > /opt/librenms/.webssh_secret
chown librenms:librenms /opt/librenms/.webssh_secret
chmod 640 /opt/librenms/.webssh_secret
sudo systemctl restart librenms-webssh.service
```
2. Sincroniza NTP: `chronyc sources -v` o `ntpq -p`. El token expira a los **60 s** por defecto.

---

## 04 · El desplegable de dispositivos está **vacío**

### Causa 1 — No hay dispositivos accesibles
El usuario no tiene `Device::hasAccess()` sobre ningún RouterOS/FortiGate y tampoco hay dispositivos generales `status=1` visibles.

### Solución
1. Entra como admin en LibreNMS: comprueba `Dispositivos → Todos`. Si no hay dispositivos, no es un problema del plugin.
2. Pestaña Permisos: en `Dispositivos → Permisos` asigna dispositivos o device-groups al usuario (rol `global-read` evita esto).

### Causa 2 — Menú visible pero página "/plugin/WebSSH" retorna 404
❌ **Esta es la causa más frecuente.** El `authorize()` no-nullable causó TypeError en `PluginManager` → actualizó `plugins.plugin_active=0` en BD.

### Solución (patrón global)
```sql
UPDATE librenms.plugins SET plugin_active = 1 WHERE plugin_name='WebSSH';
```
Y asegura los hooks autorización tienen **tipado nullable** (ya lo tiene este release 1.2.0).

---

## 05 · `[ERROR] Authentication failed connecting to device X`

### Causa
Credenciales SSH erróneas (global o del dispositivo).

### Solución paso a paso
1. Prueba primero desde la propia máquina LibreNMS como usuario `librenms`:
```bash
sudo -u librenms ssh -o StrictHostKeyChecking=no monitor@IP_EQUIPO -p PORT
# introduce la contraseña de monitor
```
Si falla → el problema **no es del plugin**, es de conectividad SSH / credencial / firewall entre LibreNMS y el dispositivo.
2. Si funciona arriba, sobrescribe credenciales en:
   - **Ajustes WebSSH → tabla por dispositivo → fila del equipo** (override).
   - O usa credencial global.
3. Si usaste clave pública SSH, ten en cuenta que la 1.2.0 usa **password** vía Paramiko. Para llave privada hay que editar `webssh-server.py` línea `client.connect(..., key_filename='/opt/librenms/.ssh/id_rsa')`.

---

## 06 · Terminal muestra caracteres extraños / codificación al revés / tildes rotas

### Causa
**Locale no UTF-8** en el router/firewall remoto o en `python3` del server.

### Solución
1. Agrega en `/etc/systemd/system/librenms-webssh.service` bajo `[Service]`:
```ini
Environment=LANG=C.UTF-8
Environment=LC_ALL=C.UTF-8
```
2. `sudo systemctl daemon-reload && sudo systemctl restart librenms-webssh.service`.
3. FortiOS: `config system global → set admin-ssh-gui-locale en` (no influye en CLI pero evita algunas emisiones no ASCII).

---

## 07 · Botón de "Conectar" no se activa al elegir dispositivo

### Causa
El `<select>` cambió de valor pero **el listener `change` no se ejecutó**. Suede pasar al navegar con teclado TAB + flechas.

### Solución
Haz clic manualmente fuera del select o actualiza página (F5). Si persiste revisa en DevTools Consola JS: si hay `TypeError: Cannot read properties of null (reading 'addEventListener')` entonces algún script anterior rompió la ejecución (normalmente terceros de Google Tag / Analytics). Desactívalos temporalmente para confirmar.

---

## 08 · Almacenar contraseña "no cambia" (sigue mostrando la antigua)

### Causa
Si dejas el input `<password>` **vacío** y guardas, `PluginObserver` interpreta: el admin quiere mantener la clave existente (comportamiento intencional). También pasa si cambias la APP_KEY después de encriptar.

### Solución
- **Cambiar contraseña**: rellena el input de password con la nueva y guarda.
- **Contraseña corrupta tras APP_KEY cambiada**:
  1. Entra en la tabla `plugins` → columna `settings` (JSON): busca `"ssh_password":"..."` y borra el key+value manualmente o sobrescribe el valor con `""`.
  2. Vuelve a Ajustes WebSSH y guarda la nueva. Haz lo mismo en `devices_attribs.attrib_type='webssh_password'` si usaste overrides por equipo (están cifrados con Crypt).

---

## 09 · Sesión se cierra sola cada ~60 s de inactividad

### Causa
`proxy_read_timeout` Nginx o `read_timeout` del `webssh-server.py` cierran canales silenciosos sin tráfico.

### Solución
1. Editar Nginx `/ws/`: `proxy_read_timeout 3600;` y `proxy_send_timeout 3600;` (reload).
2. Editar servicio systemd: argumento `--idle 900` al ejecutable.
3. Opción adicional (recomendada): en RouterOS habilita `keepalive`:
```
/ip ssh set always-allow-password-login=yes strong-crypto=yes
```
Y en CLI interactiva `:local k do={:delay 55}` para mantener tráfico cada 55s si haces tareas de espera.

---

## 10 · Error 500 al guardar ajustes / credenciales

### Causa
`PluginObserver` recibe Throwable al **cifrar/descifrar** con APP_KEY o se intenta escribir en tabla sin esquema compatible.

### Solución
```bash
tail -n 100 -f /opt/librenms/logs/librenms.log | grep -i "webssh\|crypt\|app_key"
```
- Si ves `The payload is invalid / MAC is invalid` → **APP_KEY cambiada**. Borra valores cifrados (FAQ 08) y reintroduce.
- Si `Base table or view not found: devices_attribs` → corre `lnms migrate:status` y `./daily.sh` (no se debe dar en instancias normales).

---

## Logs útiles para cualquier problema

```bash
# 1) LibreNMS general
sudo tail -n 100 /opt/librenms/logs/librenms.log
# 2) Server WebSSH Python (más útil)
journalctl -u librenms-webssh.service --since today -f
# 3) PHP-FPM (Laravel / PluginManager TypeError)
sudo tail -n 50 /var/log/php*-fpm.log
# 4) Nginx (WebSocket 404 / 502)
sudo tail -n 50 /var/log/nginx/error.log
```
Al reportar un bug adjunta siempre la combinación de los logs 1 + 2 + Consola Browser DevTools Network del request `/ws/ssh`.
