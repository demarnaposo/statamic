# Modul Catalog & Assessment

Backend API katalog sertifikasi untuk frontend Astro. Bentuk JSON & endpoint mengikuti
[`api-contract.md`](./api-contract.md) **v1.4** (sumber kebenaran). Portal customer (akun, attempt & klaim assessment, panel admin) ada di aplikasi terpisah `../portal` (lihat `portal/README.md`).

## Struktur

```
modules/
├── Catalog/                         namespace Modules\Catalog
│   ├── CatalogServiceProvider.php   binding, rate limiter, route api/v1, listener, command
│   ├── config.php                   di-merge sebagai config('catalog')
│   ├── routes.php                   /site, /categories, /programs, /programs/{slug}, /availability, /prices, /batches/{id}, /testimonials, /faqs
│   ├── Contracts/                   ← SATU-SATUNYA bagian yang boleh dipakai modul lain
│   │   ├── CatalogQuery.php         baca katalog (hanya published)
│   │   ├── SeatInventory.php        kursi terisi per batch (default NullSeatInventory = 0)
│   │   ├── ContentCache.php         cache ber-versi untuk respons build-time
│   │   ├── ContentInstaller.php     installer konten per modul (tag catalog.content-installers)
│   │   └── Data/                    DTO readonly: Category, ProgramSummary, ProgramDetail, BatchSummary, BatchDetail, Faq, Testimonial, Site
│   ├── Domain/CatalogRules.php      SATU tempat aturan: promo aktif, effective_price, harga batch, status full, next_batch
│   ├── Application/
│   │   ├── StatamicCatalogQuery.php baca entry/term/global Statamic → DTO (tanpa cache)
│   │   ├── CachedCatalogQuery.php   dekorator cache (binding default CatalogQuery)
│   │   └── AstroDeployHook.php      debounce deploy hook
│   ├── Http/Controllers, Http/Resources
│   ├── Listeners/ContentChangedSubscriber.php
│   ├── Jobs/TriggerAstroDeploy.php
│   ├── Database/Migrations/         catalog_installed_fields (field bawaan yang pernah dipasang)
│   ├── Install/                     blueprint + data demo (CatalogContentInstaller, BlueprintMerger, DemoCatalog)
│   └── Console/InstallCommand.php   php artisan catalog:install
└── Assessment/                      namespace Modules\Assessment (hanya memakai Modules\Catalog\Contracts)
    ├── routes.php                   GET /assessment, POST /internal/assessment/score (portal, Bearer token)
    ├── Application/                 AssessmentRepository (global "assessment"), AssessmentScorer
    ├── Http/                        controller + ScoreAssessmentRequest (validasi 422)
    └── Install/                     blueprint global "assessment" + 7 pertanyaan demo
```

