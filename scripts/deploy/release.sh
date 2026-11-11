#!/usr/bin/env bash
set -euo pipefail
umask 027

: "${DEPLOY_ROOT:?}" "${ARTIFACT:?}" "${EXPECTED_SHA:?}" "${ORIGIN_URL:?}" "${HOST_HEADER:?}"
[[ "$EXPECTED_SHA" =~ ^[0-9a-f]{40}$ ]] || exit 2
[[ "$DEPLOY_ROOT" = /* && "$DEPLOY_ROOT" != / ]] || exit 2
root=$(realpath -e "$DEPLOY_ROOT")
current="$root/current"
releases="$root/releases"
shared="$root/shared"
[[ -d "$releases" && -d "$shared/storage" && -d "$shared/assets" && -f "$shared/.env" ]] || { echo 'Shared directories or .env missing' >&2; exit 1; }
[[ -L "$current" && -d "$current" ]] || { echo 'current must already be a valid symlink; initial cutover is manual' >&2; exit 1; }
previous=$(realpath -e "$current")
[[ "$previous" == "$releases/"* ]] || { echo 'current is outside releases' >&2; exit 1; }
[[ -f "$ARTIFACT" ]] || exit 1
command -v flock >/dev/null
sudo -n -l /usr/bin/systemctl reload php8.2-fpm.service >/dev/null || { echo 'Runner lacks PHP-FPM reload permission' >&2; exit 1; }
exec 9>"$root/.deploy.lock"
flock -n 9 || { echo 'Another deployment is running' >&2; exit 1; }
target="$releases/$EXPECTED_SHA"
[[ ! -e "$target" && ! -L "$target" ]] || { echo 'Release already exists; use rollback for existing releases' >&2; exit 1; }
mkdir "$target"
tar -xzf "$ARTIFACT" -C "$target" --no-same-owner --no-same-permissions
[[ "$(cat "$target/RELEASE_SHA")" == "$EXPECTED_SHA" && -f "$target/artisan" && -f "$target/vendor/autoload.php" ]] || { echo 'Invalid release artifact' >&2; exit 1; }
[[ ! -e "$target/.env" && ! -L "$target/.env" && ! -L "$target/storage" ]] || exit 1
ln -s "$shared/.env" "$target/.env"
if [[ -d "$target/storage" ]]; then
  # The artifact contains only the empty Laravel directory skeleton.
  find "$target/storage" -type f -not -name .gitignore -print -quit | grep -q . && { echo 'Unexpected data in artifact storage' >&2; exit 1; }
  rm -r "$target/storage"
fi
ln -s "$shared/storage" "$target/storage"
mkdir -p "$target/bootstrap/cache"
# Setgid release directories must use the web application's dedicated group.
# Only Laravel's cache directory, not the release code, is group-writable.
[[ "$(stat -c %G "$target/bootstrap/cache")" == "${WEB_GROUP:-www-data}" ]] || { echo 'Incorrect web group on bootstrap/cache' >&2; exit 1; }
chmod g+rws "$target/bootstrap/cache"

# Keep all old content-addressed Vite assets accessible to existing clients.
assets="$target/resources/vue/dist/assets"
[[ -d "$assets" && ! -L "$assets" ]] || exit 1
copy_assets() {
  local source="$1" file rel dest
  [[ -d "$source" ]] || { echo 'Previous assets directory missing' >&2; return 1; }
  if [[ -L "$source" ]]; then
    [[ "$(realpath -e "$source")" == "$(realpath -e "$shared/assets")" ]] || return 1
    return
  fi
  if find "$source" -type l -print -quit | grep -q .; then echo 'Asset symlinks are not allowed' >&2; return 1; fi
  while IFS= read -r -d '' file; do
    rel=${file#"$source/"}
    dest="$shared/assets/$rel"
    mkdir -p "$(dirname "$dest")"
    if [[ -e "$dest" ]]; then cmp -s "$file" "$dest" || { echo 'Asset collision' >&2; return 1; }
    else cp -p "$file" "$dest"; fi
  done < <(find "$source" -type f -print0)
}
copy_assets "$previous/resources/vue/dist/assets"
copy_assets "$assets"
rm -r "$assets"
ln -s "$shared/assets" "$assets"

cd "$target"
composer check-platform-reqs --no-dev --no-interaction
php artisan package:discover --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache
[[ -e public/storage || -L public/storage ]] || php artisan storage:link
# Only backwards-compatible migrations may be deployed this way.
php artisan migrate --force --no-interaction

switched=0
rollback_on_failure() {
  if [[ "$switched" == 1 ]]; then
    ln -s "$previous" "$root/.current-rollback-$$"
    mv -Tf "$root/.current-rollback-$$" "$current"
    sudo -n /usr/bin/systemctl reload php8.2-fpm.service || true
    (cd "$previous" && php artisan queue:restart) || true
    echo 'Code rolled back; database migrations were not reverted' >&2
  fi
}
trap rollback_on_failure ERR
ln -s "$target" "$root/.current-next-$$"
mv -Tf "$root/.current-next-$$" "$current"
switched=1
sudo -n /usr/bin/systemctl reload php8.2-fpm.service
php artisan queue:restart
BASE_URL="$ORIGIN_URL" EXPECTED_SHA="$EXPECTED_SHA" EXPECTED_FRONTEND_SHA="$(cat "$target/FRONTEND_SHA")" HOST_HEADER="$HOST_HEADER" bash "$target/scripts/deploy/check.sh"
printf '%s\n' "$previous" > "$root/.previous-release-$$"
mv -f "$root/.previous-release-$$" "$root/.previous-release"
trap - ERR
echo "Origin deployment successful: $EXPECTED_SHA"
