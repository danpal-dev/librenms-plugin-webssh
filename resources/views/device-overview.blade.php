<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-terminal"></i> {{ $title }}</h3>
    </div>
    <div class="panel-body">

        {{-- ── Credenciales por dispositivo ─────────────────────────────── --}}
        @can('admin')
        <div class="panel panel-info" style="margin-bottom:10px;">
            <div class="panel-heading" style="cursor:pointer;" data-toggle="collapse"
                 data-target="#ssh-creds-{{ $device->device_id }}">
                <h4 class="panel-title">
                    <i class="fa fa-key"></i> Credenciales SSH para este dispositivo
                    <small class="text-muted pull-right">
                        @if($has_device_creds)
                            <span class="label label-success">Credenciales propias</span>
                        @else
                            <span class="label label-default">Usando credenciales globales</span>
                        @endif
                    </small>
                </h4>
            </div>
            <div id="ssh-creds-{{ $device->device_id }}" class="collapse {{ $has_device_creds ? 'in' : '' }}">
                <div class="panel-body">
                    <form id="form-ssh-creds-{{ $device->device_id }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Usuario SSH</label>
                                    <input type="text" name="username" class="form-control"
                                           value="{{ $device_username }}"
                                           placeholder="Usar global ({{ $global_username ?: 'no configurado' }})"
                                           autocomplete="off" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Contraseña SSH</label>
                                    <input type="password" name="password" class="form-control"
                                           value=""
                                           placeholder="{{ $has_device_creds ? '•••••• (guardada)' : 'Usar global' }}"
                                           autocomplete="new-password" />
                                    @if($has_device_creds)
                                        <small class="text-success"><i class="fa fa-lock"></i> Contraseña guardada y cifrada (AES-256)</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Puerto SSH</label>
                                    <input type="number" name="port" class="form-control"
                                           value="{{ $device_port }}"
                                           placeholder="{{ $global_port }}" min="1" max="65535" />
                                </div>
                            </div>
                            <div class="col-md-2" style="padding-top:25px;">
                                <button type="button" id="btn-save-creds-{{ $device->device_id }}"
                                        class="btn btn-primary btn-sm btn-block">
                                    <i class="fa fa-save"></i> Guardar
                                </button>
                                @if($has_device_creds)
                                <button type="button" id="btn-del-creds-{{ $device->device_id }}"
                                        class="btn btn-default btn-sm btn-block" style="margin-top:4px;"
                                        title="Eliminar credenciales propias y usar las globales">
                                    <i class="fa fa-trash"></i> Usar global
                                </button>
                                @endif
                            </div>
                        </div>
                        <div id="creds-msg-{{ $device->device_id }}"></div>
                    </form>
                </div>
            </div>
        </div>
        @endcan

        {{-- ── Terminal SSH ──────────────────────────────────────────────── --}}
        <div class="row" style="margin-bottom: 8px;">
            <div class="col-md-12">
                <button id="btn-ssh-connect-{{ $device->device_id }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plug"></i> Abrir Terminal SSH
                </button>
                <button id="btn-ssh-disc-{{ $device->device_id }}" class="btn btn-danger btn-sm" style="display:none;">
                    <i class="fa fa-times"></i> Desconectar
                </button>
                <span id="ssh-status-{{ $device->device_id }}" class="label label-default" style="margin-left:8px;">Desconectado</span>
            </div>
        </div>

        <div id="ssh-quick-{{ $device->device_id }}" style="display:none; margin-bottom:8px;">
            <div class="btn-group btn-group-sm">
                @if(str_contains(strtolower($device->os ?? ''), 'route') || str_contains(strtolower($device->os ?? ''), 'mikro'))
                    <button class="btn btn-default ssh-quick" data-id="{{ $device->device_id }}" data-cmd="/ip/address/print">Interfaces</button>
                    <button class="btn btn-default ssh-quick" data-id="{{ $device->device_id }}" data-cmd="/ip/route/print">Rutas</button>
                    <button class="btn btn-default ssh-quick" data-id="{{ $device->device_id }}" data-cmd=":put [/ping 8.8.8.8 count=5]">Ping 8.8.8.8</button>
                    <button class="btn btn-default ssh-quick" data-id="{{ $device->device_id }}" data-cmd="/system/resource/print">Recursos</button>
                @else
                    <button class="btn btn-default ssh-quick" data-id="{{ $device->device_id }}" data-cmd="get system interface">Interfaces</button>
                    <button class="btn btn-default ssh-quick" data-id="{{ $device->device_id }}" data-cmd="get router info routing-table all">Rutas</button>
                    <button class="btn btn-default ssh-quick" data-id="{{ $device->device_id }}" data-cmd="execute ping 8.8.8.8">Ping 8.8.8.8</button>
                    <button class="btn btn-default ssh-quick" data-id="{{ $device->device_id }}" data-cmd="get system status">Estado</button>
                @endif
            </div>
        </div>

        <div id="ssh-term-wrap-{{ $device->device_id }}" style="background:#1e1e1e; border-radius:4px; padding:4px; display:none;">
            <div id="ssh-term-{{ $device->device_id }}" style="height: 380px;"></div>
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
        el.className   = 'label label-' + cls;
    }

    document.getElementById('btn-ssh-connect-' + DEVICE_ID).addEventListener('click', async function() {
        const wrap = document.getElementById('ssh-term-wrap-' + DEVICE_ID);
        wrap.style.display = '';

        if (!term) {
            term = new Terminal({ cursorBlink:true, fontSize:13, fontFamily:'Consolas,"Courier New",monospace',
                theme:{ background:'#1e1e1e', foreground:'#d4d4d4' }, scrollback:1000, convertEol:true });
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
            el.innerHTML = '<div class="alert alert-' + cls + ' alert-sm" style="margin:6px 0 0;">' + text + '</div>';
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
