# Modul Identity & Portal Customer (tahap 1)

Akun customer (peserta) dan kerangka portal Vue 3 + Inertia di aplikasi Laravel + Statamic.
Tahap berikutnya: **Commerce** (checkout + payment gateway) dan **Learner** (enrollment, dokumen, sertifikat).

## Struktur

```
modules/
├── Identity/                          namespace Modules\Identity — TIDAK bergantung pada modul lain
│   ├── IdentityServiceProvider.php    binding, rate limiter, migrasi, view email, route portal
│   ├── routes.php                     login, register, lupa/reset sandi, verifikasi email, profil, ganti sandi
│   ├── Contracts/                     ← satu-satunya bagian yang boleh dipakai modul lain
│   │   ├── CustomerDirectory.php      find(id) / current() → CustomerData
│   │   ├── Data/CustomerData.php      id, name, email, phone, company_name, email_verified (tanpa NPWP/sandi)
│   │   └── Events/                    CustomerRegistered, CustomerAuthenticated (berisi customerId, sinkron)
│   ├── Domain/Customer.php            model Eloquent (ULID, MustVerifyEmail, npwp `encrypted`)
│   ├── Application/EloquentCustomerDirectory.php
│   ├── Http/Controllers, Http/Requests (pesan validasi Indonesia per request)
│   ├── Notifications/                 VerifyEmailNotification, ResetPasswordNotification (Bahasa Indonesia)
│   ├── resources/views/mail/          template email HTML + teks milik modul
│   └── Database/Migrations/           customers, customer_password_reset_tokens
├── Assessment/                        (diperluas) attempt + klaim ke akun
│   ├── Contracts/AssessmentResults.php     forCustomer(customerId) → list<AssessmentResultData>
│   ├── Domain/AssessmentAttempt.php        tabel assessment_attempts
│   ├── Application/AttemptStore.php        record() / claim() / forCustomer()
│   ├── Application/PendingClaim.php        token tertunda di session
│   ├── Http/Middleware/CapturePendingClaim.php   `?assessment=<token>` di semua URL portal
│   ├── Http/Controllers/ClaimController.php      GET /portal/assessment/claim?token=
│   ├── Listeners/ClaimPendingAssessment.php      CustomerRegistered|CustomerAuthenticated → klaim
│   └── portal-routes.php
└── Portal/                            komposisi UI; memakai Contracts Identity, Assessment, Catalog
    ├── PortalServiceProvider.php      middleware group `portal`, view `portal::app`, path halaman Inertia
    ├── Http/Middleware/HandleInertiaRequests.php   root view + shared props portal
    ├── Http/Controllers/              Dashboard, AssessmentHistory, Checkout (placeholder), ProgramCard
    ├── resources/views/app.blade.php  root view (hanya memuat resources/js/portal/app.ts)
    └── routes.php                     /, dashboard, assessments, orders, programs, checkout

app/Support/PortalRoutes.php          aturan grup route portal (domain/prefix/nama/middleware) bersama
config/portal.php                     domain, prefix, landing_url
resources/js/portal/                  app.ts, portal.css, components/, pages/ (Vue 3 + TypeScript)
```

Aturan batas modul diuji oleh `tests/Unit/Architecture/ModuleBoundariesTest.php`: setiap modul hanya boleh
mereferensikan `Modules\<Lain>\Contracts\…`, dan Identity tidak boleh mereferensikan modul lain sama sekali.
Catatan: route Identity memakai middleware group `portal` (didefinisikan modul Portal) lewat nama string —
dependensi runtime pada "kerangka portal", tanpa import kode.

## Guard & keamanan

| | Admin Statamic | Customer portal |
|---|---|---|
| Guard | `web` (tidak diubah) | `customer` (session) |
| Provider | `users` (eloquent `App\Models\User`) | `customers` (eloquent `Modules\Identity\Domain\Customer`) |
| Password broker | `users` | `customers` (tabel `customer_password_reset_tokens`, kedaluwarsa 60 menit) |
| Area | `/cp` | `/portal/*` |

- Customer yang login **tidak** bisa membuka `/cp` (dialihkan ke login CP); admin yang login di CP **bukan** customer.
- Halaman setelah login memakai `auth:customer`, `auth.session` (sesi lain berakhir setelah ganti sandi) dan
  `verified:portal.verification.notice`.
