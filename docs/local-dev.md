# Menjalankan lokal (native di Mac)

Tiga aplikasi berjalan langsung di Mac: **CMS** Statamic (repo ini, :8000), **portal** customer + panel admin
(`../portal`, :8001), dan **landing** Astro (`../../astro`, :4321). Hanya database dan email yang memakai
container Podman (`mariadb-dev` di port 3307 — database `statamic` dan `portal`, `mailpit` di 1025/8025).

```bash
deploy/dev-stack.sh up       # mariadb-dev + mailpit → CMS (serve, queue, schedule) → portal (serve, queue, schedule) → astro dev → deploy hook
deploy/dev-stack.sh status   # status proses + cek HTTP
deploy/dev-stack.sh down     # hentikan proses dev (DB & mailpit tetap jalan)
```

Log ada di `/tmp/sertifika-dev/` (`serve.log`, `queue.log`, `schedule.log`, `portal-serve.log`, `portal-queue.log`,
`portal-schedule.log`, `astro.log`, `hook.log`). Lokasi repo lain bisa diganti lewat env `PORTAL_DIR` / `LANDING_DIR`.

- `queue:work` CMS menjalankan deploy hook (rebuild Astro). `queue:work` **portal** mengirim email customer
  (verifikasi, reset sandi) ke Mailpit; `schedule:work` portal menjalankan `model:prune` harian (attempt assessment
  anonim > 30 hari).
- Setelah mengubah kode: `php artisan queue:restart` (di repo yang bersangkutan) lalu `deploy/dev-stack.sh up`
  (worker dijalankan ulang bila mati).

| Alamat | Isi |
|---|---|
| http://localhost:4321 | Landing Astro (dev server, konten selalu terbaru) |
| http://localhost:8000/cp | Statamic Control Panel |
| http://localhost:8000/api/v1 | API katalog untuk Astro & portal |
| http://localhost:8001 | Portal customer (`/login`, `/register`, `/dashboard`, `/checkout`) |
| http://localhost:8001/admin | Panel admin portal (2FA wajib; buat admin: `php artisan admin:create email@domain` di `../portal`) |
| http://localhost:8025 | Mailpit (email lokal) |

Kebutuhan: PHP 8.3+ (portal memakai 8.4) dan Composer, Bun (`~/.bun/bin/bun`), Podman untuk DB/mailpit.

`PORTAL_SERVICE_TOKEN` harus sama di `.env` CMS dan `.env` portal (endpoint internal scoring assessment).
Cookie browser tidak dipisah per port: CMS memakai cookie `statamic-session`, portal `sertifika-portal-session`.

## Rebuild landing statis
`astro dev` selalu membaca konten terbaru. Untuk mencoba alur production (build statis + deploy hook):
simpan konten di CP → job `queue:work` (debounce 60 detik) → `POST http://127.0.0.1:4322/rebuild` →
receiver (`bun run hook`) menjalankan `astro build` → `dist/`; lihat hasilnya dengan `bun run preview` (port 4323).
