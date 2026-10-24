# Deployment produksi (Docker Compose)

Satu server, dua repo, satu perintah. Semua berkas ada di `deploy/production/`:

| Berkas | Isi |
|---|---|
| `compose.yaml` | Seluruh stack produksi |
| `.env.production.example` | Template konfigurasi & rahasia → salin ke `.env.production` |
| `Dockerfile` | Image platform: target `app` (PHP-FPM) dan `web` (nginx + `public/`) |
| `php/` | `php.ini` produksi, pool FPM, entrypoint |
| `nginx/` | Routing host → FPM `app`/`admin` |
| `caddy/Caddyfile` | Edge: HTTPS otomatis, header keamanan, kompresi, allowlist admin |
| `local-test.env` | Uji stack produksi di Mac (port 8880, `*.localhost`, HTTP) |

Repo landing (`astro`) menyediakan `Containerfile` dengan target `runtime` (nginx) dan `builder`
(bun + `scripts/deploy-hook.mjs`).

## Arsitektur

```
internet ──80/443──▶ edge (Caddy, Let's Encrypt)
                      ├─ LANDING_DOMAIN ─▶ landing (nginx) ── volume landing-dist ◀── landing-builder
                      ├─ APP_DOMAIN     ─▶ platform-web (nginx) ─▶ app   (FPM, CP_ENABLED=false): /portal, /api/v1
                      └─ ADMIN_DOMAIN   ─▶ platform-web (nginx) ─▶ admin (FPM, CP_ENABLED=true):  /cp
     queue (queue:work) · scheduler (schedule:work) · migrate (sekali jalan) · mariadb · redis
```

- **Hanya `edge`** membuka port (80, 443, 443/udp). Service lain berada di network `backend` (internal, tanpa
  internet). PHP (`app`, `admin`, `queue`, `scheduler`) juga di network `egress` untuk SMTP, cek lisensi Statamic,
  dan nanti Odoo (diakses dari worker `queue`).
- Satu image platform untuk semua peran; perbedaan hanya di env/command. `CP_ENABLED=false` di `app`, sehingga
  `/cp` di `APP_DOMAIN` = 404. Route portal dibatasi ke `APP_DOMAIN` (`PORTAL_DOMAIN`).
- Saat start, entrypoint menjalankan `php artisan optimize` (config/route/view/event cache, dari env container
  itu sendiri) dan `storage:link`. **Migrasi tidak dijalankan oleh web**: service `migrate` menjalankan
  `migrate --force` lalu keluar; service PHP lain menunggu `service_completed_successfully`.
- Rootfs container read-only; yang bisa ditulis: volume `storage` (Laravel), `assets` (`public/assets`, upload
  Statamic), tmpfs `/tmp` dan `bootstrap/cache`.
- Cache, session, dan antrean memakai Redis. Log Laravel → stderr (`docker compose logs`), dibatasi 10 MB × 5 per
  container.
- **Rebuild landing**: simpan konten di CP → listener Catalog → job (debounce `ASTRO_DEPLOY_DEBOUNCE_SECONDS`) di
  `queue` → `POST http://landing-builder:4322/rebuild?token=…` → `astro build` membaca API lewat
  `http://platform-web:8080/api/v1` (jaringan internal) → `dist/` di volume `landing-dist` ditukar atomik.
  Build gagal = versi terakhir tetap tersaji. Saat start, builder langsung build dan mengulang dengan backoff
  (5 s → 5 menit) selama CMS belum siap/konten belum ada.

## Prasyarat server

- Linux x86_64/arm64, ≥ 2 vCPU, 4 GB RAM, 20 GB disk.
- Docker Engine 24+ dengan plugin Compose v2.24+ (`docker compose version`).
- DNS: record **A** (dan AAAA bila ada IPv6) untuk `LANDING_DOMAIN`, `APP_DOMAIN`, `ADMIN_DOMAIN` → IP server.
  Harus sudah resolve sebelum start pertama agar Let's Encrypt berhasil.
- Firewall: buka **80/tcp, 443/tcp, 443/udp** (dan SSH). Port lain tertutup.

## Pertama kali

```bash
# 1. Clone kedua repo berdampingan (LANDING_CONTEXT default: ../../../landing dari deploy/production)
sudo mkdir -p /opt/sertifika && sudo chown "$USER" /opt/sertifika && cd /opt/sertifika
git clone <repo-platform> platform
git clone <repo-landing>  landing

# 2. Konfigurasi
cd platform/deploy/production
cp .env.production.example .env.production
chmod 600 .env.production
ln -s .env.production .env          # agar `docker compose` membaca env ini tanpa --env-file
```

Isi `.env.production` (minimal):

| Variabel | Cara mengisi |
|---|---|
| `LANDING_DOMAIN`, `APP_DOMAIN`, `ADMIN_DOMAIN`, `ACME_EMAIL` | domain & email Let's Encrypt |
| `APP_KEY` | `echo "base64:$(openssl rand -base64 32)"` |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `REDIS_PASSWORD`, `DEPLOY_HOOK_TOKEN` | `openssl rand -hex 24` (masing-masing berbeda) |
| `MAIL_*` | kredensial SMTP |
| `STATAMIC_LICENSE_KEY`, `STATAMIC_PRO_ENABLED` | bila memakai Statamic Pro (Solo hanya 1 user CP) |
| `ADMIN_ALLOWED_IPS` | disarankan: IP kantor/VPN, mis. `203.0.113.10/32` |

