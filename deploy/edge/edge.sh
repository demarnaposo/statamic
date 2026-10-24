#!/usr/bin/env bash
# Manage the local edge proxy pod (host port 80 → landing.test / cms.test). Run from anywhere.
set -euo pipefail

PODMAN="${PODMAN:-$(command -v podman || echo /opt/podman/bin/podman)}"
EDGE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NETWORK="${EDGE_NETWORK:-network-dev}"

kube() { sed "s#__EDGE_DIR__#$EDGE_DIR#g" "$EDGE_DIR/edge-pod.yaml" | "$PODMAN" kube "$@" -; }

case "${1:-}" in
  up)     kube play --replace --network "$NETWORK" ;;
  down)   kube down ;;
  reload) "$PODMAN" exec edge-proxy nginx -s reload ;;
  logs)   "$PODMAN" logs -f --tail 100 edge-proxy ;;
  *) echo "Usage: $0 {up|down|reload|logs}"; exit 1 ;;
esac
