{{-- WebSSH — DeviceOverview hook (OFF por defecto). Fragmento interno (no @extends). --}}
@php
$wssdo_v = $plugin_version ?? '1.2.0';
@endphp
<div id="wssdo-root" class="wssdo-flowbite-ui" style="margin-bottom: 16px;">
<style>
  :root {
    --wssdo-primary: var(--fb-primary, #2563eb);
    --wssdo-primary-hover: var(--fb-primary-hover, #1d4ed8);
    --wssdo-font: var(--fb-font-family, 'Inter', system-ui, sans-serif);
    --wssdo-border: var(--fb-border, #e5e7eb);
    --wssdo-surface: var(--fb-surface, #ffffff);
    --wssdo-radius: 12px;
    --wssdo-danger: #dc2626;
    --wssdo-success: #059669;
    --wssdo-warning: #d97706;
  }
  #wssdo-root { font-family: var(--wssdo-font); color: #111827; line-height: 1.55; }
  #wssdo-root h3 { font-size: 14.5px; font-weight: 700; margin: 0; color: #111827; }
  #wssdo-root h4 { font-size: 13px; font-weight: 700; margin: 0 0 8px; }
  #wssdo-root label { font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; display: block; }
  .wssdo-card { background: var(--wssdo-surface); border: 1px solid var(--wssdo-border); border-radius: var(--wssdo-radius); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
  .wssdo-card-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 12px 16px; background: linear-gradient(135deg, #1e2432 0%, var(--wssdo-primary) 100%); color: #fff; cursor: pointer; user-select: none; }
  .wssdo-card-title { display: flex; align-items: center; gap: 10px; }
  .wssdo-card-title i { color: #fff; font-size: 15px; }
  .wssdo-label { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 700; padding: 3px 9px; border-radius: 9999px; letter-spacing: .3px; }
  .wssdo-label-green { background: #d1fae5; color: #065f46; }
  .wssdo-label-slate { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.25); }
  .wssdo-body { padding: 18px; }
  .wssdo-alert { border-radius: 9px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: flex-start; gap: 10px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1e3a8a; }
  .wssdo-alert i { color: var(--wssdo-primary); margin-top: 1px; }
  .wssdo-alert strong { display:block; font-size: 12.5px; margin-bottom: 2px; }
  .wssdo-alert span { font-size: 11.5px; line-height: 1.5; }
  .wssdo-grid-3 { display: grid; grid-template-columns: 2fr 2fr 1fr auto; gap: 12px; align-items: end; margin-bottom: 10px; }
  .wssdo-input { display: block; width: 100%; padding: 7px 10px; font-size: 12.5px; border: 1px solid var(--wssdo-border); border-radius: 7px; background: #fff; color: #111827; transition: border-color .15s, box-shadow .15s; font-family: inherit; }
  .wssdo-input:focus { outline: none; border-color: var(--wssdo-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--wssdo-primary) 15%, transparent); }
  .wssdo-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; font-weight: 600; font-size: 12px; border: none; cursor: pointer; transition: all .15s; white-space: nowrap; text-decoration: none; }
  .wssdo-btn:disabled { opacity: .5; cursor: not-allowed; }
  .wssdo-btn-primary { background: linear-gradient(135deg, var(--wssdo-primary) 0%, var(--wssdo-primary-hover) 100%); color: #fff; }
  .wssdo-btn-primary:hover:not(:disabled) { transform: translateY(-1px); color: #fff; }
  .wssdo-btn-danger { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: #fff; }
  .wssdo-btn-danger:hover:not(:disabled) { transform: translateY(-1px); color: #fff; }
  .wssdo-btn-ghost   { background: #fff; color: #374151; border: 1px solid var(--wssdo-border); }
  .wssdo-btn-ghost:hover { background: #f9fafb; color: #111827; }
  .wssdo-btn-sm { padding: 6px 11px; font-size: 11.5px; border-radius: 7px; }
  .wssdo-btn-group { display: inline-flex; flex-wrap: wrap; gap: 4px; }
  .wssdo-btn-quick { padding: 5px 11px; font-size: 11.5px; border-radius: 7px; background: #fff; color: #374151; border: 1px solid var(--wssdo-border); font-weight: 500; cursor: pointer; transition: all .15s; }
  .wssdo-btn-quick:hover { background: var(--wssdo-primary); color: #fff; border-color: var(--wssdo-primary); transform: translateY(-1px); }
  .wssdo-label-status { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 9999px; letter-spacing: .3px; }
  .wssdo-ls-default { background: #f1f5f9; color: #475569; }
  .wssdo-ls-success { background: #d1fae5; color: #065f46; }
  .wssdo-ls-warning { background: #fef3c7; color: #92400e; }
  .wssdo-ls-danger  { background: #fee2e2; color: #991b1b; }
  .wssdo-term-wrap { background: #0f172a; border: 1px solid #1e293b; border-radius: 10px; overflow: hidden; }
  .wssdo-term-head { display: flex; align-items: center; justify-content: space-between; padding: 8px 14px; background: #111c32; border-bottom: 1px solid #1e293b; color: #94a3b8; font-size: 11px; font-family: ui-monospace, monospace; }
  .wssdo-term-head strong { color: #e2e8f0; font-size: 12px; font-family: var(--wssdo-font); }
  .wssdo-term-inner { padding: 5px; }
  .wssdo-muted { font-size: 11px; color: #6b7280; }
  .wssdo-success-text { font-size: 11.5px; color: var(--wssdo-success); font-weight: 600; }
</style>

{{-- TARJETA PRINCIPAL --}}
<div class="wssdo-card">
  <div class="wssdo-card-header" data-toggle="collapse" data-target="#wssdo-panel">
    <div class="wssdo-card-title">
      <i class="fa fa-terminal"></i>
      <h3>{{ $title ?? 'Web SSH Terminal' }}</h3>
    </div>
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
      @if($has_device_creds ?? false)
        <span class="wssdo-label wssdo-label-green"><i class="fa fa-key"></i> Credenciales propias</span>
      @else
        <span class="wssdo-label wssdo-label-slate"><i class="fa fa-globe"></i> Usando globales</span>
      @endif
      <i class="fa fa-chevron-down" style="font-size: 11px; opacity:.8;"></i>
    </div>
  </div>
  <div id="wssdo-panel" class="panel-collapse collapse {{ ($has_device_creds ?? false) ? 'in' : '' }}">
    <div class="wssdo-body">

      {{-- SECCIÓN ADMIN: CREDENCIALES POR DISPOSITIVO --}}
      @can('admin')
      <div class="wssdo-alert">
        <i class="fa fa-info-circle"></i>
        <div style="flex:1;">
          <strong>Credenciales específicas (solo admin)</strong>
          <span>Personaliza usuario/contraseña/puerto para <strong>{{ $device->hostname ?? 'este dispositivo' }}</strong>. Déjalo vacío para usar las credenciales globales del plugin.</span>
        </div>
      </div>
      <form id="form-ssh-creds-{{ $device->device_id }}">
        <div class="wssdo-grid-3">
          <div>
            <label>Usuario SSH</label>
            <input type="text" name="username" class="wssdo-input"
                   value="{{ $device_username ?? '' }}"
                   placeholder="Usar global ({{ $global_username ?: 'no configurado' }})"
                   autocomplete="off" />
          </div>
          <div>
            <label>Contraseña SSH</label>
            <input type="password" name="password" class="wssdo-input"
                   value=""
                   placeholder="{{ ($has_device_creds ?? false) ? '•••••• (guardada)' : 'Usar global' }}"
                   autocomplete="new-password" />
            @if($has_device_creds ?? false)
              <div class="wssdo-success-text" style="margin-top:4px;"><i class="fa fa-lock"></i> Cifrada AES-256</div>
            @endif
          </div>
          <div>
            <label>Puerto SSH</label>
            <input type="number" name="port" class="wssdo-input"
                   value="{{ $device_port ?? '' }}"
                   placeholder="{{ $global_port ?? 'global' }}" min="1" max="65535" />
          </div>
          <div style="display: flex; flex-direction: column; gap: 5px;">
            <button type="button" id="btn-save-creds-{{ $device->device_id }}"
                    class="wssdo-btn wssdo-btn-primary wssdo-btn-sm" style="width:100%;">
              <i class="fa fa-save"></i> Guardar
            </button>
            @if($has_device_creds ?? false)
            <button type="button" id="btn-del-creds-{{ $device->device_id }}"
                    class="wssdo-btn wssdo-btn-ghost wssdo-btn-sm" style="width:100%;"
                    title="Eliminar credenciales propias">
              <i class="fa fa-trash"></i> Usar global
            </button>
            @endif
          </div>
        </div>
        <div id="creds-msg-{{ $device->device_id }}"></div>
      </form>
      <hr style="border:0; border-top: 1px dashed var(--wssdo-border); margin: 16px 0;">
      @endcan

      {{-- CONECTAR + STATUS --}}
      <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
        <button id="btn-ssh-connect-{{ $device->device_id }}" class="wssdo-btn wssdo-btn-primary wssdo-btn-sm">
          <i class="fa fa-plug"></i> Abrir Terminal SSH
        </button>
        <button id="btn-ssh-disc-{{ $device->device_id }}" class="wssdo-btn wssdo-btn-danger wssdo-btn-sm" style="display:none;">
          <i class="fa fa-times"></i> Desconectar
        </button>
        <span id="ssh-status-{{ $device->device_id }}" class="wssdo-label-status wssdo-ls-default">Desconectado</span>
        <span class="wssdo-muted" style="margin-left:auto;">
          <i class="fa fa-shield" style="color: var(--wssdo-success);"></i>
          Token HMAC caduco 60s · Cifrado WSS
        </span>
      </div>

      {{-- QUICK CMDS --}}
      <div id="ssh-quick-{{ $device->device_id }}" style="display:none; margin-bottom: 10px;">
        <label>Comandos rápidos</label>
        <div class="wssdo-btn-group">
          @if(str_contains(strtolower($device->os ?? ''), 'route') || str_contains(strtolower($device->os ?? ''), 'mikro'))
            <button class="wssdo-btn-quick ssh-quick" data-id="{{ $device->device_id }}" data-cmd="/ip/address/print">Interfaces</button>
            <button class="wssdo-btn-quick ssh-quick" data-id="{{ $device->device_id }}" data-cmd="/ip/route/print">Rutas</button>
            <button class="wssdo-btn-quick ssh-quick" data-id="{{ $device->device_id }}" data-cmd=":put [/ping 8.8.8.8 count=5]">Ping 8.8.8.8</button>
            <button class="wssdo-btn-quick ssh-quick" data-id="{{ $device->device_id }}" data-cmd="/system/resource/print">Recursos</button>
          @else
            <button class="wssdo-btn-quick ssh-quick" data-id="{{ $device->device_id }}" data-cmd="get system interface">Interfaces</button>
            <button class="wssdo-btn-quick ssh-quick" data-id="{{ $device->device_id }}" data-cmd="get router info routing-table all">Rutas</button>
            <button class="wssdo-btn-quick ssh-quick" data-id="{{ $device->device_id }}" data-cmd="execute ping 8.8.8.8">Ping 8.8.8.8</button>
            <button class="wssdo-btn-quick ssh-quick" data-id="{{ $device->device_id }}" data-cmd="get system status">Estado</button>
          @endif
        </div>
      </div>

      {{-- TERMINAL --}}
      <div id="ssh-term-wrap-{{ $device->device_id }}" class="wssdo-term-wrap" style="display:none;">
        <div class="wssdo-term-head">
          <strong><i class="fa fa-terminal"></i> xterm.js · Consola dispositivo</strong>
          <span>380px · unbuffered · ANSI</span>
        </div>
        <div class="wssdo-term-inner">
          <div id="ssh-term-{{ $device->device_id }}" style="height: 380px;"></div>
        </div>
      </div>

    </div>
  </div>
</div>
</div>

{{-- xterm.js (carga solo una vez por página) --}}
@once
<link  rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.min.css" />
<script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.min.js"></script>
@endonce

<script>
(function() {
    const DEVICE_ID = {{ $device->device_id }};
    const WS_URL    = @json($wsUrl);
    const SECRET    = @json($secret);

    let term, fitAddon, ws;

    async function generateToken(deviceId) {
        const ts  = Math.floor(Date.now() / 1000);
        const msg = deviceId + ':' + ts;
        const key = await crypto.subtle.importKey(
            'raw', new TextEncoder().encode(SECRET),
            { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']
        );
        const sig = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(msg));
        const hex = Array.from(new Uint8Array(sig)).map(b => b.toString(16).padStart(2,'0')).join('');
        return deviceId + ':' + ts + ':' + hex;
    }

    function setStatus(text, cls) {
        const el = document.getElementById('ssh-status-' + DEVICE_ID);
        el.textContent = text;
        el.className   = 'wssdo-label-status ' + (
            cls === 'success' ? 'wssdo-ls-success' :
            cls === 'warning' ? 'wssdo-ls-warning' :
            cls === 'danger'  ? 'wssdo-ls-danger'  : 'wssdo-ls-default'
        );
    }

    document.getElementById('btn-ssh-connect-' + DEVICE_ID).addEventListener('click', async function() {
        const wrap = document.getElementById('ssh-term-wrap-' + DEVICE_ID);
        wrap.style.display = '';

        if (!term) {
            term = new Terminal({ cursorBlink:true, fontSize:13, fontFamily:'Consolas,"Courier New",monospace',
                theme:{ background:'#0f172a', foreground:'#d4d4d4', cursor:'#aeafad' },
                scrollback:1000, convertEol:true });
            fitAddon = new FitAddon.FitAddon();
            term.loadAddon(fitAddon);
            term.open(document.getElementById('ssh-term-' + DEVICE_ID));
            fitAddon.fit();
            term.onData(d => { if(ws && ws.readyState===1) ws.send(JSON.stringify({type:'input',data:d})); });
        }

        setStatus('Conectando...', 'warning');
        const token = await generateToken(DEVICE_ID);
        ws = new WebSocket(WS_URL);
        ws.onopen  = () => ws.send(JSON.stringify({type:'connect', device_id:DEVICE_ID, token:token}));
        ws.onmessage = function(e) {
            const msg = JSON.parse(e.data);
            if (msg.type==='output')      term.write(msg.data);
            else if (msg.type==='connected') {
                setStatus('Conectado','success');
                document.getElementById('btn-ssh-connect-'+DEVICE_ID).style.display='none';
                document.getElementById('btn-ssh-disc-'+DEVICE_ID).style.display='';
                document.getElementById('ssh-quick-'+DEVICE_ID).style.display='';
            }
            else if (msg.type==='error') { term.writeln('\r\n\x1b[31m[ERROR] '+msg.data+'\x1b[0m'); setStatus('Error','danger'); }
            else if (msg.type==='disconnected') { setStatus('Desconectado','default'); resetBtns(); }
        };
        ws.onclose = () => { setStatus('Desconectado','default'); resetBtns(); };
    });

    document.getElementById('btn-ssh-disc-'+DEVICE_ID).addEventListener('click', function() {
        if(ws) { ws.send(JSON.stringify({type:'disconnect'})); ws.close(); }
        resetBtns();
    });

    document.querySelectorAll('.ssh-quick[data-id="{{ $device->device_id }}"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if(ws && ws.readyState===1) ws.send(JSON.stringify({type:'input', data:btn.dataset.cmd+'\r'}));
        });
    });

    function resetBtns() {
        document.getElementById('btn-ssh-connect-'+DEVICE_ID).style.display='';
        document.getElementById('btn-ssh-disc-'+DEVICE_ID).style.display='none';
        document.getElementById('ssh-quick-'+DEVICE_ID).style.display='none';
    }

    // ── Guardar credenciales por dispositivo ─────────────────────────────
    @can('admin')
    const _xsrfCookie = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    const CSRF_TOKEN  = _xsrfCookie ? decodeURIComponent(_xsrfCookie[1]) : '';

    function credsMsg(text, cls) {
        const el = document.getElementById('creds-msg-' + DEVICE_ID);
        if (el) {
            var clsBg = cls === 'success' ? 'background:#ecfdf5;color:#064e3b;border-color:#a7f3d0;' :
                        cls === 'danger'  ? 'background:#fef2f2;color:#7f1d1d;border-color:#fecaca;' :
                                            'background:#eff6ff;color:#1e3a8a;border-color:#bfdbfe;';
            el.innerHTML = '<div style="' + clsBg + 'border:1px solid;border-radius:8px;padding:9px 12px;margin:8px 0 0;font-size:11.5px;font-weight:600;">' + text + '</div>';
            setTimeout(() => { el.innerHTML = ''; }, 4000);
        }
    }

    const btnSave = document.getElementById('btn-save-creds-' + DEVICE_ID);
    if (btnSave) {
        btnSave.addEventListener('click', function() {
            const form = document.getElementById('form-ssh-creds-' + DEVICE_ID);
            const data = {
                username: form.querySelector('[name=username]').value,
                password: form.querySelector('[name=password]').value,
                port:     form.querySelector('[name=port]').value || null,
            };
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            fetch('{{ url("webssh/device/{$device->device_id}/creds") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify(data),
            })
            .then(r => r.json())
            .then(r => {
                credsMsg(r.ok ? '<i class="fa fa-check"></i> Credenciales guardadas y cifradas.' : 'Error al guardar.', r.ok ? 'success' : 'danger');
                if (r.ok) form.querySelector('[name=password]').value = '';
            })
            .catch(() => credsMsg('Error de red.', 'danger'))
            .finally(() => { btnSave.disabled = false; btnSave.innerHTML = '<i class="fa fa-save"></i> Guardar'; });
        });
    }

    const btnDel = document.getElementById('btn-del-creds-' + DEVICE_ID);
    if (btnDel) {
        btnDel.addEventListener('click', function() {
            if (!confirm('¿Eliminar credenciales propias? Se usarán las credenciales globales.')) return;
            fetch('{{ url("webssh/device/{$device->device_id}/creds") }}', {
                method: 'DELETE',
                headers: { 'X-XSRF-TOKEN': CSRF_TOKEN },
            })
            .then(r => r.json())
            .then(r => { if (r.ok) location.reload(); })
            .catch(() => credsMsg('Error de red.', 'danger'));
        });
    }
    @endcan
})();
</script>
