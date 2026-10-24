<?php

namespace Modules\Catalog\Application;

use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Contracts\ContentCache;
use Modules\Catalog\Contracts\Data\BatchDetail;
use Modules\Catalog\Contracts\Data\Category;
use Modules\Catalog\Contracts\Data\ProgramDetail;
use Modules\Catalog\Contracts\Data\Site;

/** Dekorator CatalogQuery dengan cache ber-versi (dipakai endpoint build-time). */
final class CachedCatalogQuery implements CatalogQuery
{
    public function __construct(
        private readonly StatamicCatalogQuery $inner,
        private readonly ContentCache $cache,
    ) {}

    public function site(): ?Site
    {
        return $this->cache->remember('site', fn () => $this->inner->site());
    }

    public function categories(): array
    {
        return $this->cache->remember('categories', fn () => $this->inner->categories());
    }

    public function category(string $slug): ?Category
    {
        foreach ($this->categories() as $category) {
            if ($category->slug === $slug) {
                return $category;
            }
        }

        return null;
    }

    public function programs(array $filters = []): array
    {
        $filters = array_filter(
            array_intersect_key($filters, array_flip(['category', 'issuer', 'featured'])),
            fn ($v) => $v !== null,
        );
        ksort($filters);

        return $this->cache->remember(
            'programs:'.md5(json_encode($filters)),
            fn () => $this->inner->programs($filters),
        );
    }

    public function program(string $slug): ?ProgramDetail
    {
        return $this->cache->remember('program:'.md5($slug), fn () => $this->inner->program($slug));
    }

    /** Data runtime (kursi terkini): tidak di-cache. */
    public function batch(string $id): ?BatchDetail
    {
        return $this->inner->batch($id);
    }

    public function testimonials(): array
    {
        return $this->cache->remember('testimonials', fn () => $this->inner->testimonials());
    }

    public function generalFaqs(): array
    {
        return $this->cache->remember('faqs', fn () => $this->inner->generalFaqs());
    }
}