- Login: session di-regenerate; remember me; kunci 60 detik setelah 5 gagal per **email+IP**, plus limiter
  route `portal-login` (20/menit/IP). Register 5/menit/IP. Lupa/reset sandi 5/menit/IP dan 3/menit/email.
  Kirim ulang & tautan verifikasi 6/menit/customer.
- Lupa sandi selalu menampilkan pesan yang sama (tidak membocorkan email terdaftar).
- Session portal terpisah dari CP: untuk request portal (prefix `PORTAL_PREFIX` dan/atau host `PORTAL_DOMAIN`)
  middleware global `App\Http\Middleware\PortalSessionCookie` (sebelum `StartSession`) memakai cookie
  `<app>-portal-session` dengan path `/<prefix>`; `XSRF-TOKEN` portal ikut ber-path `/<prefix>`. Logout portal
  (`session()->invalidate()`) tidak mengeluarkan admin CP di host yang sama.
- `url.intended` hanya dipakai bila mengarah ke portal.
- CSRF: cookie `XSRF-TOKEN` Laravel dibaca otomatis oleh klien HTTP Inertia.
- Props Inertia bersama (`auth.customer`) hanya berisi nama, email, status verifikasi. NPWP & alamat penagihan
  hanya dikirim di halaman Profil. NPWP terenkripsi di DB (`APP_KEY`; jangan ganti tanpa re-enkripsi).
- Register mewajibkan centang persetujuan Kebijakan Privasi/UU PDP (`privacy_accepted_at`); `marketing_opt_in_at` opsional.

## Inertia & Vite: isolasi dari CP Statamic

Statamic 6 CP juga memakai Inertia, dengan middleware `Statamic\Http\Middleware\CP\HandleInertiaRequests` dan root
view `statamic::layout` di grup route CP. Portal punya middleware sendiri (`Modules\Portal\…\HandleInertiaRequests`,
root view `portal::app`) yang **hanya** terpasang di middleware group `portal`. Entry Vite portal
`resources/js/portal/app.ts` hanya dimuat oleh `portal::app`; CSS portal (`@import 'tailwindcss' source(none)`)
hanya memindai `resources/js/portal` dan view Portal. Aset CP tetap dari `public/vendor/statamic/cp/build`.

Paket npm: `vue`, `@inertiajs/vue3` ^3 (pasangan `inertiajs/inertia-laravel` v3), `@vitejs/plugin-vue`,
`typescript` ~5.9 + `vue-tsc` (TypeScript 7 belum didukung vue-tsc).

## Alur klaim assessment

```
Landing Astro ──POST /api/v1/assessment/score──▶ attempt tersimpan (token_hash = sha256), respons berisi attempt_token
      │
      └─ tombol "Simpan hasil" → /portal/assessment/claim?token=<attempt_token>
                                   ├─ belum login: token → session, redirect /portal/register (notice "akan tersimpan")
                                   └─ sudah login: klaim langsung → /portal/dashboard
   (alternatif: /portal/register?assessment=<token> atau /portal/login?assessment=<token>)

Register/login sukses → event CustomerRegistered / CustomerAuthenticated (sinkron)
   → listener Assessment mengambil token dari session → UPDATE … WHERE token_hash=? AND customer_id IS NULL
```

Klaim atomik dan hanya sekali; token tak dikenal/sudah diklaim customer lain diabaikan tanpa error. Dashboard
menampilkan hasil terbaru + maks. 3 rekomendasi program dari `CatalogQuery` (dihitung ulang, selalu harga terkini);
**Assessment saya** menampilkan riwayat. Kontrak API: [`api-contract.md`](./api-contract.md) v1.2.

## Route portal (`portal.*`)

