<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-terminal"></i> Web SSH Terminal — Acceso de Monitoreo</h3>
                </div>
                <div class="panel-body">

                    {{-- Aviso de nivel de acceso --}}
                    <div class="alert alert-info" style="margin-bottom:14px; padding:10px 14px;">
                        <i class="fa fa-shield text-primary"></i>
                        <strong>Acceso de monitoreo (solo lectura).</strong>
                        Esta terminal otorga privilegios de <strong>lectura y diagnóstico</strong> sobre los routers del
                        Centro de Datos del Cliente, vía SSH sobre HTTPS. Permite revisar estado de interfaces,
                        tablas de rutas, logs del sistema y ejecutar <strong>pruebas de conectividad</strong>
                        (ping, ping extendido) para detectar intermitencia y/o latencia en el servicio.
                        No se permiten operaciones de escritura ni de administración.
                    </div>

                    {{-- Selector de dispositivo --}}
                    <div class="row" style="margin-bottom: 10px;">
                        <div class="col-md-4">
                            <label for="device-select">Dispositivo</label>
                            <select id="device-select" class="form-control">
                                <option value="">-- Seleccione un dispositivo --</option>
                                @foreach($devices as $dev)
                                    <option value="{{ $dev->device_id }}"
                                            data-hostname="{{ $dev->hostname }}"
                                            data-os="{{ $dev->os }}"
                                            data-ip="{{ $dev->ip }}">
                                        {{ $dev->sysName ?: $dev->hostname }}
                                        ({{ strtoupper($dev->os) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2" style="padding-top: 25px;">
                            <button id="btn-connect" class="btn btn-primary" disabled>
                                <i class="fa fa-plug"></i> Conectar
                            </button>
                            <button id="btn-disconnect" class="btn btn-danger" style="display:none;">
                                <i class="fa fa-times"></i> Desconectar
                            </button>
                        </div>
                        <div class="col-md-4" style="padding-top: 25px;">
                            <span id="conn-status" class="label label-default">Desconectado</span>
                            <span id="conn-device" style="margin-left: 8px; font-weight: bold;"></span>
                        </div>
                    </div>

                    {{-- Comandos rápidos --}}
                    <div class="row" id="quick-cmds" style="margin-bottom:8px; display:none;">
                        <div class="col-md-12">
                            {{-- Fila 1: información del sistema --}}
                            <div class="btn-group btn-group-sm" role="group" style="margin-bottom:5px;">
                                <button class="btn btn-default quick-cmd" data-cmd-mikrotik="/ip/address/print" data-cmd-fortigate="get system interface">Interfaces</button>
                                <button class="btn btn-default quick-cmd" data-cmd-mikrotik="/ip/route/print" data-cmd-fortigate="get router info routing-table all">Rutas</button>
                                <button class="btn btn-default quick-cmd" data-cmd-mikrotik="/system/resource/print" data-cmd-fortigate="get system status">Estado Sistema</button>
                                <button class="btn btn-default quick-cmd" data-cmd-mikrotik="/log/print" data-cmd-fortigate="get system event">Logs</button>
                                <button class="btn btn-default quick-cmd" data-cmd-mikrotik="/interface/ethernet/print stats" data-cmd-fortigate="get hardware nic all">Errores Interfaz</button>
                                <button class="btn btn-default quick-cmd" data-cmd-mikrotik="/ip/firewall/connection/print count-only" data-cmd-fortigate="get system session info">Sesiones</button>
                            </div>
                            {{-- Fila 2: pruebas de conectividad (req. 4.5.5) --}}
                            <div style="margin-top:4px; display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <div class="input-group input-group-sm" style="width:260px; flex-shrink:0;">
                                    <span class="input-group-addon"><i class="fa fa-globe"></i></span>
                                    <input type="text" id="ping-target" class="form-control"
                                           placeholder="IP o hostname destino" value="45.177.21.1" />
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-info" id="btn-ping">
                                        <i class="fa fa-dot-circle-o"></i> Ping
                                    </button>
                                    <button class="btn btn-info" id="btn-ping-ext" title="20 paquetes para detectar intermitencia">
                                        <i class="fa fa-bar-chart"></i> Ping x20
                                    </button>
                                </div>
                                <span class="text-muted" style="font-size:11px;">
                                    <i class="fa fa-lock text-success"></i> Acceso HTTPS seguro &mdash; solo monitoreo
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Terminal xterm.js --}}
                    <div id="terminal-wrapper" style="background:#1e1e1e; border-radius:4px; padding:4px;">
                        <div id="terminal" style="height: 450px;"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- xterm.js --}}
<link  rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.min.css" />
<script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.min.js"></script>

