# 📦 Instalación & Persistencia · WebSSH LibreNMS

Versionado: `release 1.2.0` (Flowbite UI + 4 docs integrados + authorize nullable fixes).

---

## ✅ Prerrequisitos mínimos

| Componente | Versión mínima | Comprobación |
|---|---|---|
| PHP | 8.1+ (8.3/8.4 recomendado) | `php -v` |
| LibreNMS | 24.x+ con sistema `app/Plugins/` hooks | `ls /opt/librenms/app/Plugins/` no esté vacío |
| Python3 | ≥ 3.8 | `python3 --version` |
| Paramiko | ≥ 3.0 (SSH en Python) | `python3 -c 'import paramiko; print(paramiko.__version__)'` |
| Websockets / aiohttp | ≥ 3.8 | `python3 -c 'import aiohttp, websockets; print(aiohttp.__version__, websockets.__version__)'` |
| systemd | ≥ 245 | `systemd --version` |
| Nginx | ≥ 1.18 (para WebSocket Upgrade) | `nginx -v` |

### Instalar dependencias Python (1 única vez)
```bash
sudo apt install -y python3-paramiko python3-websockets python3-aiohttp python3-h2
# Alternativa pip (no recomendado si hay paquetes Debian):
# sudo pip3 install --break-system-packages paramiko websockets aiohttp h2 cryptography
```

---

## 🧪 Comprobar soporte de plugins en LibreNMS
```bash
grep -n "PluginProvider\|Hooks" /opt/librenms/bootstrap/providers.php
# O verifica la ruta real de carga:
grep -rn "glob(app_path('Plugins" /opt/librenms/app/Providers/ 2>/dev/null | head
```
Si existe, está OK. Si no: actualiza LibreNMS a una build reciente.

---

## 🚀 Instalación paso a paso

### Paso 1 — Colocar plugin en el path correcto
**Esta instalación asume que ya existe la carpeta** (setup normal desde upstream). Si instalas desde cero con backup cloud:
```bash
cd /opt/librenms/app/Plugins
sudo git clone git@github.com:TU_ORG/librenms-plugin-webssh.git WebSSH
cd WebSSH
sudo git checkout 1.2.0   # tag release
sudo chown -R librenms:librenms . .git
```

### Paso 2 — Generar `.webssh_secret` compartido (32 bytes HMAC)
```bash
sudo -u librenms php -r 'echo bin2hex(random_bytes(32)).PHP_EOL;' > /opt/librenms/.webssh_secret
sudo chown librenms:librenms /opt/librenms/.webssh_secret
sudo chmod 640 /opt/librenms/.webssh_secret
```
⚠️ **NO subir este archivo a Git.** Está contemplado en el `.gitignore`.

### Paso 3 — Instalar servicio systemd
Copia el unit al systemd path y habilítalo:
```bash
sudo cp /opt/librenms/app/Plugins/WebSSH/librenms-webssh.service /etc/systemd/system/librenms-webssh.service
sudo chmod 644 /etc/systemd/system/librenms-webssh.service
# Edita el unit si necesitas --ws-port, --host, --idle custom:
#   sudo nano /etc/systemd/system/librenms-webssh.service
sudo systemctl daemon-reload
sudo systemctl enable librenms-webssh.service
sudo systemctl start  librenms-webssh.service
sudo systemctl status librenms-webssh.service
```

Resultado esperado: `Active: active (running)` escuchando en `127.0.0.1:8765`. Si expones `0.0.0.0` sin TLS delante **tendrás un agujero de seguridad.**

### Paso 4 — Configurar regla SUDOERS (start/stop servicio desde UI)
Opcional pero recomendado si quieres botones de "Reiniciar WebSSH" en ajustes futuros:
```bash
echo 'librenms ALL=(ALL) NOPASSWD: /bin/systemctl start librenms-webssh.service, /bin/systemctl stop librenms-webssh.service, /bin/systemctl restart librenms-webssh.service, /bin/systemctl status librenms-webssh.service' \
  | sudo tee /etc/sudoers.d/librenms-webssh-plugin
sudo chmod 440 /etc/sudoers.d/librenms-webssh-plugin
sudo visudo -cf /etc/sudoers.d/librenms-webssh-plugin   # debe retornar "parse OK"
```

