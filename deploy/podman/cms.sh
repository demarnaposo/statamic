#!/usr/bin/env bash
# Manage the local Statamic pod (deploy/podman/cms-pod.yaml). Run from anywhere.
set -euo pipefail

PODMAN="${PODMAN:-$(command -v podman || echo /opt/podman/bin/podman)}"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
POD_YAML="$PROJECT_DIR/deploy/podman/cms-pod.yaml"

NETWORK="${CMS_NETWORK:-network-dev}"   # shared with mariadb-dev

kube() { sed "s#__PROJECT_DIR__#$PROJECT_DIR#g" "$POD_YAML" | "$PODMAN" kube "$@" -; }

case "${1:-}" in
  build)         "$PODMAN" build -t localhost/cms-php:latest -f "$PROJECT_DIR/deploy/php/Containerfile" "$PROJECT_DIR/deploy/php" ;;
  up)            kube play --replace --network "$NETWORK" ;;
  down)          kube down ;;
  reload)        "$PODMAN" exec cms-web nginx -s reload ;;
  queue-restart) "$PODMAN" exec cms-app php artisan queue:restart ;;
  artisan)       shift; "$PODMAN" exec -it cms-app php artisan "$@" ;;
  shell)         "$PODMAN" exec -it cms-app sh ;;
  logs)          "$PODMAN" pod logs -f --tail 100 cms ;;
  ps)            "$PODMAN" ps --pod --filter pod=cms ;;
  *) echo "Usage: $0 {build|up|down|reload|queue-restart|artisan …|shell|logs|ps}"; exit 1 ;;
esac
