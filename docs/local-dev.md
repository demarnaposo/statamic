# Menjalankan lokal (native di Mac)

Statamic dan Astro berjalan langsung di Mac. Hanya database dan email yang memakai container Podman
(`mariadb-dev` di port 3307, `mailpit` di 1025/8025).

```bash
deploy/dev-stack.sh up       # mariadb-dev + mailpit → php artisan serve → queue:work → schedule:work → astro dev → deploy hook
deploy/dev-stack.sh status   # status proses + cek HTTP
deploy/dev-stack.sh down     # hentikan proses dev (DB & mailpit tetap jalan)
```

Log ada di `/tmp/sertifika-dev/` (`serve.log`, `queue.log`, `schedule.log`, `astro.log`, `hook.log`).

`queue:work` mengirim email portal (verifikasi, reset sandi) dan deploy hook; `schedule:work` menjalankan
jadwal (mis. `model:prune` harian untuk attempt assessment anonim). Setelah mengubah kode: `php artisan queue:restart`
lalu `deploy/dev-stack.sh up` (worker dijalankan ulang bila mati).

| Alamat | Isi |
|---|---|
| http://localhost:4321 | Landing Astro (dev server, konten selalu terbaru) |
| http://localhost:8000/cp | Statamic Control Panel |
| http://localhost:8000/api/v1 | API katalog untuk Astro |
| http://localhost:8000/portal | Portal customer |
| http://localhost:8025 | Mailpit (email lokal) |

Kebutuhan: PHP 8.3+ dan Composer, Bun (`~/.bun/bin/bun`), Podman untuk DB/mailpit.
Aset portal (Vue): `npm run dev` (HMR) atau `npm run build` di folder ini.

## Rebuild landing statis
`astro dev` selalu membaca konten terbaru. Untuk mencoba alur production (build statis + deploy hook):
simpan konten di CP → job `queue:work` (debounce 60 detik) → `POST http://127.0.0.1:4322/rebuild` →
receiver (`bun run hook`) menjalankan `astro build` → `dist/`; lihat hasilnya dengan `bun run preview` (port 4323).
