{{-- WebSSH — Página principal terminal. Fragmento interno (no @extends). --}}
@php
$wss_v = $plugin_version ?? '1.2.0';
$wss_total = $devices->count() ?? 0;
$wss_mikro = 0; $wss_forti = 0;
foreach ($devices as $d) {
    $os = strtolower($d->os ?? '');
    if (str_contains($os, 'routeros') || str_contains($os, 'mikrotik')) $wss_mikro++;
    elseif (str_contains($os, 'forti')) $wss_forti++;
}
$wss_secretOk = !empty($secret);
$wss_svcProc = proc_open(['sudo', '-n', 'systemctl', 'is-active', 'librenms-webssh.service'], [1=>['pipe','w'], 2=>['pipe','w']], $wss_svcPipes);
$wss_svcStatus = 'desconocido'; $wss_svcOk = false;
if (is_resource($wss_svcProc)) {
    $wss_svcRaw = trim(stream_get_contents($wss_svcPipes[1]) . stream_get_contents($wss_svcPipes[2]));
    fclose($wss_svcPipes[1]); fclose($wss_svcPipes[2]); proc_close($wss_svcProc);
    $wss_svcStatus = $wss_svcRaw ?: 'inactive';
    $wss_svcOk = ($wss_svcStatus === 'active');
}
@endphp
<div id="wss-root" class="container-fluid py-4 wss-flowbite-ui">
<style>
  :root {
    --wss-primary: var(--fb-primary, #2563eb);
    --wss-primary-hover: var(--fb-primary-hover, #1d4ed8);
    --wss-brand-500: var(--fb-brand-500, #3b82f6);
    --wss-brand-600: var(--fb-brand-600, #2563eb);
    --wss-font: var(--fb-font-family, 'Inter', system-ui, sans-serif);
    --wss-border: var(--fb-border, #e5e7eb);
    --wss-surface: var(--fb-surface, #ffffff);
    --wss-radius: 14px;
    --wss-danger: #dc2626;
    --wss-warning: #d97706;
    --wss-success: #059669;
  }
  #wss-root { font-family: var(--wss-font); color: #111827; line-height: 1.55; }
  #wss-root h2 { font-size: 22px; font-weight: 700; margin: 0 0 14px; color: #111827; }
  #wss-root h3 { font-size: 16px; font-weight: 600; margin: 0 0 10px; }
  #wss-root h4 { font-size: 14px; font-weight: 600; margin: 0 0 8px; }
  #wss-root p { color: #4b5563; margin: 0 0 10px; }
  #wss-root label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
  .wss-head-wrap { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; flex-wrap: wrap; }
  .wss-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; padding: 5px 12px; border-radius: 9999px; background: color-mix(in srgb, var(--wss-primary) 12%, #fff); color: var(--wss-primary); font-weight: 600; letter-spacing: .2px; border: 1px solid color-mix(in srgb, var(--wss-primary) 20%, #fff); }
  .wss-pill-green { background:#ecfdf5; color:#059669; border-color:#a7f3d0; }
  .wss-pill-red   { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
  .wss-pill-amber { background:#fffbeb; color:#d97706; border-color:#fde68a; }
  .wss-pill-slate { background:#f1f5f9; color:#475569; border-color:#cbd5e1; }

  .wss-banner { background: linear-gradient(135deg, #1e2432 0%, var(--wss-primary) 100%); color: #fff; border-radius: var(--wss-radius); padding: 28px 26px; margin-bottom: 22px; box-shadow: 0 6px 18px rgba(0,0,0,.08); border: 1px solid color-mix(in srgb, var(--wss-primary) 45%, #1e2432); }
  .wss-banner-row { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
  .wss-icon { background: rgba(255,255,255,.15); border-radius: 14px; padding: 14px; flex-shrink: 0; }
  .wss-icon i { font-size: 26px; }
  .wss-banner-title { font-size: 22px; font-weight: 700; margin: 0 0 3px; }
  .wss-banner-sub { font-size: 13px; opacity: .85; margin: 0; }
  .wss-actions { display: flex; gap: 10px; flex-wrap: wrap; }
  .wss-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border-radius: 10px; font-weight: 600; font-size: 13.5px; text-decoration: none; border: none; cursor: pointer; transition: all .15s; white-space: nowrap; }
  .wss-btn:disabled { opacity: .5; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
  .wss-btn-primary { background: linear-gradient(135deg, var(--wss-primary) 0%, var(--wss-primary-hover) 100%); color: #fff; box-shadow: 0 4px 12px color-mix(in srgb, var(--wss-primary) 28%, transparent); }
  .wss-btn-primary:hover:not(:disabled) { transform: translateY(-1px); color: #fff; }
  .wss-btn-danger { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: #fff; box-shadow: 0 4px 12px rgba(220, 38, 38, .25); }
  .wss-btn-danger:hover:not(:disabled) { transform: translateY(-1px); color: #fff; }
  .wss-btn-success{ background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: #fff; box-shadow: 0 4px 12px rgba(16, 185, 129, .25); }
  .wss-btn-success:hover:not(:disabled) { transform: translateY(-1px); color: #fff; }
  .wss-btn-info   { background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%); color: #fff; box-shadow: 0 4px 12px rgba(14, 165, 233, .25); }
  .wss-btn-info:hover:not(:disabled) { transform: translateY(-1px); color: #fff; }
  .wss-btn-ghost   { background: #fff; color: #374151; border: 1px solid var(--wss-border); }
  .wss-btn-ghost:hover:not(:disabled) { background: #f9fafb; color: #111827; }
  .wss-btn-sm { padding: 7px 14px; font-size: 12.5px; border-radius: 8px; }

  .wss-card { background: var(--wss-surface); border: 1px solid var(--wss-border); border-radius: var(--wss-radius); padding: 22px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
  .wss-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; gap: 10px; flex-wrap: wrap; }
  .wss-card-title { display: flex; align-items: center; gap: 10px; }
  .wss-card-title i { color: var(--wss-primary); font-size: 16px; }

  .wss-alert { border-radius: 12px; padding: 14px 18px; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 12px; border: 1px solid; }
  .wss-alert i { font-size: 20px; margin-top: 1px; flex-shrink: 0; }
  .wss-alert strong { display: block; font-size: 14px; margin-bottom: 3px; }
  .wss-alert span, .wss-alert p { font-size: 12.5px; line-height: 1.55; margin: 0; }
  .wss-alert code { background: rgba(0,0,0,.06); padding: 1px 6px; border-radius: 4px; font-family: ui-monospace, monospace; font-size: 11.5px; }
  .wss-alert-warn  { background:#fffbeb; color:#78350f; border-color:#fde68a; }
  .wss-alert-warn  i { color: var(--wss-warning); }
  .wss-alert-danger{ background:#fef2f2; color:#7f1d1d; border-color:#fecaca; }
  .wss-alert-danger i { color: var(--wss-danger); }
  .wss-alert-info  { background:#eff6ff; color:#1e3a8a; border-color:#bfdbfe; }
  .wss-alert-info  i { color: var(--wss-primary); }
  .wss-alert-success{ background:#ecfdf5; color:#064e3b; border-color:#a7f3d0; }
  .wss-alert-success i { color: var(--wss-success); }

  .wss-chip-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
  .wss-chip { border-radius: 12px; padding: 16px 16px; background: #f9fafb; border: 1px solid var(--wss-border); display: flex; align-items: center; gap: 12px; transition: all .15s; }
  .wss-chip:hover { border-color: color-mix(in srgb, var(--wss-primary) 35%, var(--wss-border)); transform: translateY(-1px); }
  .wss-chip-ico { width: 40px; height: 40px; border-radius: 10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background: color-mix(in srgb, var(--wss-primary) 12%, #fff); }
  .wss-chip-ico i { font-size: 18px; color: var(--wss-primary); }
  .wss-chip-ico-green { background:#d1fae5; } .wss-chip-ico-green i { color: var(--wss-success); }
  .wss-chip-ico-amber { background:#fef3c7; } .wss-chip-ico-amber i { color: var(--wss-warning); }
  .wss-chip-ico-red   { background:#fee2e2; } .wss-chip-ico-red i   { color: var(--wss-danger); }
  .wss-chip-ico-info  { background:#dbeafe; } .wss-chip-ico-info  i { color: var(--wss-primary); }
  .wss-chip-body { flex: 1; min-width: 0; }
  .wss-chip-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; margin-bottom: 4px; }
  .wss-chip-value { font-size: 18px; font-weight: 700; color: #111827; line-height: 1.2; }
  .wss-chip-sub   { font-size: 11px; color: #6b7280; margin-top: 3px; }

  .wss-input { display: block; width: 100%; padding: 9px 12px; font-size: 13.5px; border: 1px solid var(--wss-border); border-radius: 9px; background: #fff; color: #111827; transition: border-color .15s, box-shadow .15s; font-family: inherit; }
  .wss-input:focus { outline: none; border-color: var(--wss-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--wss-primary) 15%, transparent); }
  .wss-input-sm { padding: 6px 10px; font-size: 12.5px; border-radius: 7px; }
  .wss-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z' clip-rule='evenodd'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
  .wss-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 16px; }
  .wss-col { flex: 1 1 260px; min-width: 240px; }
  .wss-col-fixed { flex: 0 0 auto; }

  .wss-label-status { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; padding: 4px 11px; border-radius: 9999px; letter-spacing: .3px; }
  .wss-label-default { background: #f1f5f9; color: #475569; }
  .wss-label-success { background: #d1fae5; color: #065f46; }
  .wss-label-warning { background: #fef3c7; color: #92400e; }
  .wss-label-danger  { background: #fee2e2; color: #991b1b; }

  .wss-btn-group { display: inline-flex; flex-wrap: wrap; gap: 5px; margin-bottom: 5px; }
  .wss-btn-quick { padding: 6px 12px; font-size: 12px; border-radius: 7px; background: #fff; color: #374151; border: 1px solid var(--wss-border); font-weight: 500; cursor: pointer; transition: all .15s; }
  .wss-btn-quick:hover { background: var(--wss-primary); color: #fff; border-color: var(--wss-primary); transform: translateY(-1px); }

  .wss-input-group { display: flex; align-items: stretch; width: 100%; }
  .wss-input-group-pre { display: inline-flex; align-items: center; padding: 0 11px; background: #f3f4f6; border: 1px solid var(--wss-border); border-right: 0; border-radius: 9px 0 0 9px; color: #6b7280; font-size: 13px; }
  .wss-input-group .wss-input { border-top-left-radius: 0; border-bottom-left-radius: 0; }
  .wss-input-group-sm .wss-input-group-pre { padding: 0 9px; font-size: 12px; border-radius: 7px 0 0 7px; }
  .wss-input-group-sm .wss-input { border-radius: 0 7px 7px 0; }

  .wss-term-card { background: #0f172a; border: 1px solid #1e293b; border-radius: var(--wss-radius); overflow: hidden; margin-bottom: 0; }
  .wss-term-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 12px 18px; background: #111c32; border-bottom: 1px solid #1e293b; color: #cbd5e1; }
  .wss-term-head strong { font-size: 13px; color: #e2e8f0; display: flex; align-items: center; gap: 8px; }

  .wss-accordion { border: 1px solid var(--wss-border); border-radius: var(--wss-radius); overflow: hidden; background: var(--wss-surface); }
  .wss-accordion summary { list-style: none; cursor: pointer; padding: 16px 20px; background: #f9fafb; border-bottom: 1px solid var(--wss-border); display: flex; align-items: center; gap: 12px; user-select: none; }
  .wss-accordion summary::-webkit-details-marker { display: none; }
  .wss-accordion[open] summary { border-bottom: 1px solid var(--wss-border); }
  .wss-acc-title { font-weight: 700; font-size: 14px; color: #111827; }
  .wss-acc-sub { font-size: 12px; color: #6b7280; margin-left: auto; }
  .wss-acc-body { padding: 20px 22px 24px; }
  .wss-tabs { display: flex; gap: 0; border-bottom: 1px solid var(--wss-border); margin-bottom: 18px; overflow-x: auto; }
  .wss-tab { padding: 10px 16px; font-size: 13px; font-weight: 600; color: #6b7280; background: transparent; border: none; border-bottom: 2px solid transparent; cursor: pointer; white-space: nowrap; transition: all .15s; }
  .wss-tab:hover { color: var(--wss-primary); }
  .wss-tab.is-active { color: var(--wss-primary); border-bottom-color: var(--wss-primary); }
  .wss-tabpanel { display: none; padding: 6px 2px 2px; max-height: 700px; overflow: auto; border: 1px solid var(--wss-border); border-top: 0; border-radius: 0 0 12px 12px; padding: 20px; background: #fff; }
  .wss-tabpanel.is-open { display: block; }
  .wss-md-wrap { color: #374151; line-height: 1.65; font-size: 13.5px; }
  .wss-md-wrap h2 { font-size: 18px; margin-top: 1.1em; border-left: 3px solid var(--wss-primary); padding-left: 10px; }
  .wss-md-wrap h3 { font-size: 15px; margin-top: 1.1em; color: var(--wss-primary); }
  .wss-md-wrap pre { background: #0f172a; color: #e2e8f0; padding: 14px 16px; border-radius: 10px; font-size: 12px; overflow-x: auto; line-height: 1.55; font-family: 'JetBrains Mono', ui-monospace, monospace; border: 1px solid #1e293b; }
  .wss-md-wrap code { background: #f3f4f6; padding: 1px 6px; border-radius: 4px; font-size: 12px; font-family: 'JetBrains Mono', ui-monospace, monospace; color: #be123c; }
  .wss-md-wrap pre code { background: transparent; color: inherit; padding: 0; font-size: inherit; }
  .wss-md-wrap table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12.5px; }
  .wss-md-wrap th, .wss-md-wrap td { padding: 8px 10px; border: 1px solid var(--wss-border); }
  .wss-md-wrap th { background: #f9fafb; color: #111827; font-weight: 600; text-align: left; }
  .wss-md-wrap blockquote { margin: 10px 0; padding: 10px 14px; border-left: 4px solid var(--wss-primary); background: color-mix(in srgb, var(--wss-primary) 5%, #fff); color: #374151; border-radius: 0 8px 8px 0; }
  .wss-md-wrap hr { border: 0; border-top: 1px dashed var(--wss-border); margin: 18px 0; }
  .wss-md-wrap ul, .wss-md-wrap ol { padding-left: 22px; margin: 8px 0; }
  .wss-md-wrap li { margin: 4px 0; }
  .wss-md-wrap strong { color: #111827; }
  .wss-md-wrap a { color: var(--wss-primary); text-decoration: none; font-weight: 500; }
  .wss-md-wrap a:hover { text-decoration: underline; }
  @media (max-width: 640px) {
    .wss-chip-grid { grid-template-columns: 1fr 1fr; }
    .wss-actions { width: 100%; }
    .wss-btn { flex: 1; justify-content: center; }
  }
</style>

{{-- HEADER: título + pills versión y estado --}}
<div class="wss-head-wrap">
  <h2 style="margin:0;"><i class="fa fa-terminal" style="color: var(--wss-primary); margin-right: 6px;"></i> Web SSH Terminal — Acceso de Monitoreo</h2>
  <span class="wss-pill"><i class="fa fa-tag" style="font-size:10px;"></i> v{{ $wss_v }}</span>
  <span class="wss-pill wss-pill-green">
    <i class="fa fa-check-circle" style="font-size:10px;"></i> PLUGIN ACTIVO
  </span>
  @if($wss_svcOk)
  <span class="wss-pill" style="background:#d1fae5; color:#065f46; border-color:#6ee7b7;">
    <i class="fa fa-bolt" style="font-size:10px;"></i> SERVICIO ACTIVO
  </span>
  @endif
</div>

{{-- BANNER: resumen propósito + acciones rápidas --}}
<div class="wss-banner">
  <div class="wss-banner-row">
    <div class="wss-icon"><i class="fa fa-terminal" aria-hidden="true"></i></div>
    <div style="flex:1; min-width: 240px;">
      <div class="wss-banner-title">Terminal SSH sobre WebSocket WSS</div>
      <div class="wss-banner-sub">SSO LibreNMS + tokens HMAC caducos · Cifrado AES credenciales · xterm.js · Acceso solo lectura para diagnóstico de red.</div>
    </div>
    <div class="wss-actions">
      <a href="#wss-terminal-card" class="wss-btn wss-btn-primary">
        <i class="fa fa-plug"></i> Ir a terminal
      </a>
      <a href="#wss-docs-acc" class="wss-btn" style="background: rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.25);">
        <i class="fa fa-book"></i> Ver manual
      </a>
      <a href="{{ url('plugin/settings/WebSSH') }}" class="wss-btn" style="background: rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.25);">
        <i class="fa fa-key"></i> Ajustes credenciales
      </a>
    </div>
  </div>
</div>

{{-- ALERTA MODO SOLO LECTURA --}}
<div class="wss-alert wss-alert-info">
  <i class="fa fa-shield"></i>
  <div style="flex:1;">
    <strong>Acceso de monitoreo (solo lectura)</strong>
    <span>Esta terminal otorga privilegios de <strong>lectura y diagnóstico</strong> sobre los routers. Permite revisar estado de interfaces, tablas de rutas, logs del sistema y ejecutar pruebas de conectividad (ping, ping extendido) para detectar intermitencia y/o latencia. <strong>No se permiten operaciones de escritura ni de administración.</strong></span>
  </div>
</div>

{{-- TARJETA DE ESTADO: chips métricas --}}
<div class="wss-card">
  <div class="wss-card-header">
    <div class="wss-card-title"><i class="fa fa-heartbeat"></i><h3>Conexión & Dispositivos</h3></div>
    <span class="wss-chip-label">Actualizado al cargar la página</span>
  </div>
  <div class="wss-chip-grid">
    <div class="wss-chip">
      <div class="wss-chip-ico wss-chip-ico-info"><i class="fa fa-server"></i></div>
      <div class="wss-chip-body">
        <div class="wss-chip-label">Dispositivos visibles</div>
        <div class="wss-chip-value">{{ $wss_total }}</div>
        <div class="wss-chip-sub">filtrados RouterOS + FortiGate</div>
      </div>
    </div>
    <div class="wss-chip">
      <div class="wss-chip-ico wss-chip-ico-info"><i class="fa fa-microchip"></i></div>
      <div class="wss-chip-body">
        <div class="wss-chip-label">MikroTik RouterOS</div>
        <div class="wss-chip-value">{{ $wss_mikro }}</div>
        <div class="wss-chip-sub">dispositivos RouterOS</div>
      </div>
    </div>
    <div class="wss-chip">
      <div class="wss-chip-ico wss-chip-ico-info"><i class="fa fa-shield"></i></div>
      <div class="wss-chip-body">
        <div class="wss-chip-label">FortiGate / FortiOS</div>
        <div class="wss-chip-value">{{ $wss_forti }}</div>
        <div class="wss-chip-sub">firewalls NGFW Fortinet</div>
      </div>
    </div>
    <div class="wss-chip">
      <div class="wss-chip-ico {{ $wss_secretOk ? 'wss-chip-ico-green' : 'wss-chip-ico-red' }}">
        <i class="fa fa-{{ $wss_secretOk ? 'key' : 'exclamation-triangle' }}"></i>
      </div>
      <div class="wss-chip-body">
        <div class="wss-chip-label">Archivo .webssh_secret</div>
        <div class="wss-chip-value" style="font-size: 14px;">{{ $wss_secretOk ? '✅ Cargado' : '❌ Faltante' }}</div>
        <div class="wss-chip-sub">{{ $wss_secretOk ? 'HMAC token OK' : 'Sin tokens — revisar INSTALACIÓN' }}</div>
      </div>
    </div>
    <div class="wss-chip">
      <div class="wss-chip-ico {{ $wss_svcOk ? 'wss-chip-ico-green' : 'wss-chip-ico-amber' }}">
        <i class="fa fa-{{ $wss_svcOk ? 'check-circle' : 'exclamation-circle' }}"></i>
      </div>
      <div class="wss-chip-body">
        <div class="wss-chip-label">Servicio WebSSH</div>
        <div class="wss-chip-value" style="font-size: 14px;">{{ $wss_svcOk ? '✅ Corriendo' : '⚠ ' . ucfirst($wss_svcStatus) }}</div>
        <div class="wss-chip-sub">librenms-webssh.service puerto 8765</div>
      </div>
    </div>
  </div>
</div>

{{-- TARJETA PRINCIPAL: CONEXIÓN + TERMINAL --}}
<div class="wss-card" id="wss-terminal-card">
  <div class="wss-card-header">
    <div class="wss-card-title"><i class="fa fa-plug"></i><h3>Terminal SSH</h3></div>
    <span class="wss-chip-label">Seleccione dispositivo y haga clic en Conectar</span>
  </div>

  {{-- FILA 1: selector + botones conectar + status --}}
  <div class="wss-row" style="margin-bottom: 14px;">
    <div class="wss-col">
      <label for="device-select">Dispositivo</label>
      <select id="device-select" class="wss-input wss-select">
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
    <div class="wss-col-fixed" style="padding-top: 26px;">
      <button id="btn-connect" class="wss-btn wss-btn-primary" disabled>
        <i class="fa fa-plug"></i> Conectar
      </button>
      <button id="btn-disconnect" class="wss-btn wss-btn-danger" style="display:none;">
        <i class="fa fa-times"></i> Desconectar
      </button>
    </div>
    <div class="wss-col-fixed" style="padding-top: 26px;">
      <span id="conn-status" class="wss-label-status wss-label-default">Desconectado</span>
      <span id="conn-device" style="margin-left: 8px; font-weight: bold; color: #374151; font-size: 13px;"></span>
    </div>
  </div>

  {{-- FILA 2: comandos rápidos (mostrar solo cuando conectado) --}}
  <div class="row" id="quick-cmds" style="margin-bottom: 14px; display:none;">
    <div class="col-md-12">
      <label style="margin-bottom: 6px;">Comandos rápidos de diagnóstico</label>
      <div class="wss-btn-group" role="group">
        <button class="wss-btn-quick quick-cmd" data-cmd-mikrotik="/ip/address/print" data-cmd-fortigate="get system interface">Interfaces</button>
        <button class="wss-btn-quick quick-cmd" data-cmd-mikrotik="/ip/route/print" data-cmd-fortigate="get router info routing-table all">Rutas</button>
        <button class="wss-btn-quick quick-cmd" data-cmd-mikrotik="/system/resource/print" data-cmd-fortigate="get system status">Estado Sistema</button>
        <button class="wss-btn-quick quick-cmd" data-cmd-mikrotik="/log/print" data-cmd-fortigate="get system event">Logs</button>
        <button class="wss-btn-quick quick-cmd" data-cmd-mikrotik="/interface/ethernet/print stats" data-cmd-fortigate="get hardware nic all">Errores Interfaz</button>
        <button class="wss-btn-quick quick-cmd" data-cmd-mikrotik="/ip/firewall/connection/print count-only" data-cmd-fortigate="get system session info">Sesiones</button>
      </div>

      <div style="margin-top: 10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <div class="wss-input-group wss-input-group-sm" style="width: 280px; flex-shrink: 0;">
          <span class="wss-input-group-pre"><i class="fa fa-globe"></i></span>
          <input type="text" id="ping-target" class="wss-input wss-input-sm"
                 placeholder="IP o hostname destino" value="45.177.21.1" />
        </div>
        <div class="wss-btn-group" role="group" style="margin-bottom: 0;">
          <button class="wss-btn wss-btn-info wss-btn-sm" id="btn-ping">
            <i class="fa fa-dot-circle-o"></i> Ping 5
          </button>
          <button class="wss-btn wss-btn-info wss-btn-sm" id="btn-ping-ext" title="20 paquetes para detectar intermitencia">
            <i class="fa fa-bar-chart"></i> Ping x20
          </button>
        </div>
        <span style="font-size: 11.5px; color: #6b7280;">
          <i class="fa fa-lock text-success"></i> Acceso HTTPS seguro &mdash; solo monitoreo
        </span>
      </div>
    </div>
  </div>

  {{-- TERMINAL XTERM --}}
  <div class="wss-term-card">
    <div class="wss-term-head">
      <strong><i class="fa fa-terminal"></i> xterm.js · Consola interactiva</strong>
      <div style="font-size: 11px; color: #64748b; font-family: ui-monospace, monospace;">
        <i class="fa fa-keyboard-o"></i> salida unbuffered · ANSI ON · scrollback 2000 líneas
      </div>
    </div>
    <div id="terminal-wrapper" style="padding: 6px;">
      <div id="terminal" style="height: 450px;"></div>
    </div>
  </div>
</div>

{{-- ACCORDION + TABS: DOCUMENTACIÓN --}}
<details class="wss-accordion" id="wss-docs-acc" open>
  <summary>
    <i class="fa fa-book" style="color: var(--wss-primary); font-size: 16px;"></i>
    <div class="wss-acc-title">Documentación · Manual del plugin WebSSH</div>
    <div class="wss-acc-sub">4 documentos integrados</div>
  </summary>
  <div class="wss-acc-body">
    <div class="wss-tabs" role="tablist">
      <button class="wss-tab is-active" role="tab" data-wss-tab="guia"         type="button"><i class="fa fa-book"></i> 📘 Guía de usuario</button>
      <button class="wss-tab"           role="tab" data-wss-tab="faq"          type="button"><i class="fa fa-question-circle"></i> ❓ FAQ & Problemas</button>
      <button class="wss-tab"           role="tab" data-wss-tab="instalacion"  type="button"><i class="fa fa-download"></i> 📦 Instalación & Persistencia</button>
      <button class="wss-tab"           role="tab" data-wss-tab="arquitectura" type="button"><i class="fa fa-sitemap"></i> 🏗 Arquitectura técnica</button>
    </div>

    <div class="wss-tabpanel wss-md-wrap is-open" role="tabpanel" data-wss-panel="guia">
      @include('WebSSH::resources.views.markdown-lines', ['lines' => $docs['guia']['lines'] ?? []])
    </div>
    <div class="wss-tabpanel wss-md-wrap" role="tabpanel" data-wss-panel="faq">
      @include('WebSSH::resources.views.markdown-lines', ['lines' => $docs['faq']['lines'] ?? []])
    </div>
    <div class="wss-tabpanel wss-md-wrap" role="tabpanel" data-wss-panel="instalacion">
      @include('WebSSH::resources.views.markdown-lines', ['lines' => $docs['instalacion']['lines'] ?? []])
    </div>
    <div class="wss-tabpanel wss-md-wrap" role="tabpanel" data-wss-panel="arquitectura">
      @include('WebSSH::resources.views.markdown-lines', ['lines' => $docs['arquitectura']['lines'] ?? []])
    </div>
  </div>
</details>
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
            theme: { background: '#0f172a', foreground: '#d4d4d4', cursor: '#aeafad' },
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
        el.className    = 'wss-label-status wss-label-' + (
            cls === 'success' ? 'success' :
            cls === 'warning' ? 'warning' :
            cls === 'danger'  ? 'danger'  : 'default'
        );
    }

    function resetButtons() {
        document.getElementById('btn-connect').style.display    = '';
        document.getElementById('btn-disconnect').style.display = 'none';
        document.getElementById('quick-cmds').style.display      = 'none';
        document.getElementById('conn-device').textContent       = '';
    }

    // Herramientas de conectividad con destino personalizado
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

<script>
(function () {
  'use strict';
  var tabs   = document.querySelectorAll('#wss-root .wss-tab[data-wss-tab]');
  var panels = document.querySelectorAll('#wss-root .wss-tabpanel[data-wss-panel]');
  tabs.forEach(function (t) {
    t.addEventListener('click', function () {
      tabs.forEach(function (x) { x.classList.remove('is-active'); });
      panels.forEach(function (p) { p.classList.remove('is-open'); });
      t.classList.add('is-active');
      var target = document.querySelector('#wss-root .wss-tabpanel[data-wss-panel="' + t.dataset.wssTab + '"]');
      if (target) target.classList.add('is-open');
    });
  });
})();
</script>
