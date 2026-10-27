#!/usr/bin/env bash
# Local development, native on the Mac (no app containers):
#   mariadb-dev + mailpit (Podman containers) → CMS :8000 (serve, queue:work, schedule:work)
#   → portal :8001 (serve, queue:work, schedule:work) → astro dev :4321 → deploy hook :4322
#   deploy/dev-stack.sh up | down | status
set -euo pipefail

PODMAN="${PODMAN:-$(command -v podman || echo /opt/podman/bin/podman)}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LANDING_DIR="${LANDING_DIR:-$(cd "$APP_DIR/../../astro" && pwd)}"
PORTAL_DIR="${PORTAL_DIR:-$(cd "$APP_DIR/../portal" && pwd)}"
BUN="${BUN:-$(command -v bun || echo "$HOME/.bun/bin/bun")}"
LOG_DIR="${LOG_DIR:-/tmp/sertifika-dev}"
mkdir -p "$LOG_DIR"

port_pid() { lsof -tiTCP:"$1" -sTCP:LISTEN 2>/dev/null || true; }
# CMS: `php artisan …` (cwd = CMS); portal: `php <PORTAL_DIR>/artisan …` agar pola pgrep tidak saling cocok.
queue_pid() { pgrep -f "php artisan queue:work" || true; }
schedule_pid() { pgrep -f "php artisan schedule:work" || true; }
portal_queue_pid() { pgrep -f "$PORTAL_DIR/artisan queue:work" || true; }
portal_schedule_pid() { pgrep -f "$PORTAL_DIR/artisan schedule:work" || true; }

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
    [ -n "$(schedule_pid)" ]  || start_bg "scheduler" "$APP_DIR" "$LOG_DIR/schedule.log" php artisan schedule:work
    [ -n "$(port_pid 8001)" ] || start_bg "portal :8001" "$PORTAL_DIR" "$LOG_DIR/portal-serve.log" php artisan serve --port=8001
    [ -n "$(portal_queue_pid)" ]    || start_bg "portal queue worker" "$PORTAL_DIR" "$LOG_DIR/portal-queue.log" php "$PORTAL_DIR/artisan" queue:work --tries=5
    [ -n "$(portal_schedule_pid)" ] || start_bg "portal scheduler" "$PORTAL_DIR" "$LOG_DIR/portal-schedule.log" php "$PORTAL_DIR/artisan" schedule:work
    # Astro 7 `astro dev --background` detaches by itself (stop: `astro dev stop`).
    [ -n "$(port_pid 4321)" ] || { (cd "$LANDING_DIR" && "$BUN" astro dev --background --port 4321 >"$LOG_DIR/astro.log" 2>&1); echo "start astro dev :4321 (log: $LOG_DIR/astro.log)"; }
    [ -n "$(port_pid 4322)" ] || start_bg "deploy hook :4322" "$LANDING_DIR" "$LOG_DIR/hook.log" "$BUN" run hook
    sleep 5
    "$0" status
    ;;
  down)
    (cd "$LANDING_DIR" && "$BUN" astro dev stop >/dev/null 2>&1) || true
    for p in 4322 4321 8001 8000; do pid="$(port_pid $p)"; [ -n "$pid" ] && kill $pid; done
    for pid in "$(queue_pid)" "$(schedule_pid)" "$(portal_queue_pid)" "$(portal_schedule_pid)"; do [ -n "$pid" ] && kill $pid; done
    echo "Proses dev dihentikan (mariadb-dev & mailpit tetap jalan; stop dengan: podman stop mariadb-dev mailpit)."
    ;;
  status)
    "$PODMAN" ps --format '{{.Names}}\t{{.Status}}' | grep -E 'mariadb-dev|mailpit' || echo "mariadb-dev/mailpit mati"
    printf 'queue worker\t%s\n' "$([ -n "$(queue_pid)" ] && echo jalan || echo mati)"
    printf 'scheduler\t%s\n' "$([ -n "$(schedule_pid)" ] && echo jalan || echo mati)"
    printf 'portal queue worker\t%s\n' "$([ -n "$(portal_queue_pid)" ] && echo jalan || echo mati)"
    printf 'portal scheduler\t%s\n' "$([ -n "$(portal_schedule_pid)" ] && echo jalan || echo mati)"
    printf 'deploy hook :4322\t%s\n' "$([ -n "$(port_pid 4322)" ] && echo jalan || echo mati)"
    for u in http://localhost:4321/ http://localhost:8000/cp/auth/login http://localhost:8000/api/v1/site http://localhost:8001/login http://localhost:8001/admin/login; do
      printf '%-38s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' -m 10 "$u")"
    done
    echo "log: $LOG_DIR"
    ;;
  *) echo "Usage: $0 {up|down|status}"; exit 1 ;;
esac
