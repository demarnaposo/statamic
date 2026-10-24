<?php

namespace Modules\Catalog\Application;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Contracts\Data\BatchDetail;
use Modules\Catalog\Contracts\Data\BatchSummary;
use Modules\Catalog\Contracts\Data\Category;
use Modules\Catalog\Contracts\Data\Faq;
use Modules\Catalog\Contracts\Data\ProgramDetail;
use Modules\Catalog\Contracts\Data\ProgramSummary;
use Modules\Catalog\Contracts\Data\Site;
use Modules\Catalog\Contracts\Data\Testimonial;
use Modules\Catalog\Contracts\SeatInventory;
use Modules\Catalog\Domain\CatalogRules;
use Modules\Catalog\Domain\Today;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Globals\Variables;
use Statamic\Facades\Entry as Entries;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Markdown;
use Statamic\Facades\Term;

/**
 * Membaca entry/term/global Statamic dan memetakannya ke DTO kontrak.
 * Tanpa cache (lihat CachedCatalogQuery). Hanya entry published.
 */
final class StatamicCatalogQuery implements CatalogQuery
{
    public const ISSUERS = ['internal', 'bnsp'];

    public const MODES = ['online', 'offline', 'hybrid'];

    public function __construct(
        private readonly SeatInventory $seats,
        private readonly Today $today,
    ) {}

    public function site(): ?Site
    {
        $vars = GlobalSet::findByHandle('site')?->inDefaultSite();

        // Global ada tetapi belum diisi (nama wajib) = belum tersedia → 404.
        if (! $vars || ! filled($vars->get('name'))) {
            return null;
        }

        return new Site(
            name: (string) $vars->get('name', ''),
            seo: [
                'default_og_image_url' => $this->assetUrl($vars, 'default_og_image'),
                'logo_url' => $this->assetUrl($vars, 'logo'),
            ],
            tagline: (string) $vars->get('tagline', ''),
            hero: [
                'title' => (string) $vars->get('hero_title', ''),
                'subtitle' => (string) $vars->get('hero_subtitle', ''),
                'cta_label' => (string) $vars->get('hero_cta_label', ''),
            ],
            stats: $this->rows($vars->get('stats'), ['label', 'value']),
            steps: $this->rows($vars->get('steps'), ['title', 'description']),
            contact: [
                'email' => (string) $vars->get('contact_email', ''),
                'phone' => (string) $vars->get('contact_phone', ''),
                'whatsapp' => (string) $vars->get('contact_whatsapp', ''),
                'address' => (string) $vars->get('contact_address', ''),
            ],
        );
    }

    public function categories(): array
    {
        return array_values($this->categoryMap());
    }

    public function category(string $slug): ?Category
    {
        return $this->categoryMap()[$slug] ?? null;
    }

    public function programs(array $filters = []): array
    {
        $category = $filters['category'] ?? null;
        $issuer = $filters['issuer'] ?? null;
        $featured = $filters['featured'] ?? null;

        $summaries = array_map(
            fn (array $p) => $p['summary'],
            $this->loadPrograms(),
        );

        $filtered = array_filter($summaries, function (ProgramSummary $p) use ($category, $issuer, $featured) {
            return ($category === null || $p->category?->slug === $category)
                && ($issuer === null || $p->issuer === $issuer)
                && ($featured === null || $p->featured === $featured);
        });

        return array_values($filtered);
    }

    public function program(string $slug): ?ProgramDetail
    {
        foreach ($this->loadPrograms() as $program) {
            if ($program['summary']->slug !== $slug) {
                continue;
            }

            /** @var Entry $entry */
            $entry = $program['entry'];

            $faqs = $this->loadFaqs(fn (?string $programId) => $programId === (string) $entry->id());

            return new ProgramDetail(
                summary: $program['summary'],
                descriptionHtml: $this->markdown($entry->get('description')),
                benefits: $this->list($entry->get('benefits')),
                requirements: $this->list($entry->get('requirements')),
                audience: $this->list($entry->get('audience')),
                batches: $program['batches'],
                faqs: $faqs,
            );
        }

        return null;
    }