<script>
(function() {
    const WS_URL = @json($wsUrl);
    const SECRET = @json($secret);

    let term, fitAddon, ws, currentOs = '', currentIp = '';

    // Inicializar terminal
    function initTerminal() {
        if (term) { term.dispose(); }
        term = new Terminal({
            cursorBlink: true,
            fontSize: 14,
            fontFamily: 'Consolas, "Courier New", monospace',
            theme: { background: '#1e1e1e', foreground: '#d4d4d4', cursor: '#aeafad' },
            scrollback: 2000,
            convertEol: true,
        });
        fitAddon = new FitAddon.FitAddon();
        term.loadAddon(fitAddon);
        term.open(document.getElementById('terminal'));
        fitAddon.fit();
        term.writeln('\x1b[1;32mWeb SSH Terminal\x1b[0m');
        term.writeln('\x1b[90mSeleccione un dispositivo y haga clic en Conectar.\x1b[0m\r\n');

        // Reenviar teclas al servidor
        term.onData(function(data) {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ type: 'input', data: data }));
            }
        });

        // Resize
        window.addEventListener('resize', function() { if(fitAddon) fitAddon.fit(); });
    }

    // Generar token HMAC-SHA256 compatible con Python
    async function generateToken(deviceId) {
        const ts = Math.floor(Date.now() / 1000);
        const msg = deviceId + ':' + ts;
        const key = await crypto.subtle.importKey(
            'raw',
            new TextEncoder().encode(SECRET),
            { name: 'HMAC', hash: 'SHA-256' },
            false, ['sign']
        );
        const sig = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(msg));
        const hex = Array.from(new Uint8Array(sig)).map(b => b.toString(16).padStart(2, '0')).join('');
        return deviceId + ':' + ts + ':' + hex;
    }

    // Conectar WebSocket SSH
    async function connect() {
        const sel = document.getElementById('device-select');
        const deviceId  = sel.value;
        const opt       = sel.options[sel.selectedIndex];
        currentOs = (opt.dataset.os || '').toLowerCase();
        currentIp = opt.dataset.ip || '';
        currentIp = opt.dataset.ip || '';

        if (!deviceId) return;

        setStatus('Conectando...', 'warning');
        document.getElementById('conn-device').textContent = opt.text;

        const token = await generateToken(deviceId);

        ws = new WebSocket(WS_URL);
        ws.onopen = function() {
            ws.send(JSON.stringify({ type: 'connect', device_id: parseInt(deviceId), token: token }));
        };
        ws.onmessage = function(e) {
            const msg = JSON.parse(e.data);
            if (msg.type === 'output') {
                term.write(msg.data);
            } else if (msg.type === 'connected') {
                setStatus('Conectado', 'success');
                document.getElementById('btn-connect').style.display    = 'none';
                document.getElementById('btn-disconnect').style.display = '';
                document.getElementById('quick-cmds').style.display      = '';
            } else if (msg.type === 'error') {
                term.writeln('\r\n\x1b[1;31m[ERROR] ' + msg.data + '\x1b[0m\r\n');
                setStatus('Error', 'danger');
            } else if (msg.type === 'disconnected') {
                setStatus('Desconectado', 'default');
                resetButtons();
            }
        };
        ws.onclose = function() {
            setStatus('Desconectado', 'default');
            resetButtons();
        };
        ws.onerror = function() {
            term.writeln('\r\n\x1b[1;31m[WS ERROR] No se pudo conectar al servidor WebSSH.\x1b[0m\r\n');
            setStatus('Error', 'danger');
        };
    }

    function disconnect() {
        if (ws) { ws.send(JSON.stringify({ type: 'disconnect' })); ws.close(); }
        resetButtons();
        setStatus('Desconectado', 'default');
    }

    function setStatus(text, cls) {
        const el = document.getElementById('conn-status');
        el.textContent  = text;
        el.className    = 'label label-' + cls;
    }

    function resetButtons() {
        document.getElementById('btn-connect').style.display    = '';
        document.getElementById('btn-disconnect').style.display = 'none';
        document.getElementById('quick-cmds').style.display      = 'none';
        document.getElementById('conn-device').textContent       = '';
    }

    // Herramientas de conectividad con destino personalizado (req. 4.5.5)
    function isMikrotik() {
        return currentOs.includes('routeros') || currentOs.includes('mikrotik');
    }

    function sendCmd(cmd) {
        if (!ws || ws.readyState !== WebSocket.OPEN) return;
        ws.send(JSON.stringify({ type: 'input', data: cmd + '\r' }));
    }

    document.getElementById('btn-ping').addEventListener('click', function() {
        var target = (document.getElementById('ping-target').value.trim()) || '45.177.21.1';
        if (isMikrotik()) {
            var cmd = '/tool/ping address=' + target + ' count=5';
            if (currentIp) cmd += ' src-address=' + currentIp;
            sendCmd(cmd);
        } else {
            sendCmd('execute ping ' + target);
        }
    });

    document.getElementById('btn-ping-ext').addEventListener('click', function() {
        var target = (document.getElementById('ping-target').value.trim()) || '45.177.21.1';
        if (isMikrotik()) {
            var cmd = '/tool/ping address=' + target + ' count=20';
            if (currentIp) cmd += ' src-address=' + currentIp;
            sendCmd(cmd);
        } else {
            // FortiGate: configurar ping-options primero, luego ejecutar
            sendCmd('execute ping-options count 20');
            setTimeout(function() { sendCmd('execute ping ' + target); }, 600);
        }
    });

    // Comandos rápidos de información del sistema (ajusta según OS)
    document.querySelectorAll('.quick-cmd').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!ws || ws.readyState !== WebSocket.OPEN) return;
            let cmd = '';
            if (isMikrotik()) {
                cmd = btn.dataset.cmdMikrotik;
            } else {
                cmd = btn.dataset.cmdFortigate;
            }
            if (cmd) {
                ws.send(JSON.stringify({ type: 'input', data: cmd + '\r' }));
            }
        });
    });

    document.getElementById('device-select').addEventListener('change', function() {
        document.getElementById('btn-connect').disabled = !this.value;
    });
    document.getElementById('btn-connect').addEventListener('click', connect);
    document.getElementById('btn-disconnect').addEventListener('click', disconnect);

    initTerminal();
})();
</script>
