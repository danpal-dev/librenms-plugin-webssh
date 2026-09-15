{{-- WebSSH — Ajustes. Fragmento interno (no @extends). --}}
@php
$wss_v = $plugin_version ?? '1.2.0';
@endphp
<div id="wsss-root" class="container-fluid py-4 wss-flowbite-ui">
<style>
  :root {
    --wsss-primary: var(--fb-primary, #2563eb);
    --wsss-primary-hover: var(--fb-primary-hover, #1d4ed8);
    --wsss-brand-500: var(--fb-brand-500, #3b82f6);
    --wsss-font: var(--fb-font-family, 'Inter', system-ui, sans-serif);
    --wsss-border: var(--fb-border, #e5e7eb);
    --wsss-surface: var(--fb-surface, #ffffff);
    --wsss-radius: 14px;
    --wsss-danger: #dc2626;
    --wsss-warning: #d97706;
    --wsss-success: #059669;
  }
  #wsss-root { font-family: var(--wsss-font); color: #111827; line-height: 1.55; }
  #wsss-root h2 { font-size: 22px; font-weight: 700; margin: 0 0 14px; color: #111827; }
  #wsss-root h3 { font-size: 15px; font-weight: 700; margin: 0 0 10px; }
  #wsss-root h4 { font-size: 14px; font-weight: 600; margin: 0 0 12px; }
  #wsss-root p { color: #4b5563; margin: 0 0 10px; }
  #wsss-root label { font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
  .wsss-head-wrap { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; flex-wrap: wrap; }
  .wsss-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; padding: 5px 12px; border-radius: 9999px; background: color-mix(in srgb, var(--wsss-primary) 12%, #fff); color: var(--wsss-primary); font-weight: 600; letter-spacing: .2px; border: 1px solid color-mix(in srgb, var(--wsss-primary) 20%, #fff); }
  .wsss-pill-green { background:#ecfdf5; color:#059669; border-color:#a7f3d0; }
  .wsss-pill-red   { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
  .wsss-pill-amber { background:#fffbeb; color:#d97706; border-color:#fde68a; }

  .wsss-banner { background: linear-gradient(135deg, #1e2432 0%, var(--wsss-primary) 100%); color: #fff; border-radius: var(--wsss-radius); padding: 24px 24px; margin-bottom: 22px; box-shadow: 0 6px 18px rgba(0,0,0,.08); border: 1px solid color-mix(in srgb, var(--wsss-primary) 45%, #1e2432); }
  .wsss-banner-row { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
  .wsss-icon { background: rgba(255,255,255,.15); border-radius: 14px; padding: 14px; flex-shrink: 0; }
  .wsss-icon i { font-size: 24px; }
  .wsss-banner-title { font-size: 20px; font-weight: 700; margin: 0 0 3px; }
  .wsss-banner-sub { font-size: 12.5px; opacity: .85; margin: 0; }
  .wsss-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-left: auto; }
  .wsss-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 13px; text-decoration: none; border: none; cursor: pointer; transition: all .15s; white-space: nowrap; }
  .wsss-btn-primary { background: linear-gradient(135deg, var(--wsss-primary) 0%, var(--wsss-primary-hover) 100%); color: #fff; box-shadow: 0 4px 12px color-mix(in srgb, var(--wsss-primary) 28%, transparent); }
  .wsss-btn-primary:hover { transform: translateY(-1px); color: #fff; }
  .wsss-btn-danger { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: #fff; box-shadow: 0 4px 12px rgba(220, 38, 38, .25); }
  .wsss-btn-danger:hover { transform: translateY(-1px); color: #fff; }
  .wsss-btn-success{ background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: #fff; }
  .wsss-btn-success:hover { transform: translateY(-1px); color: #fff; }
  .wsss-btn-ghost   { background: #fff; color: #374151; border: 1px solid var(--wsss-border); }
  .wsss-btn-ghost:hover { background: #f9fafb; color: #111827; }
  .wsss-btn-xs { padding: 5px 10px; font-size: 11.5px; border-radius: 7px; }
  .wsss-btn-sm { padding: 7px 14px; font-size: 12px; border-radius: 8px; }

  .wsss-card { background: var(--wsss-surface); border: 1px solid var(--wsss-border); border-radius: var(--wsss-radius); padding: 22px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
  .wsss-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 10px; flex-wrap: wrap; }
  .wsss-card-title { display: flex; align-items: center; gap: 10px; }
  .wsss-card-title i { color: var(--wsss-primary); font-size: 16px; }

  .wsss-alert { border-radius: 12px; padding: 14px 18px; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 12px; border: 1px solid; }
  .wsss-alert i { font-size: 20px; margin-top: 1px; flex-shrink: 0; }
  .wsss-alert strong { display: block; font-size: 13.5px; margin-bottom: 3px; }
  .wsss-alert span, .wsss-alert p { font-size: 12px; line-height: 1.55; margin: 0; }
  .wsss-alert code { background: rgba(0,0,0,.06); padding: 1px 6px; border-radius: 4px; font-family: ui-monospace, monospace; font-size: 11px; }
  .wsss-alert-warn  { background:#fffbeb; color:#78350f; border-color:#fde68a; }
  .wsss-alert-warn  i { color: var(--wsss-warning); }
  .wsss-alert-info  { background:#eff6ff; color:#1e3a8a; border-color:#bfdbfe; }
  .wsss-alert-info  i { color: var(--wsss-primary); }
  .wsss-alert-success{ background:#ecfdf5; color:#064e3b; border-color:#a7f3d0; }
  .wsss-alert-success i { color: var(--wsss-success); }

  .wsss-input { display: block; width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid var(--wsss-border); border-radius: 9px; background: #fff; color: #111827; transition: border-color .15s, box-shadow .15s; font-family: inherit; }
  .wsss-input:focus { outline: none; border-color: var(--wsss-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--wsss-primary) 15%, transparent); }
  .wsss-input-sm { padding: 6px 10px; font-size: 12px; border-radius: 7px; }
  .wsss-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z' clip-rule='evenodd'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
  .wsss-grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
  .wsss-form-group { margin-bottom: 14px; }
  .wsss-muted { font-size: 11.5px; color: #6b7280; margin-top: 4px; line-height: 1.5; }

  .wsss-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12.5px; border: 1px solid var(--wsss-border); border-radius: 10px; overflow: hidden; }
  .wsss-table th { background: #f9fafb; color: #111827; font-weight: 700; text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--wsss-border); font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; }
  .wsss-table td { padding: 9px 12px; border-bottom: 1px solid var(--wsss-border); color: #374151; vertical-align: top; }
  .wsss-table tr:last-child td { border-bottom: 0; }
  .wsss-table tr:hover td { background: #fafbfc; }
  .wsss-label-os { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 700; padding: 3px 9px; border-radius: 9999px; }
  .wsss-label-info { background: #dbeafe; color: #1e40af; }
  .wsss-label-warn { background: #fef3c7; color: #92400e; }

  .wsss-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 6px; padding-top: 14px; border-top: 1px dashed var(--wsss-border); }
  .wsss-foot small { color: #6b7280; font-size: 11.5px; }
</style>

{{-- HEADER: título + pills versión y estado --}}
<div class="wsss-head-wrap">
  <h2 style="margin:0;"><i class="fa fa-cog" style="color: var(--wsss-primary); margin-right: 6px;"></i> Configuración · Plugin WebSSH</h2>
  <span class="wsss-pill"><i class="fa fa-tag" style="font-size:10px;"></i> v{{ $wsss_v }}</span>
  <span class="wsss-pill wsss-pill-green">
    <i class="fa fa-lock" style="font-size:10px;"></i> CIFRADO AES ACTIVO
  </span>
</div>

{{-- BANNER: resumen propósito + acciones rápidas --}}
<div class="wsss-banner">
  <div class="wsss-banner-row">
    <div class="wsss-icon"><i class="fa fa-key" aria-hidden="true"></i></div>
    <div style="flex:1; min-width: 240px;">
      <div class="wsss-banner-title">Credenciales & Ajustes de Conexión SSH</div>
      <div class="wsss-banner-sub">Usuario/contraseña globales o por dispositivo. Todas las contraseñas se cifran con AES-256 usando APP_KEY de LibreNMS.</div>
    </div>
    <div class="wsss-actions">
      <a href="{{ url('plugin/WebSSH') }}" class="wsss-btn" style="background: rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.25);">
        <i class="fa fa-terminal"></i> Abrir terminal
      </a>
    </div>
  </div>
</div>

{{-- CARD 1: FORMULARIO GLOBAL --}}
<div class="wsss-card">
  <div class="wsss-card-header">
    <div class="wsss-card-title"><i class="fa fa-user-secret"></i><h3>Credenciales SSH globales + Puertos</h3></div>
    <span class="wsss-muted">Se usan como fallback cuando un dispositivo no tiene credenciales propias.</span>
  </div>
  <form method="POST" action="{{ url('plugin/settings/WebSSH') }}">
    @csrf
    <div class="wsss-grid-2">
      <div>
        <h4 style="margin-bottom: 10px;"><i class="fa fa-user-circle" style="color: var(--wsss-primary);"></i> Credenciales SSH (usuario de monitoreo)</h4>
        <div class="wsss-form-group">
          <label>Usuario SSH</label>
          <input type="text" name="settings[ssh_username]" class="wsss-input"
                 value="{{ $ssh_username }}" placeholder="monitor" autocomplete="off" />
        </div>
        <div class="wsss-form-group">
          <label>Contraseña SSH</label>
          <input type="password" name="settings[ssh_password]" class="wsss-input"
                 value="" placeholder="{{ $ssh_password ? '••••••••  (guardada)' : 'Ingrese contraseña' }}"
                 autocomplete="new-password" />
          <div class="wsss-muted">
            @if($ssh_password)
                <i class="fa fa-lock text-success"></i> Contraseña guardada y encriptada (AES-256). Déjelo vacío para no cambiarla.
            @else
                Se almacenará encriptada con AES-256 (APP_KEY).
            @endif
          </div>
        </div>
      </div>

      <div>
        <h4 style="margin-bottom: 10px;"><i class="fa fa-plug" style="color: var(--wsss-primary);"></i> Puertos SSH por tipo de dispositivo</h4>
        <div class="wsss-form-group">
          <label>Puerto MikroTik (RouterOS)</label>
          <input type="number" name="settings[ssh_port_mikrotik]" class="wsss-input"
                 value="{{ $ssh_port_mikrotik }}" min="1" max="65535" />
        </div>
        <div class="wsss-form-group">
          <label>Puerto FortiGate</label>
          <input type="number" name="settings[ssh_port_fortigate]" class="wsss-input"
                 value="{{ $ssh_port_fortigate }}" min="1" max="65535" />
        </div>
        <div class="wsss-form-group">
          <label>Puerto WebSocket interno</label>
          <input type="number" name="settings[ws_port]" class="wsss-input"
                 value="{{ $ws_port }}" min="1024" max="65535" />
          <div class="wsss-muted">Puerto donde escucha el servidor Python WebSSH (default: 8765 loopback).</div>
        </div>
      </div>
    </div>

    <div class="wsss-alert wsss-alert-info" style="margin-top: 12px;">
      <i class="fa fa-info-circle"></i>
      <div style="flex:1;">
        <strong>Acceso de solo monitoreo</strong>
        <span>El usuario SSH debe tener únicamente privilegios de lectura en los dispositivos. MikroTik: grupo <code>read</code>. FortiGate: perfil <code>prof_admin</code> con acceso SSH read-only.</span>
      </div>
    </div>

    <div class="wsss-form-group" style="max-width: 360px; margin-top: 12px;">
      <label for="min_role">Acceso mínimo requerido (menú + página)</label>
      <select id="min_role" class="wsss-input wsss-select" name="settings[min_role]">
        <option value="user"        {{ ($min_role ?? 'user') === 'user'        ? 'selected' : '' }}>Cualquier usuario (user)</option>
        <option value="global-read" {{ ($min_role ?? 'user') === 'global-read' ? 'selected' : '' }}>Lectura global (global-read)</option>
        <option value="admin"       {{ ($min_role ?? 'user') === 'admin'       ? 'selected' : '' }}>Solo administrador (admin)</option>
      </select>
      <div class="wsss-muted">Controla quién puede ver el menú y la página del terminal SSH.</div>
    </div>

    <div class="wsss-foot">
      <button type="submit" class="wsss-btn wsss-btn-primary">
        <i class="fa fa-save"></i> Guardar Configuración
      </button>
      <small>
        <i class="fa fa-shield" style="color: var(--wsss-success);"></i>
        Los cambios se persisten en <code>plugins.settings</code> JSON y sobreviven actualizaciones.
      </small>
    </div>
  </form>
</div>

{{-- CARD 2: TABLA CREDENCIALES POR DISPOSITIVO --}}
<div class="wsss-card">
  <div class="wsss-card-header">
    <div class="wsss-card-title"><i class="fa fa-server"></i><h3>Credenciales SSH por dispositivo</h3></div>
    <span class="wsss-muted">Override por dispositivo. Vacío = usará credenciales globales.</span>
  </div>
  <p class="wsss-muted" style="margin: 0 0 14px;">Configura usuario, contraseña y puerto SSH para cada dispositivo. Si se deja vacío, se usan las credenciales globales.</p>

  @if(empty($devices))
    <div class="wsss-alert wsss-alert-warn">
      <i class="fa fa-exclamation-triangle"></i>
      <div style="flex:1;"><strong>Sin dispositivos compatibles</strong><span>No hay dispositivos RouterOS o FortiGate registrados en LibreNMS.</span></div>
    </div>
  @else
  <table class="wsss-table" id="tbl-device-creds">
    <thead>
      <tr>
        <th>Dispositivo</th>
        <th style="width:90px;">OS</th>
        <th style="width:170px;">Usuario SSH</th>
        <th style="width:170px;">Contraseña SSH</th>
        <th style="width:90px;">Puerto</th>
        <th style="width:110px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
    @foreach($devices as $dev)
    <tr id="row-dev-{{ $dev->device_id }}">
      <td><strong>{{ $dev->hostname }}</strong>@if($dev->sysName && $dev->sysName !== $dev->hostname) <br><small class="wsss-muted">{{ $dev->sysName }}</small>@endif</td>
      <td><span class="wsss-label-os {{ str_contains($dev->os,'route') || str_contains($dev->os,'mikro') ? 'wsss-label-info' : 'wsss-label-warn' }}">{{ $dev->os }}</span></td>
      <td>
        <input type="text" class="wsss-input wsss-input-sm dev-username"
               data-id="{{ $dev->device_id }}"
               value="{{ $dev->ssh_username ?? '' }}"
               placeholder="(global)" autocomplete="off" />
      </td>
      <td>
        <input type="password" class="wsss-input wsss-input-sm dev-password"
               data-id="{{ $dev->device_id }}"
               value=""
               placeholder="{{ $dev->has_password ? '•••••• (guardada)' : '(global)' }}"
               autocomplete="new-password" />
      </td>
      <td>
        <input type="number" class="wsss-input wsss-input-sm dev-port"
               data-id="{{ $dev->device_id }}"
               value="{{ $dev->ssh_port ?? '' }}"
               placeholder="(global)" min="1" max="65535" />
      </td>
      <td>
        <button class="wsss-btn wsss-btn-xs wsss-btn-primary btn-save-dev" data-id="{{ $dev->device_id }}" title="Guardar">
          <i class="fa fa-save"></i>
        </button>
        @if($dev->ssh_username || $dev->has_password || $dev->ssh_port)
        <button class="wsss-btn wsss-btn-xs wsss-btn-ghost btn-del-dev" data-id="{{ $dev->device_id }}" title="Eliminar y usar global" style="margin-left:2px;">
          <i class="fa fa-trash"></i>
        </button>
        @endif
        <span class="dev-msg-{{ $dev->device_id }}" style="display:block;font-size:10.5px;margin-top:4px;min-height:14px;"></span>
      </td>
    </tr>
    @endforeach
    </tbody>
  </table>
  @endif
</div>
</div>

<script>
(function(){
    function devMsg(id, text, cls) {
        var el = document.querySelector('.dev-msg-' + id);
        if (!el) return;
        el.textContent = text;
        el.className = 'dev-msg-' + id + (
            cls === 'success' ? ' text-success' :
            cls === 'danger'  ? ' text-danger'  : ' text-muted'
        );
        if (cls === 'success') el.style.color = '#059669';
        else if (cls === 'danger') el.style.color = '#dc2626';
        else el.style.color = '#6b7280';
        el.style.fontWeight = '600';
        setTimeout(function(){ el.textContent = ''; }, 3500);
    }

    function getToken() {
        var match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-save-dev');
        if (btn) {
            var id = btn.dataset.id;
            var row = document.getElementById('row-dev-' + id);
            var username = row.querySelector('.dev-username').value;
            var password = row.querySelector('.dev-password').value;
            var port     = row.querySelector('.dev-port').value;

            fetch('{{ url("webssh/device") }}/' + id + '/creds', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-XSRF-TOKEN': getToken()},
                body: JSON.stringify({username: username, password: password || undefined, port: port || undefined})
            })
            .then(r => r.json())
            .then(function(d) {
                if (d.ok) {
                    devMsg(id, '✓ Guardado', 'success');
                    row.querySelector('.dev-password').placeholder = '•••••• (guardada)';
                    row.querySelector('.dev-password').value = '';
                    if (!row.querySelector('.btn-del-dev')) {
                        var delBtn = document.createElement('button');
                        delBtn.className = 'wsss-btn wsss-btn-xs wsss-btn-ghost btn-del-dev';
                        delBtn.dataset.id = id;
                        delBtn.title = 'Eliminar y usar global';
                        delBtn.style.marginLeft = '2px';
                        delBtn.innerHTML = '<i class="fa fa-trash"></i>';
                        btn.parentNode.insertBefore(delBtn, btn.nextSibling);
                    }
                } else {
                    devMsg(id, d.message || 'Error', 'danger');
                }
            })
            .catch(function(){ devMsg(id, 'Error de red', 'danger'); });
        }

        var del = e.target.closest('.btn-del-dev');
        if (del) {
            var id = del.dataset.id;
            if (!confirm('¿Eliminar credenciales propias de este dispositivo?')) return;
            fetch('{{ url("webssh/device") }}/' + id + '/creds', {
                method: 'DELETE',
                headers: {'Content-Type':'application/json','X-XSRF-TOKEN': getToken()}
            })
            .then(r => r.json())
            .then(function(d) {
                if (d.ok) {
                    var row = document.getElementById('row-dev-' + id);
                    row.querySelector('.dev-username').value = '';
                    row.querySelector('.dev-password').value = '';
                    row.querySelector('.dev-password').placeholder = '(global)';
                    row.querySelector('.dev-port').value = '';
                    del.remove();
                    devMsg(id, '✓ Eliminado', 'success');
                } else {
                    devMsg(id, d.message || 'Error', 'danger');
                }
            })
            .catch(function(){ devMsg(id, 'Error de red', 'danger'); });
        }
    });
})();
</script>