    public function batch(string $id): ?BatchDetail
    {
        $entry = Entries::find($id);

        if (! $entry || $entry->collectionHandle() !== 'batches' || ! $entry->published()) {
            return null;
        }

        $programId = $this->ref($entry->get('program'));

        foreach ($this->loadPrograms() as $program) {
            if ($program['summary']->id !== $programId) {
                continue;
            }

            $batch = $this->batchSummary($entry, $program['summary']->effectivePrice);

            return $batch ? new BatchDetail($batch, $program['summary']) : null;
        }

        return null;
    }

    public function testimonials(): array
    {
        $programSlugs = $this->programSlugMap();

        return $this->published('testimonials')
            ->sortBy(fn (Entry $e) => [(int) $e->get('sort_order', 0), (string) $e->get('name')])
            ->map(fn (Entry $e) => new Testimonial(
                id: (string) $e->id(),
                name: (string) $e->get('name', $e->get('title')),
                role: $this->str($e->get('role')),
                company: $this->str($e->get('company')),
                quote: (string) $e->get('quote', ''),
                programSlug: $this->lookup($programSlugs, $e->get('program')),
            ))
            ->values()
            ->all();
    }

    public function generalFaqs(): array
    {
        return $this->loadFaqs(fn (?string $programId) => $programId === null);
    }

    /**
     * Daftar program lengkap dengan entry & semua batch mendatang.
     *
     * @return list<array{entry: Entry, summary: ProgramSummary, batches: list<BatchSummary>}>
     */
    private function loadPrograms(): array
    {
        $today = $this->today->date();
        $categories = $this->categoryMap();
        $batchesByProgram = $this->published('batches')->groupBy(fn (Entry $e) => (string) $this->ref($e->get('program')));

        $programs = $this->published('programs')->map(function (Entry $entry) use ($today, $categories, $batchesByProgram) {
            $price = (int) $entry->get('price', 0);
            $promoPrice = $this->int($entry->get('promo_price'));
            $promoUntil = $this->date($entry->get('promo_until'));
            $effective = CatalogRules::effectivePrice($price, $promoPrice, $promoUntil, $today);

            $batches = ($batchesByProgram->get((string) $entry->id()) ?? collect())
                ->map(fn (Entry $b) => $this->batchSummary($b, $effective))
                ->filter()
                ->values()
                ->all();
            $batches = CatalogRules::upcomingBatches($batches, $today);

            $issuer = (string) $entry->get('issuer', 'internal');

            $summary = new ProgramSummary(
                id: (string) $entry->id(),
                slug: (string) $entry->slug(),
                title: (string) $entry->get('title'),
                sku: (string) $entry->get('sku', ''),
                category: $this->lookup($categories, $entry->get('category')),
                issuer: in_array($issuer, self::ISSUERS, true) ? $issuer : 'internal',
                level: $this->str($entry->get('level')),
                duration: $this->str($entry->get('duration')),
                summary: (string) $entry->get('summary', ''),
                imageUrl: $this->assetUrl($entry, 'image'),
                price: $price,
                promoPrice: $promoPrice,
                promoUntil: $promoUntil,
                effectivePrice: $effective,
                promoActive: CatalogRules::promoActive($price, $promoPrice, $promoUntil, $today),
                featured: (bool) $entry->get('featured', false),
                nextBatch: CatalogRules::nextBatch($batches, $today),
            );

            return ['entry' => $entry, 'summary' => $summary, 'batches' => $batches];
        })->all();

        usort($programs, fn ($a, $b) => [! $a['summary']->featured, mb_strtolower($a['summary']->title)]
            <=> [! $b['summary']->featured, mb_strtolower($b['summary']->title)]);

        return $programs;
    }