`APP_URL`, `LANDING_URL`, `CORS_ALLOWED_ORIGINS`, `PORTAL_DOMAIN`, `ASTRO_DEPLOY_HOOK_URL`, `SESSION_DRIVER`, dsb.
**dihitung oleh `compose.yaml`** dari domain — jangan diisi di env.

```bash
# 3. Build & jalankan (build pertama ±5–10 menit)
docker compose up -d --build
docker compose ps                    # semua healthy; migrate = Exited (0)

# 4a. Konten dari lingkungan lain (dump + upload) — lihat "Impor konten" di bawah, ATAU
# 4b. Mulai kosong: struktur katalog (+ --demo untuk data contoh)
docker compose exec admin php artisan catalog:install

# 5. User admin Statamic (interaktif: nama, email, sandi, super user)
docker compose exec admin php please make:user
```

Buka `https://ADMIN_DOMAIN/cp`, `https://APP_DOMAIN/portal/login`, `https://LANDING_DOMAIN`.
Landing menampilkan 404 sampai build pertama sukses (butuh pengaturan situs & program terisi); pantau dengan
`docker compose logs -f landing-builder`.

### Impor konten

```bash
# Dump DB (dari Mac: podman exec mariadb-dev mariadb-dump -uroot -p... --single-transaction --databases statamic > dump.sql)
docker compose exec -T mariadb sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD"' < dump.sql
docker compose run --rm migrate                     # migrasi yang lebih baru dari dump

# Upload Statamic (public/assets) ke volume; COPYFILE_DISABLE mencegah file ._* dari macOS
COPYFILE_DISABLE=1 tar -C /path/ke/public/assets -cf - --exclude .gitkeep . \
  | docker compose exec -T admin tar -C /srv/app/public/assets -xf -

docker compose exec admin php artisan cache:clear
docker compose restart landing-builder              # build ulang landing dengan konten baru
```

Dump berisi `--databases statamic` → nama DB harus sama dengan `DB_DATABASE`. User CP ikut terimpor
(tabel `users`); `make:user` tidak diperlukan bila sudah ada.

## Image dari registry (tanpa build di server)

Build di CI/mesin lain lalu push:

```bash
# repo platform
docker build -f deploy/production/Dockerfile --target app -t registry.example.com/sertifika/platform:1.4.0 .
docker build -f deploy/production/Dockerfile --target web -t registry.example.com/sertifika/platform-web:1.4.0 .
# repo landing
docker build --target runtime -f Containerfile -t registry.example.com/sertifika/landing:1.4.0 .
docker build --target builder -f Containerfile -t registry.example.com/sertifika/landing-builder:1.4.0 .
docker push …   # keempat image
```

Di server: `PLATFORM_IMAGE=registry.example.com/sertifika/platform`, `LANDING_IMAGE=registry.example.com/sertifika/landing`,
`IMAGE_TAG=1.4.0`, lalu:

```bash
docker login registry.example.com
docker compose pull
docker compose up -d --no-build
```

Repo landing tidak perlu di-clone bila image ditarik (cukup repo platform untuk `compose.yaml` + `Caddyfile`).

## Update & rollback

```bash
cd /opt/sertifika/platform && git pull && (cd ../landing && git pull)
cd deploy/production
docker compose exec mariadb sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --databases statamic' \
  > ~/backup/pre-update-$(date +%F-%H%M).sql      # backup dulu
docker compose up -d --build                         # migrate jalan otomatis, lalu container baru
docker image prune -f
```

Jeda layanan ±10–30 detik saat container PHP diganti. Rollback:

- **Registry:** set `IMAGE_TAG` ke tag sebelumnya → `docker compose up -d --no-build`.
- **Build di server:** `git checkout <commit-sebelumnya>` di repo terkait → `docker compose up -d --build`.
- Migrasi tidak di-rollback otomatis. Bila migrasi baru merusak: `docker compose run --rm app php artisan migrate:rollback --step=1 --force`
  (sebelum menurunkan image), atau restore dump `pre-update`.

## Backup & restore

| Data | Lokasi | Cara backup |
|---|---|---|
| Database | volume `db` | `mariadb-dump` (di bawah), harian via cron |
| Upload Statamic | volume `assets` | tar volume |
| Storage Laravel (glide cache, file upload form) | volume `storage` | tar volume (opsional; sebagian cache) |
| Sertifikat TLS | volume `caddy-data` | opsional (bisa diterbitkan ulang) |
| `.env.production` | file | simpan di password manager/secret store — **wajib**, berisi `APP_KEY` |

