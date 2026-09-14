#!/usr/bin/env python3
"""
WebSSH WebSocket server — LibreNMS plugin
Listens on 127.0.0.1:8765 (proxied by nginx at /ws/ssh).

Protocol (JSON over WebSocket):
  Client → server:
    { "type": "connect",    "device_id": INT, "token": "id:ts:hmac" }
    { "type": "input",      "data": STR }
    { "type": "disconnect" }
  Server → client:
    { "type": "connected" }
    { "type": "output",      "data": STR }
    { "type": "error",       "data": STR }
    { "type": "disconnected" }
"""
import asyncio
import base64
import hashlib
import hmac
import json
import logging
import os
import re
import sys
import time

import paramiko
import pymysql
import websockets
from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
BASE_DIR   = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(
             os.path.abspath(__file__)))))   # /opt/librenms
ENV_FILE   = os.path.join(BASE_DIR, '.env')
SECRET_FILE = os.path.join(BASE_DIR, '.webssh_secret')

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [webssh] %(levelname)s %(message)s',
    handlers=[logging.StreamHandler(sys.stdout)],
)
log = logging.getLogger('webssh')

TOKEN_TTL = 60   # seconds — must match JS generateToken window


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
def load_env(path: str) -> dict:
    env = {}
    try:
        with open(path) as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith('#') or '=' not in line:
                    continue
                k, _, v = line.partition('=')
                v = v.strip().strip('"').strip("'")
                env[k.strip()] = v
    except OSError:
        pass
    return env


def load_secret(path: str) -> bytes:
    try:
        with open(path) as f:
            return f.read().strip().encode()
    except OSError:
        raise RuntimeError(f'Missing WebSSH secret file: {path}')


def parse_app_key(app_key: str) -> bytes:
    if app_key.startswith('base64:'):
        return base64.b64decode(app_key[7:])
    return app_key.encode()


def laravel_decrypt(encrypted: str, key: bytes) -> str:
    """Decrypt a Laravel Crypt::encryptString() value (AES-256-CBC)."""
    payload = json.loads(base64.b64decode(encrypted))
    iv      = base64.b64decode(payload['iv'])
    value   = base64.b64decode(payload['value'])
    cipher  = Cipher(algorithms.AES(key), modes.CBC(iv), backend=default_backend())
    raw     = cipher.decryptor().update(value) + cipher.decryptor().finalize()
    pad     = raw[-1]
    return raw[:-pad].decode('utf-8')


def verify_token(token: str, secret: bytes) -> int | None:
    """Return device_id if token is valid and not expired, else None."""
    try:
        parts = token.split(':')
        if len(parts) != 3:
            return None
        device_id, ts_str, sig = parts
        ts = int(ts_str)
        if abs(time.time() - ts) > TOKEN_TTL:
            return None
        msg = f'{device_id}:{ts_str}'.encode()
        expected = hmac.new(secret, msg, hashlib.sha256).hexdigest()
        if not hmac.compare_digest(expected, sig):
            return None
        return int(device_id)
    except Exception:
        return None


# ---------------------------------------------------------------------------
# DB helpers
# ---------------------------------------------------------------------------
def db_connect(env: dict):
    return pymysql.connect(
        host     = env.get('DB_HOST', '127.0.0.1'),
        port     = int(env.get('DB_PORT', 3306)),
        user     = env.get('DB_USERNAME', 'librenms'),
        password = env.get('DB_PASSWORD', ''),
        database = env.get('DB_DATABASE', 'librenms'),
        charset  = 'utf8mb4',
        connect_timeout = 5,
    )


def get_device(db, device_id: int) -> dict | None:
    with db.cursor(pymysql.cursors.DictCursor) as cur:
        cur.execute(
            "SELECT device_id, hostname, sysName, os, ip "
            "FROM devices WHERE device_id=%s",
            (device_id,)
        )
        return cur.fetchone()


def get_plugin_settings(db, app_key: bytes) -> dict:
    with db.cursor(pymysql.cursors.DictCursor) as cur:
        cur.execute(
            "SELECT settings FROM plugins WHERE plugin_name='WebSSH' LIMIT 1"
        )
        row = cur.fetchone()
    if not row or not row['settings']:
        return {}
    settings = row['settings']
    if isinstance(settings, str):
        settings = json.loads(settings)

    # Decrypt global password (stored as "enc:<base64json>")
    pwd = settings.get('ssh_password', '')
    if isinstance(pwd, str) and pwd.startswith('enc:'):
        try:
            settings['ssh_password'] = laravel_decrypt(pwd[4:], app_key)
        except Exception:
            settings['ssh_password'] = ''
    return settings


def get_device_creds(db, device_id: int, app_key: bytes) -> dict:
    with db.cursor(pymysql.cursors.DictCursor) as cur:
        cur.execute(
            "SELECT attrib_type, attrib_value FROM devices_attribs "
            "WHERE device_id=%s AND attrib_type IN "
            "('webssh_username','webssh_password','webssh_port')",
            (device_id,)
        )
        rows = cur.fetchall()
    attribs = {r['attrib_type']: r['attrib_value'] for r in rows}

    pwd = attribs.get('webssh_password', '')
    if pwd:
        try:
            attribs['webssh_password'] = laravel_decrypt(pwd, app_key)
        except Exception:
            attribs['webssh_password'] = ''
    return attribs


