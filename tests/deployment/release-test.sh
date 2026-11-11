#!/usr/bin/env bash
set -euo pipefail
base=$(mktemp -d "${RUNNER_TEMP:-/tmp/opencode}/blts-deploy-test.XXXXXXXX")
trap 'rm -r "$base"' EXIT
repo=$(cd "$(dirname "$0")/../.." && pwd)
root="$base/root"
bin="$base/bin"
old=1111111111111111111111111111111111111111
new=2222222222222222222222222222222222222222
bad=3333333333333333333333333333333333333333
mkdir -p "$bin" "$root/releases/$old/resources/vue/dist/assets" "$root/releases/$old/scripts/deploy" "$root/shared/storage" "$root/shared/assets" "$base/bundle/vendor" "$base/bundle/resources/vue/dist/assets" "$base/bundle/scripts/deploy" "$base/bundle/storage"
printf '%s\n' "$old" > "$root/releases/$old/RELEASE_SHA"
printf 'old\n' > "$root/releases/$old/resources/vue/dist/assets/old.js"
printf 'new\n' > "$base/bundle/resources/vue/dist/assets/new.js"
printf '<script src="/assets/new.js"></script>\n' > "$base/bundle/resources/vue/dist/index.html"
sha256sum "$base/bundle/resources/vue/dist/index.html" | cut -d ' ' -f1 > "$base/bundle/FRONTEND_SHA"
cp "$base/bundle/FRONTEND_SHA" "$root/releases/$old/FRONTEND_SHA"
touch "$root/shared/.env" "$base/bundle/artisan" "$base/bundle/vendor/autoload.php"
cp "$repo/scripts/deploy/check.sh" "$base/bundle/scripts/deploy/check.sh"
cp "$repo/scripts/deploy/check.sh" "$root/releases/$old/scripts/deploy/check.sh"
ln -s "$root/releases/$old" "$root/current"
for command in php composer sudo; do
  printf '#!/usr/bin/env bash\nexit 0\n' > "$bin/$command"
  chmod +x "$bin/$command"
done
cat > "$bin/curl" <<'EOF'
#!/usr/bin/env bash
if [[ "${FAKE_FAIL_HEALTH:-0}" == 1 ]]; then exit 22; fi
if [[ "${*: -1}" == */api/deployment-health ]]; then
  printf '{"status":"ok","release":"%s"}\n' "$(cat "$DEPLOY_ROOT/current/RELEASE_SHA")"
elif [[ "${*: -1}" == /dev/null ]]; then
  exit 0
else
  printf '<script src="/assets/new.js"></script>\n'
fi
EOF
chmod +x "$bin/curl"
web_group=$(id -gn)
export PATH="$bin:$PATH" DEPLOY_ROOT="$root" ORIGIN_URL=http://127.0.0.1 HOST_HEADER=example.test WEB_GROUP="$web_group"
printf '%s\n' "$new" > "$base/bundle/RELEASE_SHA"
tar -czf "$base/release.tar.gz" -C "$base/bundle" .
EXPECTED_SHA="$new" ARTIFACT="$base/release.tar.gz" bash "$repo/scripts/deploy/release.sh"
[[ "$(realpath "$root/current")" == "$root/releases/$new" ]]
[[ -f "$root/shared/assets/old.js" && -f "$root/shared/assets/new.js" ]]
ROLLBACK_SHA="$old" bash "$repo/scripts/deploy/rollback.sh"
[[ "$(realpath "$root/current")" == "$root/releases/$old" ]]
printf '%s\n' "$bad" > "$base/bundle/RELEASE_SHA"
tar -czf "$base/release.tar.gz" -C "$base/bundle" .
if FAKE_FAIL_HEALTH=1 EXPECTED_SHA="$bad" ARTIFACT="$base/release.tar.gz" bash "$repo/scripts/deploy/release.sh"; then
  echo 'Expected deployment to fail' >&2; exit 1
fi
[[ "$(realpath "$root/current")" == "$root/releases/$old" ]]
echo 'Release, asset retention, rollback and failure recovery verified'