```bash
cd /opt/sertifika/platform/deploy/production
# DB
docker compose exec -T mariadb sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines --triggers --databases statamic' \
  | gzip > ~/backup/db-$(date +%F).sql.gz
# Volume (nama volume = <COMPOSE_PROJECT_NAME>_<volume>)
docker run --rm -v sertifika_assets:/data:ro -v ~/backup:/backup alpine tar -C /data -czf /backup/assets-$(date +%F).tgz .

# Restore DB
gunzip -c ~/backup/db-2026-10-24.sql.gz | docker compose exec -T mariadb sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD"'
docker compose exec admin php artisan cache:clear
# Restore volume
docker run --rm -v sertifika_assets:/data -v ~/backup:/backup alpine sh -c 'tar -C /data -xzf /backup/assets-2026-10-24.tgz && chown -R 82:82 /data'
```

Contoh cron (`crontab -e`): `30 2 * * * cd /opt/sertifika/platform/deploy/production && docker compose exec -T mariadb sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --databases statamic' | gzip > /var/backups/sertifika/db-$(date +\%F).sql.gz`.
Salin backup ke luar server (object storage) dan uji restore berkala.

## Log & artisan

```bash
docker compose ps
docker compose logs -f --tail 100 app admin        # error Laravel & PHP
docker compose logs -f queue                       # job (deploy hook, email)
docker compose logs -f landing-builder             # build landing
docker compose logs -f edge                        # akses & TLS (JSON)

docker compose exec admin php artisan about
docker compose exec admin php artisan queue:failed
docker compose exec admin php artisan queue:retry all
docker compose exec admin php artisan tinker
docker compose run --rm app php artisan <perintah>   # container sekali pakai
docker compose restart queue                        # setelah mengubah env worker
```

Perubahan `.env.production` berlaku setelah `docker compose up -d` (container dibuat ulang; cache config
dibangun ulang otomatis). Jangan `config:cache` manual di dalam container.

## Checklist keamanan

- [ ] `APP_DEBUG=false`, `APP_ENV=production` (dipaksa compose), `LOG_LEVEL=info`.
- [ ] `ADMIN_ALLOWED_IPS` diisi (IP kantor/VPN). CP juga memakai 2FA (`STATAMIC_TWO_FACTOR_ENABLED=true`).
- [ ] `.env.production` `chmod 600`, tidak masuk git, tersimpan juga di secret store.
- [ ] Semua password/token acak & unik; `DEPLOY_HOOK_TOKEN` ≥ 32 karakter.
- [ ] **`APP_KEY` jangan dirotasi sembarangan**: NPWP terenkripsi, session & tautan bertanda tangan bergantung
      padanya. Bila harus rotasi: kunci lama ke `APP_PREVIOUS_KEYS`, kunci baru ke `APP_KEY`, lalu re-enkripsi data.
- [ ] Firewall host: hanya 22, 80, 443 (tcp+udp). SSH dengan key, tanpa login root/password.
- [ ] Docker mem-bypass `ufw` untuk port yang di-publish — hanya `edge` yang mem-publish port, jangan tambah `ports:`
      di service lain.
- [ ] Update image dasar berkala (`docker compose build --pull`), dan `apt upgrade` host.
- [ ] Backup harian DB + `assets`, disalin ke luar server, restore pernah diuji.
- [ ] `vm.overcommit_memory = 1` di host (`/etc/sysctl.d/99-redis.conf`) agar Redis tidak memberi peringatan.

## Uji lokal di Mac (berdampingan dengan stack dev)

Stack dev Podman memakai port 80; uji produksi memakai project `sertifika-test`, port 8880/8443, domain
`*.localhost`, dan Caddy mode HTTP (`CADDY_SCHEME=http://`). Data di DB test terpisah.

```bash
cd deploy/production
export DOCKER_HOST="unix://$(podman machine inspect --format '{{.ConnectionInfo.PodmanSocket.Path}}')"
docker compose --env-file local-test.env up -d --build --wait
# impor konten (opsional): lihat "Impor konten"
curl -I http://landing.localhost:8880/  http://app.localhost:8880/portal/login  http://admin.localhost:8880/cp
docker compose --env-file local-test.env down -v    # hapus stack + volume test
```

Mode TLS lokal: `CADDY_SCHEME=" "` (spasi) → Caddy menerbitkan sertifikat dari CA internalnya untuk `*.localhost`
(`curl -k https://app.localhost:8443/`).

## Catatan

- **Landing ke CDN nanti**: landing murni statis. Bisa dipindah ke Cloudflare Pages/Netlify/Vercel: set
  `ASTRO_DEPLOY_HOOK_URL` ke build hook platform tersebut, `CMS_API_URL=https://APP_DOMAIN/api/v1`, lalu hapus
  service `landing`/`landing-builder` dan blok `LANDING_DOMAIN` di Caddyfile.
- **DB eksternal (RDS/managed MariaDB)**: kosongkan `COMPOSE_PROFILES`, set `DB_HOST`/`DB_*`; service `mariadb`
  tidak dijalankan.
- **Skala**: `app` bisa dinaikkan (`APP_FPM_MAX_CHILDREN`) atau dipindah ke server lain; volume `storage`/`assets`
  lalu perlu storage bersama (S3 untuk assets).
- Stack dev lokal (Podman): [`local-dev.md`](./local-dev.md).
