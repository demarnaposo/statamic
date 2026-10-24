# Menjalankan CMS dengan Podman (lokal, gaya production)

URL: **http://cms.test:8000** (CP: `/cp`, API: `/api/v1`). HTTP biasa (dev), env production.

Port 80 dipakai pod `landing` (`landing.test`), jadi CMS memakai host port **8000** (hanya 127.0.0.1).

## Isi pod `cms`

| Container | Image | Tugas |
|---|---|---|
| `cms-web` | `nginxinc/nginx-unprivileged:1.29-alpine` | nginx :8080 di pod → host 127.0.0.1:8000, root `public/` |
| `cms-app` | `localhost/cms-php:latest` (PHP 8.4-FPM) | Laravel/Statamic, FPM di 127.0.0.1:9000 (internal pod) |
| `cms-queue` | `localhost/cms-php:latest` | `php artisan queue:work` (deploy hook Astro, retry + backoff) |

Folder `app/` di Mac di-**mount** ke `/srv/app` (bukan di-copy): perubahan PHP, Blade/Antlers,
config, dan file `public/` langsung berlaku di request berikutnya (OPcache `revalidate_freq=0`).
Build ulang image hanya jika `deploy/php/*` berubah.

File: `deploy/php/` (Containerfile, php.ini, pool FPM), `deploy/nginx/cms.conf`,
`deploy/podman/cms-pod.yaml`, `deploy/podman/cms.sh`.

## Setup pertama

```bash
# 1. domain (sekali)
echo "127.0.0.1 cms.test" | sudo tee -a /etc/hosts

# 2. image PHP + jalankan pod (dari folder app/)
deploy/podman/cms.sh build
deploy/podman/cms.sh up
```

Pod bergabung ke network Podman **`network-dev`** (bersama `mariadb-dev`), jadi database diakses
langsung dengan nama container: `DB_HOST=mariadb-dev`, `DB_PORT=3306` (port internal, bukan 3307).
Ganti network lewat `CMS_NETWORK=… deploy/podman/cms.sh up`.

## Perintah harian

```bash
deploy/podman/cms.sh up              # start / terapkan perubahan cms-pod.yaml
deploy/podman/cms.sh down            # stop & hapus pod (data di DB/folder tetap)
deploy/podman/cms.sh reload          # setelah edit deploy/nginx/cms.conf
deploy/podman/cms.sh queue-restart   # setelah ubah kode yang dipakai job (worker memuat kode sekali)
deploy/podman/cms.sh artisan migrate --force
deploy/podman/cms.sh logs            # log semua container
deploy/podman/cms.sh ps
```

Jika `podman` tidak ada di PATH, script memakai `/opt/podman/bin/podman` (atau set `PODMAN=`).

## Env: `.env` + override pod

Pod membaca `.env` seperti biasa, tetapi env berikut (di `cms-pod.yaml`) **menang** atas `.env`:
`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=http://cms.test:8000`, `LOG_LEVEL=info`,
`DEBUGBAR_ENABLED=false`, `DB_HOST=mariadb-dev`, `DB_PORT=3306`,
`CORS_ALLOWED_ORIGINS=http://landing.test,http://localhost:4321`,
`ASTRO_DEPLOY_HOOK_URL=http://host.containers.internal:4322/...`, `STATAMIC_STACHE_WATCHER=true`.

Sehingga `php artisan …` dan `php artisan test` di Mac tetap memakai setelan lokal `.env`.

Bedanya dengan production sungguhan (disengaja untuk dev): kode di-mount (bukan di-bake ke image),
OPcache memeriksa timestamp file, tidak ada `config:cache`/`route:cache`, tanpa TLS.
Untuk production: copy kode ke image, `composer install --no-dev -o`, `php artisan optimize`,
`opcache.validate_timestamps=0`, TLS di depan nginx.

## Catatan

- Jangan jalankan `php artisan config:cache` di Mac: file cache ikut ter-mount dan pod akan
  memakai config lokal (`APP_ENV=local`). Kalau terlanjur: `php artisan config:clear`.
- Cache aplikasi (`storage/framework/cache`) dipakai bersama oleh pod dan `php artisan serve`.
- Astro (landing) mengambil konten dari CMS: arahkan `CMS_API_URL` ke `http://cms.test:8000/api/v1`
  (dari dalam container landing: `http://host.containers.internal:8000/api/v1`).