def resolve_credentials(device: dict, plugin: dict, attribs: dict) -> dict:
    """Merge device-specific and global credentials, device takes priority."""
    os_key  = (device.get('os') or '').lower()
    is_mikrotik = 'routeros' in os_key
    global_port = plugin.get('ssh_port_mikrotik' if is_mikrotik else 'ssh_port_fortigate', 22)

    return {
        'username': attribs.get('webssh_username') or plugin.get('ssh_username', ''),
        'password': attribs.get('webssh_password') or plugin.get('ssh_password', ''),
        'port':     int(attribs.get('webssh_port') or global_port or 22),
        'host':     device.get('hostname') or device.get('ip', ''),
    }


# ---------------------------------------------------------------------------
# SSH relay
# ---------------------------------------------------------------------------
async def ssh_relay(ws, host: str, port: int, username: str, password: str):
    loop = asyncio.get_event_loop()
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

    try:
        await loop.run_in_executor(
            None,
            lambda: client.connect(
                hostname    = host,
                port        = port,
                username    = username,
                password    = password,
                timeout     = 15,
                allow_agent = False,
                look_for_keys = False,
            )
        )
    except Exception as e:
        await ws.send(json.dumps({'type': 'error', 'data': f'SSH connect failed: {e}'}))
        return

    channel = client.invoke_shell(term='xterm', width=220, height=50)
    await ws.send(json.dumps({'type': 'connected'}))

    async def read_ssh():
        try:
            while True:
                if channel.recv_ready():
                    data = channel.recv(4096).decode('utf-8', errors='replace')
                    await ws.send(json.dumps({'type': 'output', 'data': data}))
                elif channel.closed or channel.exit_status_ready():
                    break
                else:
                    await asyncio.sleep(0.02)
        except Exception:
            pass
        finally:
            await ws.send(json.dumps({'type': 'disconnected'}))

    reader = asyncio.ensure_future(read_ssh())

    try:
        async for message in ws:
            msg = json.loads(message)
            if msg.get('type') == 'input':
                channel.send(msg.get('data', ''))
            elif msg.get('type') == 'disconnect':
                break
    except websockets.exceptions.ConnectionClosed:
        pass
    finally:
        reader.cancel()
        try:
            channel.close()
        except Exception:
            pass
        client.close()


# ---------------------------------------------------------------------------
# WebSocket handler
# ---------------------------------------------------------------------------
async def handler(ws):
    env     = load_env(ENV_FILE)
    secret  = load_secret(SECRET_FILE)
    app_key = parse_app_key(env.get('APP_KEY', ''))

    try:
        raw = await asyncio.wait_for(ws.recv(), timeout=10)
        msg = json.loads(raw)
    except Exception as e:
        await ws.send(json.dumps({'type': 'error', 'data': f'Bad handshake: {e}'}))
        return

    if msg.get('type') != 'connect':
        await ws.send(json.dumps({'type': 'error', 'data': 'Expected connect message'}))
        return

    token     = msg.get('token', '')
    device_id = verify_token(token, secret)
    if device_id is None:
        await ws.send(json.dumps({'type': 'error', 'data': 'Invalid or expired token'}))
        return

    try:
        db = db_connect(env)
        device  = get_device(db, device_id)
        plugin  = get_plugin_settings(db, app_key)
        attribs = get_device_creds(db, device_id, app_key)
        db.close()
    except Exception as e:
        await ws.send(json.dumps({'type': 'error', 'data': f'DB error: {e}'}))
        return

    if not device:
        await ws.send(json.dumps({'type': 'error', 'data': 'Device not found'}))
        return

    creds = resolve_credentials(device, plugin, attribs)
    if not creds['username'] or not creds['password']:
        await ws.send(json.dumps({'type': 'error', 'data': 'No credentials configured'}))
        return

    log.info('SSH connect: device=%s host=%s port=%d user=%s',
             device_id, creds['host'], creds['port'], creds['username'])

    await ssh_relay(ws, creds['host'], creds['port'], creds['username'], creds['password'])


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
async def main():
    listen_host = '127.0.0.1'
    listen_port = 8765

    env = load_env(ENV_FILE)
    try:
        plugin_settings_port = None
        db = db_connect(env)
        with db.cursor(pymysql.cursors.DictCursor) as cur:
            cur.execute("SELECT settings FROM plugins WHERE plugin_name='WebSSH' LIMIT 1")
            row = cur.fetchone()
        db.close()
        if row and row['settings']:
            s = row['settings']
            if isinstance(s, str):
                s = json.loads(s)
            plugin_settings_port = s.get('ws_port')
        if plugin_settings_port:
            listen_port = int(plugin_settings_port)
    except Exception:
        pass

    log.info('Starting WebSSH server on %s:%d', listen_host, listen_port)
    async with websockets.serve(handler, listen_host, listen_port):
        await asyncio.Future()


if __name__ == '__main__':
    asyncio.run(main())
