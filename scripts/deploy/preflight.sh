#!/usr/bin/env bash
set -euo pipefail

: "${DEPLOY_ROOT:?}" "${ORIGIN_URL:?}" "${HOST_HEADER:?}" "${PUBLIC_URL:?}"
: "${CLOUDFLARE_ZONE_ID:?}" "${CLOUDFLARE_API_TOKEN:?}"
[[ "$DEPLOY_ROOT" = /* && "$DEPLOY_ROOT" != / ]] || exit 2
[[ "$ORIGIN_URL" =~ ^https?://[a-zA-Z0-9.:-]+$ ]] || exit 2
[[ "$PUBLIC_URL" =~ ^https://[a-zA-Z0-9.:-]+$ ]] || exit 2
[[ "$HOST_HEADER" =~ ^[a-zA-Z0-9.:-]+$ ]] || exit 2
[[ "$CLOUDFLARE_ZONE_ID" =~ ^[0-9a-f]{32}$ ]] || exit 2
[[ "$CLOUDFLARE_API_TOKEN" =~ ^[a-zA-Z0-9_-]+$ ]] || exit 2
root=$(realpath -e "$DEPLOY_ROOT")
[[ -d "$root/releases" && -w "$root/releases" && -w "$root" ]] || { echo 'Release paths unavailable or not writable' >&2; exit 1; }
[[ -L "$root/current" && -d "$root/current" ]] || { echo 'Initial current symlink is not configured' >&2; exit 1; }
[[ -f "$root/shared/.env" && -r "$root/shared/.env" && -d "$root/shared/storage" && -d "$root/shared/assets" && -w "$root/shared/assets" ]] || { echo 'Shared files unavailable' >&2; exit 1; }
for command in php composer flock sudo curl python3; do command -v "$command" >/dev/null; done
sudo -n -l /usr/bin/systemctl reload php8.2-fpm.service >/dev/null || { echo 'Runner lacks the required targeted PHP-FPM reload permission' >&2; exit 1; }
echo 'Deployment prerequisites present (no production data or configuration changed)'
