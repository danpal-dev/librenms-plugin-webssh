<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-cog"></i> Configuración WebSSH</h3>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ url('plugin/settings/WebSSH') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <h4>Credenciales SSH (usuario de monitoreo)</h4>
                    <div class="form-group">
                        <label>Usuario SSH</label>
                        <input type="text" name="settings[ssh_username]" class="form-control"
                               value="{{ $ssh_username }}" placeholder="monitor" autocomplete="off" />
                    </div>
                    <div class="form-group">
                        <label>Contraseña SSH</label>
                        <input type="password" name="settings[ssh_password]" class="form-control"
                               value="" placeholder="{{ $ssh_password ? '••••••••  (guardada)' : 'Ingrese contraseña' }}"
                               autocomplete="new-password" />
                        <small class="text-muted">
                            @if($ssh_password)
                                <i class="fa fa-lock text-success"></i> Contraseña guardada y encriptada (AES-256).
                                Déjelo vacío para no cambiarla.
                            @else
                                Se almacenará encriptada con AES-256 (APP_KEY).
                            @endif
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <h4>Puertos SSH por tipo</h4>
                    <div class="form-group">
                        <label>Puerto MikroTik (RouterOS)</label>
                        <input type="number" name="settings[ssh_port_mikrotik]" class="form-control"
                               value="{{ $ssh_port_mikrotik }}" min="1" max="65535" />
                    </div>
                    <div class="form-group">
                        <label>Puerto FortiGate</label>
                        <input type="number" name="settings[ssh_port_fortigate]" class="form-control"
                               value="{{ $ssh_port_fortigate }}" min="1" max="65535" />
                    </div>
                    <div class="form-group">
                        <label>Puerto WebSocket interno</label>
                        <input type="number" name="settings[ws_port]" class="form-control"
                               value="{{ $ws_port }}" min="1024" max="65535" />
                        <small class="text-muted">Puerto donde escucha el servidor Python WebSSH (default: 8765).</small>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>Acceso de solo monitoreo:</strong> El usuario SSH debe tener únicamente privilegios de lectura
                en los dispositivos (MikroTik: grupo <code>read</code>, FortiGate: perfil <code>prof_admin</code> acceso SSH read-only).
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="min_role">Acceso mínimo requerido</label>
                        <select id="min_role" class="form-control" name="settings[min_role]">
                            <option value="user"        {{ ($min_role ?? 'user') === 'user'        ? 'selected' : '' }}>Cualquier usuario (user)</option>
                            <option value="global-read" {{ ($min_role ?? 'user') === 'global-read' ? 'selected' : '' }}>Lectura global (global-read)</option>
                            <option value="admin"       {{ ($min_role ?? 'user') === 'admin'       ? 'selected' : '' }}>Solo administrador (admin)</option>
                        </select>
                        <small class="text-muted">Controla quién puede ver el menú y la página del terminal SSH.</small>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Guardar Configuración
            </button>
        </form>
    </div>
</div>

{{-- ── Credenciales SSH por dispositivo ─────────────────────────────── --}}
<div class="panel panel-default" style="margin-top:20px;">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-key"></i> Credenciales SSH por dispositivo</h3>
    </div>
    <div class="panel-body">
        <p class="text-muted">Configura usuario, contraseña y puerto SSH para cada dispositivo. Si se deja vacío, se usarán las credenciales globales de arriba.</p>

        @if(empty($devices))
            <div class="alert alert-warning">No hay dispositivos RouterOS o FortiGate registrados.</div>
        @else
        <table class="table table-bordered table-hover table-condensed" id="tbl-device-creds">
            <thead>
                <tr>
                    <th>Dispositivo</th>
                    <th>OS</th>
                    <th style="width:180px">Usuario SSH</th>
                    <th style="width:180px">Contraseña SSH</th>
                    <th style="width:90px">Puerto</th>
                    <th style="width:100px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($devices as $dev)
            <tr id="row-dev-{{ $dev->device_id }}">
                <td><strong>{{ $dev->hostname }}</strong>@if($dev->sysName && $dev->sysName !== $dev->hostname) <br><small class="text-muted">{{ $dev->sysName }}</small>@endif</td>
                <td><span class="label {{ str_contains($dev->os,'route') || str_contains($dev->os,'mikro') ? 'label-info' : 'label-warning' }}">{{ $dev->os }}</span></td>
                <td>
                    <input type="text" class="form-control input-sm dev-username"
                           data-id="{{ $dev->device_id }}"
                           value="{{ $dev->ssh_username ?? '' }}"
                           placeholder="(global)" autocomplete="off" />
                </td>
                <td>
                    <input type="password" class="form-control input-sm dev-password"
                           data-id="{{ $dev->device_id }}"
                           value=""
                           placeholder="{{ $dev->has_password ? '•••••• (guardada)' : '(global)' }}"
                           autocomplete="new-password" />
                </td>
                <td>
                    <input type="number" class="form-control input-sm dev-port"
                           data-id="{{ $dev->device_id }}"
                           value="{{ $dev->ssh_port ?? '' }}"
                           placeholder="(global)" min="1" max="65535" />
                </td>
                <td>
                    <button class="btn btn-primary btn-xs btn-save-dev" data-id="{{ $dev->device_id }}" title="Guardar">
                        <i class="fa fa-save"></i>
                    </button>
                    @if($dev->ssh_username || $dev->has_password || $dev->ssh_port)
                    <button class="btn btn-default btn-xs btn-del-dev" data-id="{{ $dev->device_id }}" title="Eliminar y usar global" style="margin-left:2px;">
                        <i class="fa fa-trash"></i>
                    </button>
                    @endif
                    <span class="dev-msg-{{ $dev->device_id }}" style="display:block;font-size:11px;margin-top:2px;"></span>
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
        el.className = 'dev-msg-' + id + ' text-' + cls;
        setTimeout(function(){ el.textContent = ''; }, 3000);
    }

    function getToken() {
        // Laravel escribe el token en la cookie XSRF-TOKEN
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
                    // Mostrar botón eliminar si no existe
                    if (!row.querySelector('.btn-del-dev')) {
                        var delBtn = document.createElement('button');
                        delBtn.className = 'btn btn-default btn-xs btn-del-dev';
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
