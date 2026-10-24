# Kontrak API Catalog v1.2 (Statamic/Laravel → Astro)

Sumber kebenaran bersama untuk backend (`statamic/app`) dan frontend (`astro`).
Perubahan kontrak harus diperbarui di kedua repo.

## Konvensi
- Base URL lokal: `http://localhost:8000/api/v1`
- Semua respons sukses: `{ "data": ... }`. Error 404: `{ "message": "..." }`. Error 422: format validasi Laravel (`{ "message", "errors": { field: [..] } }`).
- Uang: **integer Rupiah, sudah termasuk PPN**. Tanggal: `YYYY-MM-DD`. Datetime: ISO 8601.
- API read-only kecuali `POST /assessment/score`. Hanya entry **published** yang dikembalikan.
- Field dikembalikan secara eksplisit (whitelist), bukan dump blueprint Statamic.
- Endpoint build-time boleh di-cache di server (invalidasi saat konten disimpan). Endpoint runtime (`availability`, `assessment/score`) tidak di-cache oleh CDN (`Cache-Control: no-store`).
- CORS: origin dari env `CORS_ALLOWED_ORIGINS` (lokal: `http://localhost:4321`).

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

type AssessmentResult = {
  version: number;
  scores: { category: Category; score: number }[];   // urut skor tertinggi
  recommended_category: Category;
  recommendations: ProgramSummary[];                  // maks 3
  attempt_token: string;  // v1.2: token acak sekali-tampil untuk klaim hasil ke akun portal (server hanya menyimpan hash-nya)
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
| GET | `/batches/{id}` | `BatchDetail` (404 jika tidak ada/tidak published) | **runtime (browser)** |
| GET | `/testimonials` | `Testimonial[]` | build |
| GET | `/faqs` | `Faq[]` (FAQ umum, `program_slug = null`) | build |
| GET | `/assessment` | `Assessment` | build |
| POST | `/assessment/score` body `{ "answers": { "<question_id>": "<option_id>" } }` | `AssessmentResult` (422 jika tidak valid/tidak lengkap) | **runtime (browser)** |

`seats_taken` saat ini selalu `0` (modul Commerce belum ada); dihitung lewat interface `SeatInventory` yang nanti diimplementasikan Commerce.

Scoring assessment: setiap opsi terhubung ke satu kategori; tiap jawaban menambah skor kategori itu +1. Kategori skor tertinggi (seri: urutan kategori pertama di daftar) menjadi rekomendasi; `recommendations` = program dalam kategori tsb (featured dulu), maks 3. Bobot tidak pernah dikirim ke browser.

## Aturan tambahan (v1.1)
- **Promo aktif** = `promo_price` terisi, lebih kecil dari `price`, dan (`promo_until` kosong **atau** `promo_until >= hari ini`, zona waktu Asia/Jakarta). Promo tanpa tanggal akhir berlaku sampai dihapus admin.
- **Urutan default**: `/programs` → featured dulu, lalu judul A–Z. `/categories`, `/faqs`, `/testimonials` → field `sort_order`, lalu judul. Batch → `start_date` naik.
- **404** untuk `/site` dan `/assessment` jika global belum diisi; `/programs/{slug}`, `/programs/{slug}/availability`, `/batches/{id}` jika tidak ada/tidak published.
- **422**: filter `/programs` tidak valid; `POST /assessment/score` memakai key error `answers.<question_id>` (dan `answers` untuk error umum).
- **Status batch**: `status` di endpoint build-time bisa tertinggal sampai rebuild; sumber status kursi terkini adalah `/availability` dan `/batches/{id}` (`Cache-Control: no-store`).

## Klaim hasil assessment ke akun portal (v1.2)
- Setiap `POST /assessment/score` yang valid **menyimpan attempt** (anonim) dan mengembalikan `attempt_token`.
- Astro mengarahkan tombol "Simpan hasil" ke `{PUBLIC_APP_URL}/assessment/claim?token=<attempt_token>`
  (lokal `PUBLIC_APP_URL=http://localhost:8000/portal`). Alternatif: tambahkan `?assessment=<attempt_token>` ke URL
  register/login portal (`/portal/register?assessment=…`).
- Portal menyimpan token di session; setelah customer daftar/masuk, attempt diklaim ke akun **sekali saja**.
  Token yang sudah diklaim (oleh siapa pun) atau tidak dikenal diabaikan tanpa error.
- Token jangan dicatat ke analytics/log; simpan paling lama selama sesi browser (mis. `sessionStorage`).

## Changelog
- **v1.2** (2026-10-24): aditif — `AssessmentResult.attempt_token`; attempt assessment disimpan server-side untuk klaim ke akun portal (`/portal/assessment/claim?token=`). Field lain tidak berubah.
- **v1.1** (2026-10-24): `ProgramSummary.promo_active`; `Availability.price/promo_price/promo_until`; `Site.seo`; endpoint `GET /batches/{id}`; promo tanpa `promo_until` = aktif; dokumentasi urutan, 404, 422.
- **v1** (2026-10-24): rilis awal.
