#!/usr/bin/env bash
# Local development, native on the Mac (no app containers):
#   mariadb-dev + mailpit (Podman containers) → php artisan serve :8000 → queue:work → astro dev :4321 → deploy hook :4322
#   deploy/dev-stack.sh up | down | status
set -euo pipefail

PODMAN="${PODMAN:-$(command -v podman || echo /opt/podman/bin/podman)}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LANDING_DIR="${LANDING_DIR:-$(cd "$APP_DIR/../../../astro" && pwd)}"
BUN="${BUN:-$(command -v bun || echo "$HOME/.bun/bin/bun")}"
LOG_DIR="${LOG_DIR:-/tmp/sertifika-dev}"
mkdir -p "$LOG_DIR"

port_pid() { lsof -tiTCP:"$1" -sTCP:LISTEN 2>/dev/null || true; }
queue_pid() { pgrep -f "artisan queue:work" || true; }

start_bg() { # name dir log cmd...
  local name="$1" dir="$2" log="$3"; shift 3
  (cd "$dir" && nohup "$@" >"$log" 2>&1 &)
  echo "start $name (log: $log)"
}

case "${1:-}" in
  up)
    "$PODMAN" start mariadb-dev mailpit >/dev/null
    printf 'tunggu MariaDB siap'
    for _ in $(seq 1 60); do
      "$PODMAN" exec mariadb-dev sh -c 'mariadb-admin ping --silent 2>/dev/null || mysqladmin ping --silent' >/dev/null 2>&1 && break
      printf '.'; sleep 1
    done; echo
    [ -n "$(port_pid 8000)" ] || start_bg "statamic :8000" "$APP_DIR" "$LOG_DIR/serve.log" php artisan serve --port=8000
    [ -n "$(queue_pid)" ]     || start_bg "queue worker" "$APP_DIR" "$LOG_DIR/queue.log" php artisan queue:work --tries=5
    # Astro 7 `astro dev --background` detaches by itself (stop: `astro dev stop`).
    [ -n "$(port_pid 4321)" ] || { (cd "$LANDING_DIR" && "$BUN" astro dev --background --port 4321 >"$LOG_DIR/astro.log" 2>&1); echo "start astro dev :4321 (log: $LOG_DIR/astro.log)"; }
    [ -n "$(port_pid 4322)" ] || start_bg "deploy hook :4322" "$LANDING_DIR" "$LOG_DIR/hook.log" "$BUN" run hook
    sleep 5
    "$0" status
    ;;
  down)
    (cd "$LANDING_DIR" && "$BUN" astro dev stop >/dev/null 2>&1) || true
    for p in 4322 4321 8000; do pid="$(port_pid $p)"; [ -n "$pid" ] && kill $pid; done
    pid="$(queue_pid)"; [ -n "$pid" ] && kill $pid
    echo "Proses dev dihentikan (mariadb-dev & mailpit tetap jalan; stop dengan: podman stop mariadb-dev mailpit)."
    ;;
  status)
    "$PODMAN" ps --format '{{.Names}}\t{{.Status}}' | grep -E 'mariadb-dev|mailpit' || echo "mariadb-dev/mailpit mati"
    printf 'queue worker\t%s\n' "$([ -n "$(queue_pid)" ] && echo jalan || echo mati)"
    printf 'deploy hook :4322\t%s\n' "$([ -n "$(port_pid 4322)" ] && echo jalan || echo mati)"
    for u in http://localhost:4321/ http://localhost:8000/cp/auth/login http://localhost:8000/api/v1/site http://localhost:8000/portal/login; do
      printf '%-38s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' -m 10 "$u")"
    done
    echo "log: $LOG_DIR"
    ;;
  *) echo "Usage: $0 {up|down|status}"; exit 1 ;;
esac
