#!/usr/bin/env bash
# Start/stop the whole local stack in the right order:
#   mariadb-dev + mailpit → cms pod → landing pod → edge proxy (port 80) → Astro deploy-hook receiver.
#   deploy/dev-stack.sh up | down | status
set -euo pipefail

PODMAN="${PODMAN:-$(command -v podman || echo /opt/podman/bin/podman)}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LANDING_DIR="${LANDING_DIR:-$(cd "$APP_DIR/../../../astro" && pwd)}"
BUN="${BUN:-$(command -v bun || echo "$HOME/.bun/bin/bun")}"
HOOK_LOG="${HOOK_LOG:-/tmp/astro-deploy-hook.log}"

hook_pid() { lsof -tiTCP:4322 -sTCP:LISTEN 2>/dev/null || true; }

case "${1:-}" in
  up)
    "$PODMAN" start mariadb-dev mailpit >/dev/null
    "$APP_DIR/deploy/podman/cms.sh" up >/dev/null
    (cd "$LANDING_DIR" && PATH="$(dirname "$PODMAN"):$PATH" "$BUN" run pod:up >/dev/null)
    "$APP_DIR/deploy/edge/edge.sh" up >/dev/null
    if [ -z "$(hook_pid)" ]; then
      (cd "$LANDING_DIR" && nohup "$BUN" run hook:landing >"$HOOK_LOG" 2>&1 &)
    fi
    sleep 3
    "$0" status
    ;;
  down)
    pid="$(hook_pid)"; [ -n "$pid" ] && kill $pid
    "$APP_DIR/deploy/edge/edge.sh" down >/dev/null || true
    (cd "$LANDING_DIR" && PATH="$(dirname "$PODMAN"):$PATH" "$BUN" run pod:down >/dev/null) || true
    "$APP_DIR/deploy/podman/cms.sh" down >/dev/null || true
    echo "Stack dimatikan (mariadb-dev & mailpit tetap jalan)."
    ;;
  status)
    "$PODMAN" ps --format '{{.Names}}\t{{.Status}}' | grep -E 'mariadb-dev|mailpit|cms-|landing-|edge-' || true
    printf 'deploy hook :4322\t%s\n' "$([ -n "$(hook_pid)" ] && echo "jalan (log: $HOOK_LOG)" || echo mati)"
    for u in http://landing.test/ http://cms.test/cp/auth/login http://cms.test/api/v1/site http://cms.test/portal/login; do
      printf '%-34s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' -m 5 "$u")"
    done
    ;;
  *) echo "Usage: $0 {up|down|status}"; exit 1 ;;
esac
