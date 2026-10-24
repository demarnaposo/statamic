# Menjalankan stack lokal (Podman)

```bash
# sekali: /etc/hosts
echo "127.0.0.1 landing.test cms.test" | sudo tee -a /etc/hosts

deploy/dev-stack.sh up       # mariadb-dev + mailpit → pod cms → pod landing → proxy edge → deploy hook Astro
deploy/dev-stack.sh status   # status container + cek HTTP
deploy/dev-stack.sh down     # matikan pod & hook (mariadb-dev dan mailpit tetap jalan)
```

| Alamat | Isi | Akses langsung |
|---|---|---|
| http://landing.test | Landing Astro statis (`astro/dist`) | 127.0.0.1:8081 |
| http://cms.test/cp | Statamic Control Panel | 127.0.0.1:8000 |
| http://cms.test/api/v1 | API katalog untuk Astro | 127.0.0.1:8000 |
| http://cms.test/portal | Portal customer | 127.0.0.1:8000 |
| http://localhost:8025 | Mailpit (email lokal) | — |

## Routing
Port 80 dipegang pod **`edge`** (`deploy/edge/`: `edge.conf`, `edge-pod.yaml`, `edge.sh up|down|reload|logs`).
Ia meneruskan request berdasarkan header `Host` ke pod `landing` dan `cms` lewat DNS network `network-dev`,
meniru load balancer/CDN di production. Host lain mendapat 404.

## Rebuild landing saat konten berubah
Simpan konten di CP → listener Catalog → job di container `cms-queue` (debounce 60 detik) →
`POST http://host.containers.internal:4322/rebuild` → receiver Astro (`bun run hook:landing`, dijalankan oleh
`dev-stack.sh up`) → `astro build` dengan `SITE_URL=http://landing.test` → `dist/` diganti → tampil di landing.test.
Log receiver: `/tmp/astro-deploy-hook.log`. Total ±1,5 menit.

## Produksi
Stack produksi (Docker Compose: Caddy, nginx, FPM app/admin, queue, scheduler, MariaDB, Redis, landing builder)
dan cara mengujinya di Mac berdampingan dengan stack ini: [`deployment.md`](./deployment.md).
