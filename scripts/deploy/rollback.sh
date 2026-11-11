#!/usr/bin/env bash
set -euo pipefail
umask 027
: "${DEPLOY_ROOT:?}" "${ROLLBACK_SHA:?}" "${ORIGIN_URL:?}" "${HOST_HEADER:?}"
[[ "$ROLLBACK_SHA" =~ ^[0-9a-f]{40}$ ]] || exit 2
root=$(realpath -e "$DEPLOY_ROOT")
current="$root/current"
target="$root/releases/$ROLLBACK_SHA"
[[ -L "$current" && -d "$target" && ! -L "$target" && "$(cat "$target/RELEASE_SHA")" == "$ROLLBACK_SHA" ]] || exit 1
sudo -n -l /usr/bin/systemctl reload php8.2-fpm.service >/dev/null || { echo 'Runner lacks PHP-FPM reload permission' >&2; exit 1; }
exec 9>"$root/.deploy.lock"
flock -n 9 || exit 1
previous=$(realpath -e "$current")
[[ "$previous" == "$root/releases/"* ]] || exit 1
restore() {
  ln -s "$previous" "$root/.current-restore-$$"
  mv -Tf "$root/.current-restore-$$" "$current"
  sudo -n /usr/bin/systemctl reload php8.2-fpm.service || true
  (cd "$previous" && php artisan queue:restart) || true
}
trap restore ERR
ln -s "$target" "$root/.current-rollback-$$"
mv -Tf "$root/.current-rollback-$$" "$current"
sudo -n /usr/bin/systemctl reload php8.2-fpm.service
(cd "$target" && php artisan queue:restart)
BASE_URL="$ORIGIN_URL" EXPECTED_SHA="$ROLLBACK_SHA" EXPECTED_FRONTEND_SHA="$(cat "$target/FRONTEND_SHA")" HOST_HEADER="$HOST_HEADER" bash "$(dirname "$0")/check.sh"
printf '%s\n' "$previous" > "$root/.previous-release-$$"
mv -f "$root/.previous-release-$$" "$root/.previous-release"
trap - ERR
echo 'Code rollback complete; database was not rolled back.'