| Path | Nama | Akses |
|---|---|---|
| `/portal` | `portal.home` | → dashboard (atau login) |
| `/portal/login`, `/portal/register`, `/portal/forgot-password`, `/portal/reset-password/{token}` | `portal.login`, … | tamu |
| `/portal/email/verify`, `/portal/email/verify/{id}/{hash}` (signed), `POST /portal/email/verification-notification` | `portal.verification.*` | login |
| `/portal/dashboard`, `/portal/assessments`, `/portal/orders`, `/portal/programs`, `/portal/profile` | | login + verified |
| `/portal/checkout?batch=&qty=` | `portal.checkout` | login + verified; placeholder "Checkout segera hadir" + ringkasan batch |
| `/portal/assessment/claim?token=` | `portal.assessment.claim` | semua |

Landing Astro memakai `PUBLIC_APP_URL` + `/checkout`, `/register`: lokal set `PUBLIC_APP_URL=http://localhost:8000/portal`.

## Environment baru

| Variabel | Default | Keterangan |
|---|---|---|
| `LANDING_URL` | `http://localhost:4321` | Landing Astro (tautan katalog, halaman program `/program/{slug}`, assessment). |
| `PORTAL_DOMAIN` | kosong | Jika diisi (mis. `app.sertifika.id`), semua route portal dibatasi ke domain itu (`Route::domain`). |
| `PORTAL_PREFIX` | `portal` | Prefix path portal. Boleh dikosongkan bila memakai `PORTAL_DOMAIN`. Juga path cookie session portal. |
| `ASSESSMENT_ANON_RETENTION_DAYS` | `30` | Attempt assessment anonim yang tidak diklaim dihapus setelah N hari (`model:prune` harian). |
| `MAIL_*` | Mailpit lokal | `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`, `MAIL_FROM_ADDRESS=no-reply@sertifika.test`, `MAIL_FROM_NAME=Sertifika`. |

## Menjalankan lokal

```bash
/opt/podman/bin/podman start mailpit       # UI http://localhost:8025
php artisan migrate                        # customers, customer_password_reset_tokens, assessment_attempts
npm install
npm run dev        # HMR (Vite) untuk portal; atau:
npm run build      # build produksi ke public/build
npm run typecheck  # vue-tsc
php artisan serve --port=8000              # portal: http://localhost:8000/portal
php artisan queue:work --tries=5           # restart setelah kode berubah: php artisan queue:restart
php artisan test
```

Email verifikasi & reset dikirim lewat **queue** (`ShouldQueue`); butuh `queue:work` (lihat [`local-dev.md`](./local-dev.md)).
Saat `npm run dev` berjalan, file `public/hot` membuat `@vite` memakai dev server; CP tidak terpengaruh
karena asetnya dari `public/vendor/statamic/cp`.

## Catatan produksi

- Jalankan portal di subdomain sendiri: `PORTAL_DOMAIN=app.sertifika.id` (opsional `PORTAL_PREFIX=`),
  `APP_URL` sesuai, `SESSION_DOMAIN` **tidak** di-share ke domain landing/CP kecuali memang diperlukan
  (biarkan `null` = host saat ini), `SESSION_SECURE_COOKIE=true`.
- Di service/container publik untuk portal, set `CP_ENABLED=false` agar `/cp` tidak terekspos; CP dijalankan di
  service internal/terpisah dengan DB yang sama.
- `CORS_ALLOWED_ORIGINS` tetap untuk `api/*` saja (portal bukan API lintas origin).
- Jalankan scheduler (`php artisan schedule:run` tiap menit via cron, atau `schedule:work`): `model:prune` harian
  menghapus attempt anonim yang tidak diklaim > `ASSESSMENT_ANON_RETENTION_DAYS` hari (default 30).
- Pakai mailer transaksional (SES/Postmark/dll.); worker `queue:work` wajib jalan untuk email portal.

## Test

```bash
php artisan test --filter='Identity|Portal|Assessment|ModuleBoundaries'
```

Mencakup register, login sukses/gagal/throttle, intended URL, verifikasi email (signed/tampered/throttle),
reset sandi, profil (NPWP terenkripsi, hanya di props profil, ganti email → verifikasi ulang), ganti sandi,
isolasi guard (customer → `/cp` ditolak, login & dashboard CP tetap 200, admin bukan customer), klaim assessment
(anonim → register, `?assessment=` saat login, token dipakai ulang tidak berpindah pemilik), checkout placeholder,
cookie session portal terpisah dari CP (prefix & `PORTAL_DOMAIN`), prune attempt anonim, dan batas modul.