Provider didaftarkan di `bootstrap/providers.php`; autoload `Modules\` → `modules/` di `composer.json`.
Middleware bersama `App\Http\Middleware\NoStore` dipakai endpoint runtime.

## Model konten (Statamic, tersimpan di DB via eloquent driver)

| Handle | Jenis | Isi |
|---|---|---|
| `categories` | taxonomy | title, slug, description, sort_order |
| `programs` | collection (headless) | title, slug, sku (unik), category, issuer, level, duration, summary, description (markdown), benefits/requirements/audience, image, price, promo_price, promo_until, featured |
| `batches` | collection (judul otomatis = kode) | program, code (unik), start_date, end_date, mode, location, capacity, price_override, status |
| `testimonials` | collection | name, role, company, quote, program, sort_order |
| `faqs` | collection | question, answer (markdown), program (kosong = FAQ umum), sort_order |
| `site` | global | identitas, hero, SEO & logo (`logo`, `default_og_image`: asset), stats, steps, kontak |
| `assessment` | global | title, intro, version, questions (grid → options grid: option_id, label, category) |

## Instalasi konten

```bash
php artisan catalog:install          # hanya struktur (taxonomy, collection, blueprint, global)
php artisan catalog:install --demo   # + data demo (5 kategori, 8 program, 19 batch, 5 testimoni, 11 FAQ, site, asesmen)
php artisan catalog:install --demo --force   # timpa blueprint & data demo dengan versi bawaan
```

Idempoten: menjalankan ulang tidak menduplikasi data dan tidak menimpa perubahan admin
(kecuali `--force`). Deploy hook tidak dipicu selama install; cache di-reset sekali di akhir.

**Upgrade blueprint yang aman** (tanpa `--force`): `BlueprintMerger` hanya *menambahkan* field
bawaan yang belum ada **dan belum pernah dipasang** (ke tab/section bawaan yang sama, atau section
baru di akhir tab). Field bawaan yang pernah dipasang dicatat per blueprint di tabel
`catalog_installed_fields`, sehingga field yang sengaja **dihapus** admin di CP tidak muncul lagi;
field yang benar-benar baru di versi kode berikutnya ditambahkan sekali. Saat pencatatan pertama kali
berjalan pada blueprint yang sudah ada, semua field bawaan dianggap sudah dipasang (yang tidak ada =
dihapus admin, tidak ditambahkan). Field, label, urutan, dan field tambahan buatan admin tidak
disentuh; entry/global tidak disentuh sama sekali. Teks petunjuk bawaan lama yang sudah tidak berlaku
(`STALE_DEFAULTS`, mis. petunjuk `promo_until`) diganti hanya jika isinya masih persis teks lama.
Output menampilkan perubahan, mis. `globals.site digabung: +logo, +default_og_image`.

`php artisan catalog:install --dry-run` menjalankan proses yang sama di dalam transaksi DB lalu
rollback: output = perubahan yang akan terjadi, tanpa menyimpan apa pun.

Upgrade ke v1.1 di server yang sudah berisi konten:

```bash
# 1. backup (contoh tanpa mysqldump: ekspor semua tabel ke JSON di storage/app/backups/)
php artisan tinker --execute='$d=storage_path("app/backups/db-".now()->format("Ymd-His"));mkdir($d);$db=DB::getDatabaseName();foreach(Schema::getTables() as $t){if(($t["schema"]??$db)!==$db)continue;file_put_contents("$d/{$t["name"]}.json",json_encode(DB::table($t["name"])->get()));}'
# 2. gabungkan field baru (JANGAN pakai --demo --force di server berisi konten admin)
php artisan catalog:install
```

## Aturan bisnis (Domain/CatalogRules)

- **Promo aktif** jika `promo_price > 0`, `promo_price < price`, dan (`promo_until` kosong **atau**
  `promo_until >= hari ini`, zona `CATALOG_TIMEZONE`). Promo tanpa tanggal berlaku sampai harga promo
  dihapus admin. Promo yang tidak lebih murah diabaikan (blueprint juga memvalidasi `lt:price`).
- `effective_price` = promo_price bila promo aktif, selain itu `price`.
- Harga batch = `price_override` bila diisi, selain itu `effective_price` program.
- Status batch: `closed` bila ditutup admin; `full` bila `seats_taken >= capacity`; selain itu `open`.
- `batches` = batch dengan `start_date >= hari ini`, urut tanggal. `next_batch` = batch `open` pertama.
- Urutan program: featured dulu, lalu judul. Urutan kategori: `sort_order`, lalu nama
  (juga menjadi pemecah seri skor asesmen). FAQ & testimoni: `sort_order`, lalu judul.
- `/batches/{id}`: batch published dari program published (batch yang sudah lewat tetap bisa
  dibaca); selain itu 404. Harga & status dihitung dengan aturan yang sama.
- `/site` 404 jika global `site` belum diisi (nama kosong); `/assessment` 404 jika belum ada pertanyaan.
- URL asset (`image_url`, `seo.*_url`) absolut berbasis `APP_URL` (bukan host request) atau `null`.

## Cache & invalidasi

1. Endpoint build-time (`site`, `categories`, `programs`, `programs/{slug}`, `testimonials`, `faqs`,
   `assessment`) dibaca lewat `ContentCache::remember()` dengan kunci
   `catalog:v{versi}:{tanggal}:{kunci}` (TTL `CATALOG_CACHE_TTL`). Tanggal ada di kunci karena
   promo/batch mendatang berubah tiap hari.
2. `ContentChangedSubscriber` mendengarkan `EntrySaved/Deleted` (collection katalog),
   `TermSaved/Deleted` + `LocalizedTerm*` (categories), `GlobalVariablesSaved/Deleted`,
   `GlobalSetSaved/Deleted` (site, assessment), `AssetSaved/Deleted`.
   Setiap event → versi cache diganti (ULID baru) → semua kunci lama otomatis basi.
3. Event yang sama memanggil `AstroDeployHook::schedule()`:
   - Jika `ASTRO_DEPLOY_HOOK_URL` kosong → tidak melakukan apa pun.
   - Mengambil cache lock `catalog:astro-deploy:debounce` selama `ASTRO_DEPLOY_DEBOUNCE_SECONDS`
     (default 60). Gagal ambil lock = deploy sudah dijadwalkan → abaikan.
   - Queue async (`QUEUE_CONNECTION=database`, setelan saat ini): job `TriggerAstroDeploy`
     di-dispatch dengan **delay 60 detik**, sehingga semua simpan dalam jendela itu ikut satu
     build (banyak simpan dalam 60 dtk → satu job). Simpan setelah jendela lewat menjadwalkan job baru.
   - Queue `sync` (dipakai test): job dijalankan setelah respons dikirim (afterResponse); lock
     tetap mencegah build berulang, tetapi perubahan sesudahnya dalam jendela tidak ikut build itu.
   - Job mengirim `POST` JSON `{source, triggered_at}` ke URL hook (timeout 10 dtk).
     Gagal (receiver mati, HTTP 4xx/5xx) → log `WARNING Astro deploy hook gagal; akan dicoba ulang`
     (host+path saja, token tidak dicatat) lalu di-retry: maks 5 percobaan, backoff 30/60/120/300 dtk.
     Setelah percobaan terakhir: log `ERROR … gagal permanen` + baris di `failed_jobs`
     (`php artisan queue:retry all` untuk mengulang). Worker tidak berhenti.

### Menjalankan worker queue

Tanpa worker, job deploy hanya menumpuk di tabel `jobs` (Astro tidak di-rebuild). Lokal:

```bash
php artisan queue:work --tries=5        # biarkan jalan di terminal terpisah
php artisan queue:failed                # lihat job yang gagal permanen
php artisan queue:retry all             # ulangi
```

Setelah mengubah kode modul, restart worker (`php artisan queue:restart`) karena worker
memuat kode sekali di awal. Di server, jalankan worker lewat supervisor/systemd.
4. Endpoint runtime `programs/{slug}/availability`, `prices`, `batches/{id}`, dan `internal/assessment/score` tidak di-cache server
   dan selalu mengirim `Cache-Control: no-store, private` (termasuk respons 404/422).

Catatan Laravel 13: cache menolak unserialize objek kecuali di-whitelist. Provider modul
menambahkan kelas DTO ke `cache.serializable_classes` (sama seperti yang dilakukan Statamic).

## Menjalankan lokal

Lihat [`local-dev.md`](./local-dev.md) (native di Mac: `deploy/dev-stack.sh up`).

## Environment

| Variabel | Default | Keterangan |
|---|---|---|
| `CORS_ALLOWED_ORIGINS` | `http://localhost:4321` | Origin Astro, pisahkan dengan koma. Dipakai `config/cors.php` (paths `api/*`). |
| `QUEUE_CONNECTION` | `database` | Wajib async agar debounce bekerja; butuh `php artisan queue:work`. |
| `ASTRO_DEPLOY_HOOK_URL` | kosong | URL deploy hook (Netlify/Vercel/Cloudflare Pages). Kosong = nonaktif. Lokal: `http://127.0.0.1:4322/rebuild?token=local-dev-rebuild` (receiver dari repo Astro). |
| `ASTRO_DEPLOY_DEBOUNCE_SECONDS` | `60` | Jendela debounce deploy hook. |
| `CATALOG_CACHE_TTL` | `86400` | TTL cache respons build-time (detik). |
| `CATALOG_API_RATE_LIMIT` | `120` | Request per menit per IP (`throttle:catalog-api` / `assessment-api`). |
| `CATALOG_TIMEZONE` | `Asia/Jakarta` | Zona waktu penentu "hari ini". |

## Test

```bash
php artisan test --filter='Catalog|Assessment'
```

Test memakai sqlite in-memory (phpunit.xml) + `RefreshDatabase`; migrasi eloquent driver
berjalan normal di sqlite. Fixture dibuat lewat `catalog:install` + facade Statamic, dengan
waktu dibekukan ke 2026-10-24 (Asia/Jakarta). Debounce diuji dengan `Queue::fake()` +
`queue.default=database` (banyak simpan → satu job tertunda); upgrade blueprint diuji di
`InstallCommandTest` (field baru masuk sekali, field yang dihapus admin tidak kembali, edit admin &
konten tetap, `--dry-run` tidak menyimpan).

## Integrasi modul lain (Commerce)

Ikat implementasi sendiri di service provider Commerce:

```php
$this->app->bind(\Modules\Catalog\Contracts\SeatInventory::class, OrderSeatInventory::class);
```

Status `full` dan `seats_left` otomatis memakai implementasi tersebut. Program diidentifikasi
ke Odoo lewat `sku` (default_code).
