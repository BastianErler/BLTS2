#!/usr/bin/env bash
set -euo pipefail

: "${BASE_URL:?}" "${EXPECTED_SHA:?}" "${EXPECTED_FRONTEND_SHA:?}"
[[ "$EXPECTED_SHA" =~ ^[0-9a-f]{40}$ ]] || exit 2
[[ "$EXPECTED_FRONTEND_SHA" =~ ^[0-9a-f]{64}$ ]] || exit 2
[[ "$BASE_URL" =~ ^https?://[a-zA-Z0-9.:-]+$ ]] || exit 2
headers=()
if [[ -n "${HOST_HEADER:-}" ]]; then
  [[ "$HOST_HEADER" =~ ^[a-zA-Z0-9.:-]+$ ]] || exit 2
  headers=(-H "Host: $HOST_HEADER")
fi
html=$(mktemp)
trap 'rm -f "$html"' EXIT
curl --fail --silent --show-error --max-time 15 "${headers[@]}" "$BASE_URL/" > "$html"
grep -q '/assets/' "$html" || { echo 'Frontend does not reference Vite assets' >&2; exit 1; }
[[ "$(sha256sum "$html" | cut -d ' ' -f1)" == "$EXPECTED_FRONTEND_SHA" ]] || { echo 'Frontend does not match expected release' >&2; exit 1; }
mapfile -t assets < <(python3 - "$html" <<'PY'
from html.parser import HTMLParser
import re
import sys
class Assets(HTMLParser):
    def handle_starttag(self, tag, attrs):
        for name, value in attrs:
            if ((tag == 'script' and name == 'src') or (tag == 'link' and name == 'href')) and value and re.fullmatch(r'/assets/[A-Za-z0-9_./-]+\.(?:js|css)', value):
                print(value)
parser = Assets()
with open(sys.argv[1], encoding='utf-8') as source:
    parser.feed(source.read())
PY
)
(( ${#assets[@]} > 0 )) || { echo 'No frontend entry assets found' >&2; exit 1; }
for asset in "${assets[@]}"; do
  curl --fail --silent --show-error --max-time 15 "${headers[@]}" "$BASE_URL$asset" -o /dev/null
done
curl --fail --silent --show-error --max-time 15 "${headers[@]}" -H 'Cache-Control: no-cache' "$BASE_URL/api/deployment-health" |
  EXPECTED_SHA="$EXPECTED_SHA" python3 -c 'import json,os,sys; d=json.load(sys.stdin); sys.exit(0 if d.get("status")=="ok" and d.get("release")==os.environ["EXPECTED_SHA"] else 1)'
echo 'Frontend and release/database health verified'