    private function batchSummary(Entry $entry, int $programEffectivePrice): ?BatchSummary
    {
        $start = $this->date($entry->get('start_date'));

        if ($start === null) {
            return null;
        }

        $capacity = max(0, (int) $entry->get('capacity', 0));
        $seatsTaken = $this->seats->seatsTaken((string) $entry->id());
        $mode = (string) $entry->get('mode', 'online');

        return new BatchSummary(
            id: (string) $entry->id(),
            code: (string) $entry->get('code', ''),
            title: (string) $entry->get('title', $entry->get('code', '')),
            startDate: $start,
            endDate: $this->date($entry->get('end_date')) ?? $start,
            mode: in_array($mode, self::MODES, true) ? $mode : 'online',
            location: $this->str($entry->get('location')),
            capacity: $capacity,
            price: CatalogRules::batchPrice($this->int($entry->get('price_override')), $programEffectivePrice),
            status: CatalogRules::batchStatus((string) $entry->get('status', 'open'), $capacity, $seatsTaken),
            seatsTaken: $seatsTaken,
        );
    }

    /**
     * @param  callable(?string): bool  $filter  menerima id program (null = FAQ umum)
     * @return list<Faq>
     */
    private function loadFaqs(callable $filter): array
    {
        $faqs = $this->published('faqs')
            ->filter(fn (Entry $e) => $filter($this->ref($e->get('program'))))
            ->sortBy(fn (Entry $e) => [(int) $e->get('sort_order', 0), (string) $e->get('question')]);

        $programSlugs = $faqs->contains(fn (Entry $e) => $this->ref($e->get('program')) !== null)
            ? $this->programSlugMap()
            : [];

        return $faqs
            ->map(fn (Entry $e) => new Faq(
                id: (string) $e->id(),
                question: (string) $e->get('question', $e->get('title')),
                answerHtml: $this->markdown($e->get('answer')),
                programSlug: $this->lookup($programSlugs, $e->get('program')),
            ))
            ->values()
            ->all();
    }

    /** @return array<string, Category> */
    private function categoryMap(): array
    {
        return Term::query()
            ->where('taxonomy', 'categories')
            ->get()
            ->sortBy(fn ($t) => [(int) ($t->get('sort_order') ?? 0), mb_strtolower((string) $t->title())])
            ->mapWithKeys(fn ($t) => [(string) $t->slug() => new Category(
                slug: (string) $t->slug(),
                title: (string) $t->title(),
                description: $this->str($t->get('description')),
            )])
            ->all();
    }

    /** @return array<string, string> id program published => slug */
    private function programSlugMap(): array
    {
        return $this->published('programs')
            ->mapWithKeys(fn (Entry $e) => [(string) $e->id() => (string) $e->slug()])
            ->all();
    }

    /** @return Collection<int, Entry> */
    private function published(string $collection): Collection
    {
        return Entries::query()
            ->where('collection', $collection)
            ->where('published', true)
            ->get()
            ->values();
    }

    /**
     * URL absolut asset (field assets max 1) berbasis APP_URL, bukan host request,
     * agar respons yang di-cache sama untuk semua klien. Null jika kosong/hilang.
     */
    private function assetUrl(Entry|Variables $item, string $field): ?string
    {
        if (! $item->get($field)) {
            return null;
        }

        $asset = $item->augmentedValue($field)->value();

        if ($asset instanceof Collection) {
            $asset = $asset->first();
        }

        $url = $asset instanceof Asset ? $asset->url() : null;

        if (! $url) {
            return null;
        }

        return preg_match('#^https?://#i', $url) ? $url : rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, string>>
     */
    private function rows(mixed $rows, array $keys): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn ($row) => is_array($row) && ($row['enabled'] ?? true) !== false)
            ->map(fn (array $row) => collect($keys)->mapWithKeys(fn ($k) => [$k => (string) ($row[$k] ?? '')])->all())
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function list(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();
    }

    private function markdown(mixed $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? '' : trim(Markdown::parse($value));
    }

    /**
     * @template T
     *
     * @param  array<string, T>  $map
     * @return T|null
     */
    private function lookup(array $map, mixed $ref): mixed
    {
        $key = $this->ref($ref);

        return $key === null ? null : ($map[$key] ?? null);
    }

    /** Nilai relasi (terms/entries max 1) bisa berupa string atau array berisi 1 item. */
    private function ref(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return $this->str($value);
    }

    private function str(mixed $value): ?string
    {
        if ($value === null || is_array($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function int(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private function date(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_array($value)) {
            $value = $value['date'] ?? $value['start'] ?? null;
        }

        $value = $this->str($value);

        return $value === null ? null : substr($value, 0, 10);
    }
}
