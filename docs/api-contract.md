# Kontrak API Catalog v1.4 (Statamic/Laravel → Astro & portal)

Sumber kebenaran bersama untuk backend (`statamic/app`), frontend (`astro`), dan aplikasi portal (`portal`, klien server-to-server).
Perubahan kontrak harus diperbarui di semua repo. Endpoint publik milik portal (`POST /api/assessment/score`,
`GET /api/session`) didokumentasikan di `portal/docs/api-contract.md`.

## Konvensi
- Base URL lokal: `http://localhost:8000/api/v1`
- Semua respons sukses: `{ "data": ... }`. Error 404: `{ "message": "..." }`. Error 422: format validasi Laravel (`{ "message", "errors": { field: [..] } }`).
- Uang: **integer Rupiah, sudah termasuk PPN**. Tanggal: `YYYY-MM-DD`. Datetime: ISO 8601.
- API publik read-only. Satu-satunya POST adalah endpoint internal `POST /internal/assessment/score` (khusus portal). Hanya entry **published** yang dikembalikan.
- Field dikembalikan secara eksplisit (whitelist), bukan dump blueprint Statamic.
- Endpoint build-time boleh di-cache di server (invalidasi saat konten disimpan). Endpoint runtime (`availability`, `prices`, `batches/{id}`, `internal/assessment/score`) tidak di-cache oleh CDN (`Cache-Control: no-store`).
- CORS: origin dari env `CORS_ALLOWED_ORIGINS` (lokal: `http://localhost:4321`), untuk semua `api/*` **kecuali** `api/v1/internal/*` (tanpa CORS).

## Tipe

```ts
type Category = { slug: string; title: string; description: string | null };

type Issuer = "internal" | "bnsp";
type BatchMode = "online" | "offline" | "hybrid";
type BatchStatus = "open" | "closed" | "full";

type BatchSummary = {
  id: string;            // id entry Statamic
  code: string;          // mis. "PMP-2026-11"
  title: string;
  start_date: string;    // YYYY-MM-DD
  end_date: string;
  mode: BatchMode;
  location: string | null;
  capacity: number;
  price: number;         // harga efektif per kursi (price_override ?? effective_price program)
  status: BatchStatus;
};

type ProgramSummary = {
  id: string;
  slug: string;
  title: string;
  sku: string;           // kunci ke produk Odoo (default_code)
  category: Category | null;
  issuer: Issuer;
  level: string | null;      // mis. "Dasar", "Menengah", "Lanjut"
  duration: string | null;   // mis. "3 hari (24 JP)"
  summary: string;
  image_url: string | null;
  price: number;             // harga normal
  promo_price: number | null;
  promo_until: string | null;
  promo_active: boolean;     // v1.1: promo_price terisi DAN (promo_until kosong ATAU promo_until >= hari ini)
  effective_price: number;   // promo_price jika promo_active, selain itu price
  featured: boolean;
  next_batch: BatchSummary | null; // batch open terdekat (start_date >= hari ini)
};

type Faq = { id: string; question: string; answer_html: string; program_slug: string | null };

type ProgramDetail = ProgramSummary & {
  description_html: string;
  benefits: string[];
  requirements: string[];
  audience: string[];
  batches: BatchSummary[];   // batch mendatang, urut start_date
  faqs: Faq[];               // FAQ khusus program ini
};

type Testimonial = { id: string; name: string; role: string | null; company: string | null; quote: string; program_slug: string | null };

type Site = {
  name: string;
  seo: { default_og_image_url: string | null; logo_url: string | null };   // v1.1
  tagline: string;
  hero: { title: string; subtitle: string; cta_label: string };
  stats: { label: string; value: string }[];
  steps: { title: string; description: string }[];   // "Cara kerja"
  contact: { email: string; phone: string; whatsapp: string; address: string };
};

type AssessmentQuestion = { id: string; question: string; options: { id: string; label: string }[] };
type Assessment = { version: number; title: string; intro: string; questions: AssessmentQuestion[] }; // tanpa bobot/kategori

type Availability = {
  program_slug: string;
  price: number;               // v1.1: harga normal (untuk harga coret)
  promo_price: number | null;  // v1.1
  promo_until: string | null;  // v1.1
  effective_price: number;
  promo_active: boolean;
  batches: { id: string; code: string; capacity: number; seats_taken: number; seats_left: number; status: BatchStatus; price: number }[];
  generated_at: string;
};

// v1.1: GET /batches/{id} (runtime, untuk checkout-preview / portal)
type BatchDetail = BatchSummary & {
  capacity: number;
  seats_taken: number;
  seats_left: number;
  program: ProgramSummary;
};

// v1.3: GET /prices (runtime, satu request per halaman untuk semua kartu harga)
type Price = {
  price: number;
  promo_price: number | null;
  promo_until: string | null;
  promo_active: boolean;
  effective_price: number;
};
type Prices = Record<string, Price>;   // key = slug program published

// v1.4: dikembalikan CMS hanya ke portal (endpoint internal), tanpa `attempt_token`.
// Portal meneruskannya ke browser dengan tambahan `attempt_token` (lihat portal/docs/api-contract.md).
type AssessmentResult = {
  version: number;
  scores: { category: Category; score: number }[];   // urut skor tertinggi
  recommended_category: Category;
  recommendations: ProgramSummary[];                  // maks 3
};
```

## Endpoint