### Paso 5 — Proxy reverso Nginx `/ws/`
Añade esta location al server block del site HTTPS de LibreNMS:
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
    proxy_read_timeout 3600;
    proxy_send_timeout 3600;
}
```
Guarda y `nginx -t && sudo systemctl reload nginx`.

### Paso 6 — Activar el plugin en LibreNMS
1. Login como `admin`.
2. `Configuración → Plugins → WebSSH → Enable`.
3. Luego entra en **Settings** del plugin y configura credenciales globales / puertos / `min_role`. Guarda.
4. Test: entra en `/plugin/WebSSH/` → elige dispositivo → Conectar.

---

## 🧩 Actualizaciones (Upgrade 1.x → 1.2.0)
Si vienes de una versión anterior (1.0.x / 1.1.x):
```bash
cd /opt/librenms/app/Plugins/WebSSH
sudo -u librenms git pull origin main          # o tag v1.2.0
sudo chown -R librenms:librenms . .git
sudo systemctl restart librenms-webssh.service
```
Y si cambió `librenms-webssh.service` en la release:
```bash
sudo cp /opt/librenms/app/Plugins/WebSSH/librenms-webssh.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl restart librenms-webssh.service
```

---

## 🔄 Desinstalación completa

1. **Desactiva plugin** desde `Configuración → Plugins → WebSSH → Disable`.
2. **Detén y desinstala servicio**:
```bash
sudo systemctl stop    librenms-webssh.service
sudo systemctl disable librenms-webssh.service
sudo rm /etc/systemd/system/librenms-webssh.service
sudo systemctl daemon-reload
```
3. **Limpia datos persistentes** (opcional — si no los quieres para una reinstalación):
```sql
DELETE FROM librenms.devices_attribs WHERE attrib_type IN ('webssh_username','webssh_password','webssh_port');
DELETE FROM librenms.plugins WHERE plugin_name='WebSSH';
```
4. **Elimina archivos plugin y secret**:
```bash
sudo rm -rf /opt/librenms/app/Plugins/WebSSH
sudo rm -f  /opt/librenms/.webssh_secret
sudo rm -f  /etc/sudoers.d/librenms-webssh-plugin
```
5. **Remueve bloque `/ws/` en Nginx**, guarda y reload.

---

## 🛟 PERSISTENCIA POST ACTUALIZACIÓN (OBLIGATORIO LEER)

LibreNMS corre `daily.sh` a medianoche que hace `git pull` sobre **el repositorio padre de LibreNMS** (`/opt/librenms/.git`). Si el plugin **no tiene su propio `.git`** podría ser borrado o sobreescrito.

### Buenas noticias
El release 1.2.0 incluye **sub-repositorio Git independiente** dentro de `/opt/librenms/app/Plugins/WebSSH/.git/`. **Git padre NO toca carpetas que contengan su propio `.git/`** (las ignora como submódulos). El plugin sobrevive intacto a cualquier `daily.sh / git pull` de LibreNMS.

### Comprueba que tu instalación lo tiene
```bash
ls -la /opt/librenms/app/Plugins/WebSSH/.git/HEAD
# esperado: salida normal, no "No such file"
cd /opt/librenms/app/Plugins/WebSSH && sudo -u librenms git log -1 --oneline
# esperado: hash corto + "release 1.2.0 ..."
```

### ☁️ Backups cloud (opción recomendada)
Si no tienes backup remoto y tu servidor se cae, puedes perder el plugin. **Configura un remote privado GitHub/GitLab/Gitea**:
```bash
cd /opt/librenms/app/Plugins/WebSSH
sudo -u librenms git remote add origin git@github.com:TU_ORG/librenms-plugin-webssh.git
sudo -u librenms git branch -M main
sudo -u librenms git push -u origin main
# Para guardar cambios futuros:
sudo -u librenms git add -A
sudo -u librenms git -c user.email=webssh@local -c user.name=WebSSHBot commit -m "release X.Y.Z: ..."
sudo -u librenms git push
```

### 🚨 Recuperación post-borrado accidental
Si por algún motivo la carpeta `WebSSH/` desaparece:
```bash
# 1) Reinstala desde backup cloud
cd /opt/librenms/app/Plugins
sudo git clone git@github.com:TU_ORG/librenms-plugin-webssh.git WebSSH
cd WebSSH
sudo git checkout 1.2.0
sudo chown -R librenms:librenms . .git
# 2) Reinstala servicio + secret (si el secret no lo guardaste → regenera y reinicia webssh)
sudo cp /opt/librenms/app/Plugins/WebSSH/librenms-webssh.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl enable librenms-webssh && sudo systemctl start librenms-webssh
# 3) Activa plugin en la UI si plugins.plugin_active quedó a 0:
sudo -u librenms php -r 'require "/opt/librenms/vendor/autoload.php"; $app=require "/opt/librenms/bootstrap/app.php"; $app->make("Illuminate\\Contracts\\Console\\Kernel")->bootstrap(); \App\Models\Plugin::where("plugin_name","WebSSH")->update(["plugin_active"=>1]);'
```
---

## ✅ Checklist post-instalación

- [ ] `systemctl status librenms-webssh.service` → active (running).
- [ ] `/ws/ssh` pasa por Nginx y devuelve `101 Switching Protocols` o 401 Python (nunca 404).
- [ ] `.webssh_secret` existe, propietario `librenms:librenms`, chmod `640`.
- [ ] Ajustes plugin guardados credenciales sin error.
- [ ] 1 dispositivo RouterOS/FortiGate visible al usuario test.
- [ ] Conexión SSH de extremo a extremo OK: Conectar → comando `ping` funciona.
- [ ] `.git/` del plugin existe y tiene push configurado a remote cloud.
- [ ] `plugins.plugin_active=1` y después de `daily.sh` sigue en 1.

Terminado esto ya puedes dar acceso a usuarios NOC con `min_role=global-read`.
