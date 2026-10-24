<?php

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\BatchDetail;
use Modules\Catalog\Contracts\Data\Category;
use Modules\Catalog\Contracts\Data\Faq;
use Modules\Catalog\Contracts\Data\ProgramDetail;
use Modules\Catalog\Contracts\Data\ProgramSummary;
use Modules\Catalog\Contracts\Data\Site;
use Modules\Catalog\Contracts\Data\Testimonial;

/**
 * Akses baca katalog (hanya konten published). Satu-satunya pintu masuk
 * yang boleh dipakai modul lain untuk membaca data katalog.
 */
interface CatalogQuery
{
    public function site(): ?Site;

    /** @return list<Category> urut sesuai "Urutan tampil" lalu nama */
    public function categories(): array;

    public function category(string $slug): ?Category;

    /**
     * @param  array{category?: ?string, issuer?: ?string, featured?: ?bool}  $filters
     * @return list<ProgramSummary> featured dulu, lalu judul
     */
    public function programs(array $filters = []): array;

    public function program(string $slug): ?ProgramDetail;

    /** Batch published (termasuk yang sudah lewat) dari program published; null jika tidak ada. */
    public function batch(string $id): ?BatchDetail;

    /** @return list<Testimonial> */
    public function testimonials(): array;

    /** @return list<Faq> FAQ umum (tanpa program) */
    public function generalFaqs(): array;
}