| Method | Path | Respons `data` | Dipakai Astro |
|---|---|---|---|
| GET | `/site` | `Site` | build |
| GET | `/categories` | `Category[]` | build |
| GET | `/programs?category=&issuer=&featured=1` | `ProgramSummary[]` | build |
| GET | `/programs/{slug}` | `ProgramDetail` (404 jika tidak ada) | build |
| GET | `/programs/{slug}/availability` | `Availability` | **runtime (browser)** |
| GET | `/prices?slugs=a,b` | `Prices` (v1.3; `slugs` opsional, slug tak dikenal diabaikan; kosong = `{}`) | **runtime (browser)** |
| GET | `/batches/{id}` | `BatchDetail` (404 jika tidak ada/tidak published) | **runtime (browser)** |
| GET | `/testimonials` | `Testimonial[]` | build |
| GET | `/faqs` | `Faq[]` (FAQ umum, `program_slug = null`) | build |
| GET | `/assessment` | `Assessment` | build |
| POST | `/internal/assessment/score` body `{ "answers": { "<question_id>": "<option_id>" } }` | `AssessmentResult` tanpa `attempt_token` (422 jika tidak valid/tidak lengkap, 401 tanpa token) | **tidak** — hanya portal (server-to-server) |

`seats_taken` saat ini selalu `0` (modul Commerce belum ada); dihitung lewat interface `SeatInventory` yang nanti diimplementasikan Commerce.

Scoring assessment: setiap opsi terhubung ke satu kategori; tiap jawaban menambah skor kategori itu +1. Kategori skor tertinggi (seri: urutan kategori pertama di daftar) menjadi rekomendasi; `recommendations` = program dalam kategori tsb (featured dulu), maks 3. Bobot tidak pernah dikirim ke browser.

## Aturan tambahan (v1.1)
- **Promo aktif** = `promo_price` terisi, lebih kecil dari `price`, dan (`promo_until` kosong **atau** `promo_until >= hari ini`, zona waktu Asia/Jakarta). Promo tanpa tanggal akhir berlaku sampai dihapus admin.
- **Urutan default**: `/programs` → featured dulu, lalu judul A–Z. `/categories`, `/faqs`, `/testimonials` → field `sort_order`, lalu judul. Batch → `start_date` naik.
- **404** untuk `/site` dan `/assessment` jika global belum diisi; `/programs/{slug}`, `/programs/{slug}/availability`, `/batches/{id}` jika tidak ada/tidak published.
- **422**: filter `/programs` tidak valid; `POST /internal/assessment/score` memakai key error `answers.<question_id>` (dan `answers` untuk error umum).
- **Status batch**: `status` di endpoint build-time bisa tertinggal sampai rebuild; sumber status kursi terkini adalah `/availability` dan `/batches/{id}` (`Cache-Control: no-store`).

## Scoring internal untuk portal (v1.4)
- `POST /internal/assessment/score` wajib header `Authorization: Bearer <PORTAL_SERVICE_TOKEN>` (dibandingkan
  constant-time; token kosong di server = endpoint menolak semua request → 401). Tanpa CORS; jangan dipanggil dari browser.
- CMS hanya menghitung skor (bobot tetap di CMS) dan **tidak menyimpan apa pun**. Penyimpanan attempt, `attempt_token`,
  dan klaim hasil ke akun ada di portal (`POST {portal}/api/assessment/score`, `GET {portal}/assessment/claim?token=`).
- `GET /assessment` (pertanyaan tanpa bobot) tetap publik untuk build Astro.

## Harga runtime untuk kartu (v1.3)
- Harga di respons build-time (`/programs`, `ProgramSummary` dalam `AssessmentResult`) bisa tertinggal sampai rebuild
  (admin ubah harga/promo, promo kedaluwarsa). Astro menghidrasi semua kartu harga di satu halaman dengan **satu**
  `GET /prices` saat halaman dibuka; harga build tetap tampil bila request gagal.
- Nilai dihitung dengan aturan yang sama seperti `/availability` dan `/batches/{id}`, sehingga kartu, detail, dan
  checkout-preview selalu menampilkan harga yang sama.

## Changelog
- **v1.4** (2026-10-27): **breaking** — `POST /assessment/score` publik dihapus dari CMS; scoring untuk browser pindah ke portal (`POST {portal}/api/assessment/score`, respons sama dengan v1.3 termasuk `attempt_token`). Baru: endpoint internal `POST /internal/assessment/score` (Bearer `PORTAL_SERVICE_TOKEN`, tanpa CORS, tanpa `attempt_token`, tidak menyimpan attempt). Tabel `customers` & `assessment_attempts` dihapus dari DB CMS.
- **v1.3** (2026-10-25): aditif — endpoint runtime `GET /prices` (harga terkini semua program published, opsional `?slugs=`). Field lain tidak berubah.
- **v1.2** (2026-10-24): aditif — `AssessmentResult.attempt_token`; attempt assessment disimpan server-side untuk klaim ke akun portal (`/portal/assessment/claim?token=`). Field lain tidak berubah.
- **v1.1** (2026-10-24): `ProgramSummary.promo_active`; `Availability.price/promo_price/promo_until`; `Site.seo`; endpoint `GET /batches/{id}`; promo tanpa `promo_until` = aktif; dokumentasi urutan, 404, 422.
- **v1** (2026-10-24): rilis awal.
