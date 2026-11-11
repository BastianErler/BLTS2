#!/usr/bin/env python3
"""Purge only mutable frontend files; never purge hashed Vite assets or API data."""
import json
import os
from pathlib import Path
import re
import sys
from urllib.parse import quote, urlsplit
from urllib.request import Request, urlopen

zone = os.environ['CLOUDFLARE_ZONE_ID']
token = os.environ['CLOUDFLARE_API_TOKEN']
public = os.environ['PUBLIC_URL'].rstrip('/')
root = Path(os.environ['DEPLOY_ROOT']).resolve(strict=True)
current = (root / 'current').resolve(strict=True)
if not re.fullmatch(r'[0-9a-f]{32}', zone) or not re.fullmatch(r'[A-Za-z0-9_-]+', token):
    sys.exit('Invalid Cloudflare credentials format')
url = urlsplit(public)
if url.scheme != 'https' or not url.hostname or url.username or url.password or url.path or url.query or url.fragment:
    sys.exit('PUBLIC_URL must be a plain HTTPS origin')
if current.parent != root / 'releases':
    sys.exit('current is outside releases')
dist = current / 'resources/vue/dist'
if not dist.is_dir():
    sys.exit('Frontend build missing')
paths = {'/', '/index.html', '/sw.js', '/registerSW.js', '/manifest.webmanifest', '/offline.html'}
builds = [dist]
previous_record = root / '.previous-release'
if previous_record.is_file():
    previous = Path(previous_record.read_text().strip()).resolve(strict=True)
    if previous.parent != root / 'releases':
        sys.exit('Previous release is outside releases')
    builds.append(previous / 'resources/vue/dist')
for build in builds:
    for path in build.rglob('*'):
        if path.is_symlink() or not path.is_file():
            continue
        relative = path.relative_to(build)
        if relative.parts[0] == 'assets' or any(part.startswith('.') for part in relative.parts):
            continue
        paths.add('/' + quote(relative.as_posix(), safe='/'))
files = [public + path for path in sorted(paths)]
for start in range(0, len(files), 25):
    request = Request(
        'https://api.cloudflare.com/client/v4/zones/' + zone + '/purge_cache',
        data=json.dumps({'files': files[start:start + 25]}).encode(),
        headers={'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json'},
        method='POST',
    )
    try:
        with urlopen(request, timeout=20) as response:
            result = json.load(response)
        if not result.get('success'):
            sys.exit('Cloudflare purge failed; retry separately')
    except Exception as exc:
        sys.exit('Cloudflare purge failed: ' + type(exc).__name__ + '; retry separately')
print(f'Cloudflare purge accepted for {len(files)} mutable URLs')
