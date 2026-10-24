<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Contracts\SeatInventory;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private const PROGRAM_SUMMARY_KEYS = [
        'id', 'slug', 'title', 'sku', 'category', 'issuer', 'level', 'duration', 'summary', 'image_url',
        'price', 'promo_price', 'promo_until', 'promo_active', 'effective_price', 'featured', 'next_batch',
    ];

    private const BATCH_KEYS = ['id', 'code', 'title', 'start_date', 'end_date', 'mode', 'location', 'capacity', 'price', 'status'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCatalog();
    }

    public function test_site_returns_contract_shape(): void
    {
        $this->siteGlobals();

        $this->getJson('/api/v1/site')
            ->assertOk()
            ->assertExactJson(['data' => [
                'name' => 'Sertifika',
                'seo' => ['default_og_image_url' => null, 'logo_url' => null],
                'tagline' => 'Tagline',
                'hero' => ['title' => 'Hero', 'subtitle' => 'Sub', 'cta_label' => 'Lihat Program'],
                'stats' => [['label' => 'Alumni', 'value' => '10+']],
                'steps' => [['title' => 'Daftar', 'description' => 'Isi formulir']],
                'contact' => ['email' => 'halo@example.test', 'phone' => '021', 'whatsapp' => '62811', 'address' => 'Jakarta'],
            ]]);
    }

    public function test_categories_are_ordered_by_sort_order(): void
    {
        $this->category('z', 'Zeta', 1);
        $this->category('a', 'Alfa', 2);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['slug' => 'z', 'title' => 'Zeta', 'description' => 'Deskripsi Zeta'],
                ['slug' => 'a', 'title' => 'Alfa', 'description' => 'Deskripsi Alfa'],
            ]]);
    }

    public function test_programs_list_shape_and_only_published(): void
    {
        $this->standardCatalog();

        $response = $this->getJson('/api/v1/programs')->assertOk();
        $data = $response->json('data');

        $this->assertSame(['promo-aktif', 'promo-lewat'], array_column($data, 'slug'), 'featured dulu, draft tidak tampil');
        $this->assertSame(self::PROGRAM_SUMMARY_KEYS, array_keys($data[0]));
        $this->assertSame(self::BATCH_KEYS, array_keys($data[0]['next_batch']));
        $this->assertSame(['slug' => 'pm', 'title' => 'Manajemen Proyek', 'description' => 'Deskripsi Manajemen Proyek'], $data[0]['category']);
    }

    public function test_promo_and_effective_price(): void
    {
        $this->standardCatalog();

        $data = collect($this->getJson('/api/v1/programs')->json('data'))->keyBy('slug');

        $active = $data['promo-aktif'];
        $this->assertSame(7500000, $active['price']);
        $this->assertSame(6000000, $active['promo_price']);
        $this->assertSame('2026-11-15', $active['promo_until']);
        $this->assertSame(6000000, $active['effective_price']);
        $this->assertTrue($active['promo_active']);

        $expired = $data['promo-lewat'];
        $this->assertSame(2500000, $expired['promo_price']);
        $this->assertSame(3000000, $expired['effective_price']);
        $this->assertFalse($expired['promo_active']);
    }

    public function test_promo_without_end_date_is_active_and_promo_not_below_price_is_ignored(): void
    {
        $this->category('pm', 'Manajemen Proyek');
        $open = $this->program('tanpa-tanggal', ['price' => 4000000, 'promo_price' => 3500000]);
        $this->program('promo-sama', ['price' => 4000000, 'promo_price' => 4000000, 'promo_until' => '2026-12-31']);
        $this->program('promo-lebih-mahal', ['price' => 4000000, 'promo_price' => 4500000]);
        $this->batch($open, 'T-1', '2026-11-10');

        $data = collect($this->getJson('/api/v1/programs')->json('data'))->keyBy('slug');

        $this->assertSame([3500000, null, true, 3500000], [$data['tanpa-tanggal']['promo_price'], $data['tanpa-tanggal']['promo_until'], $data['tanpa-tanggal']['promo_active'], $data['tanpa-tanggal']['effective_price']]);
        $this->assertSame(3500000, $data['tanpa-tanggal']['next_batch']['price'], 'harga batch mengikuti promo aktif');

        foreach (['promo-sama', 'promo-lebih-mahal'] as $slug) {
            $this->assertFalse($data[$slug]['promo_active'], $slug);
            $this->assertSame(4000000, $data[$slug]['effective_price'], $slug);
        }

        $availability = $this->getJson('/api/v1/programs/promo-lebih-mahal/availability')->json('data');
        $this->assertSame([4000000, 4500000, false, 4000000], [$availability['price'], $availability['promo_price'], $availability['promo_active'], $availability['effective_price']]);
    }

    public function test_next_batch_skips_past_closed_and_unpublished(): void
    {
        $this->standardCatalog();

        $next = collect($this->getJson('/api/v1/programs')->json('data'))->keyBy('slug')['promo-aktif']['next_batch'];

        $this->assertSame('P-OVR', $next['code']);
        $this->assertSame('2026-12-01', $next['start_date']);
        $this->assertSame(8000000, $next['price'], 'price_override menimpa harga efektif');
        $this->assertSame('open', $next['status']);
    }

    public function test_programs_filters(): void
    {
        $this->standardCatalog();

        $this->assertSame(['promo-lewat'], array_column($this->getJson('/api/v1/programs?category=sec')->json('data'), 'slug'));
        $this->assertSame(['promo-lewat'], array_column($this->getJson('/api/v1/programs?issuer=bnsp')->json('data'), 'slug'));
        $this->assertSame(['promo-aktif'], array_column($this->getJson('/api/v1/programs?featured=1')->json('data'), 'slug'));
        $this->assertSame([], $this->getJson('/api/v1/programs?category=tidak-ada')->json('data'));

        $this->getJson('/api/v1/programs?issuer=lainnya')
            ->assertStatus(422)
            ->assertJsonValidationErrors('issuer');
    }

    public function test_program_detail(): void
    {
        $this->standardCatalog();

        $data = $this->getJson('/api/v1/programs/promo-aktif')->assertOk()->json('data');

        $this->assertSame(array_merge(self::PROGRAM_SUMMARY_KEYS, ['description_html', 'benefits', 'requirements', 'audience', 'batches', 'faqs']), array_keys($data));
        $this->assertSame("<h2>Judul</h2>\n<p>Isi <strong>tebal</strong>.</p>", $data['description_html']);
        $this->assertSame(['Manfaat 1', 'Manfaat 2'], $data['benefits']);
        $this->assertSame([], $data['requirements']);
        $this->assertSame(['P-CLOSED', 'P-OVR', 'P-JAN'], array_column($data['batches'], 'code'));
        $this->assertSame(['closed', 'open', 'open'], array_column($data['batches'], 'status'));
        $this->assertSame([6000000, 8000000, 6000000], array_column($data['batches'], 'price'));
        $this->assertCount(1, $data['faqs']);
        $this->assertSame(['id', 'question', 'answer_html', 'program_slug'], array_keys($data['faqs'][0]));
        $this->assertSame('promo-aktif', $data['faqs'][0]['program_slug']);
    }

    public function test_program_detail_404_for_missing_and_unpublished(): void
    {
        $this->standardCatalog();

        $this->getJson('/api/v1/programs/tidak-ada')->assertNotFound()->assertExactJson(['message' => 'Program tidak ditemukan.']);
        $this->getJson('/api/v1/programs/draft')->assertNotFound()->assertExactJson(['message' => 'Program tidak ditemukan.']);
        $this->getJson('/api/v1/programs/draft/availability')->assertNotFound();
    }

    public function test_availability_is_runtime_with_seat_inventory(): void
    {
        ['promo' => $promo] = $this->standardCatalog();
        $janId = Entry::query()->where('collection', 'batches')->where('code', 'P-JAN')->first()->id();

        $this->app->instance(SeatInventory::class, new class($janId) implements SeatInventory
        {
            public function __construct(private string $fullId) {}

            public function seatsTaken(string $batchId): int
            {
                return $batchId === $this->fullId ? 20 : 3;
            }
        });

        $response = $this->getJson('/api/v1/programs/promo-aktif/availability')->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $data = $response->json('data');
        $this->assertSame(['program_slug', 'price', 'promo_price', 'promo_until', 'effective_price', 'promo_active', 'batches', 'generated_at'], array_keys($data));
        $this->assertSame('promo-aktif', $data['program_slug']);
        $this->assertSame([7500000, 6000000, '2026-11-15'], [$data['price'], $data['promo_price'], $data['promo_until']]);
        $this->assertSame(6000000, $data['effective_price']);
        $this->assertTrue($data['promo_active']);
        $this->assertSame(['id', 'code', 'capacity', 'seats_taken', 'seats_left', 'status', 'price'], array_keys($data['batches'][0]));

        $jan = collect($data['batches'])->firstWhere('code', 'P-JAN');
        $this->assertSame([20, 0, 'full'], [$jan['seats_taken'], $jan['seats_left'], $jan['status']]);
        $ovr = collect($data['batches'])->firstWhere('code', 'P-OVR');
        $this->assertSame([3, 17, 'open'], [$ovr['seats_taken'], $ovr['seats_left'], $ovr['status']]);

        $this->assertStringContainsString('no-store', $this->getJson('/api/v1/programs/tidak-ada/availability')->headers->get('Cache-Control'));
    }

    public function test_batch_detail_is_runtime_with_program_and_seats(): void
    {
        $this->standardCatalog();
        $ovr = Entry::query()->where('collection', 'batches')->where('code', 'P-OVR')->first();

        $this->app->instance(SeatInventory::class, new class implements SeatInventory
        {
            public function seatsTaken(string $batchId): int
            {
                return 5;
            }
        });

        $response = $this->getJson('/api/v1/batches/'.$ovr->id())->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $data = $response->json('data');
        $this->assertSame(array_merge(self::BATCH_KEYS, ['seats_taken', 'seats_left', 'program']), array_keys($data));
        $this->assertSame([$ovr->id(), 'P-OVR', '2026-12-01', 20, 5, 15, 8000000, 'open'], [
            $data['id'], $data['code'], $data['start_date'], $data['capacity'], $data['seats_taken'], $data['seats_left'], $data['price'], $data['status'],
        ]);
        $this->assertSame(self::PROGRAM_SUMMARY_KEYS, array_keys($data['program']));
        $this->assertSame(['promo-aktif', true, 6000000], [$data['program']['slug'], $data['program']['promo_active'], $data['program']['effective_price']]);

        // Batch tanpa override memakai harga efektif program; batch lampau tetap bisa dibaca.
        $past = Entry::query()->where('collection', 'batches')->where('code', 'P-PAST')->first();
        $this->getJson('/api/v1/batches/'.$past->id())->assertOk()->assertJsonPath('data.price', 6000000);
    }

    public function test_batch_detail_full_when_seats_sold_out(): void
    {
        ['promo' => $promo] = $this->standardCatalog();
        $jan = Entry::query()->where('collection', 'batches')->where('code', 'P-JAN')->first();

        $this->app->instance(SeatInventory::class, new class implements SeatInventory
        {
            public function seatsTaken(string $batchId): int
            {
                return 25;
            }
        });

        $this->getJson('/api/v1/batches/'.$jan->id())->assertOk()
            ->assertJsonPath('data.status', 'full')
            ->assertJsonPath('data.seats_left', 0);
    }

    public function test_batch_detail_404_for_missing_draft_or_non_batch(): void
    {
        ['promo' => $promo, 'draft' => $draft] = $this->standardCatalog();
        $draftBatch = Entry::query()->where('collection', 'batches')->where('code', 'P-DRAFT')->first();
        $batchOfDraftProgram = $this->batch($draft, 'D-1', '2026-11-11');

        foreach (['tidak-ada', $draftBatch->id(), $batchOfDraftProgram->id(), $promo->id()] as $id) {
            $response = $this->getJson('/api/v1/batches/'.$id)
                ->assertNotFound()
                ->assertExactJson(['message' => 'Batch tidak ditemukan.']);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
    }

    public function test_site_seo_urls_are_absolute_from_app_url(): void
    {
        config([
            'app.url' => 'https://cms.example.test',
            'filesystems.disks.assets.root' => storage_path('framework/testing/catalog-assets'),
        ]);
        Storage::disk('assets')->put('brand/logo.png', 'png');
        Storage::disk('assets')->put('brand/og.jpg', 'jpg');
        AssetContainer::make('assets')->disk('assets')->save();
        Asset::make()->container('assets')->path('brand/logo.png')->save();
        Asset::make()->container('assets')->path('brand/og.jpg')->save();

        $this->siteGlobals();
        $vars = GlobalSet::findByHandle('site')->inDefaultSite();
        $vars->set('logo', 'brand/logo.png')->set('default_og_image', 'brand/og.jpg')->save();

        $this->getJson('/api/v1/site')->assertOk()->assertJsonPath('data.seo', [
            'default_og_image_url' => 'https://cms.example.test/assets/brand/og.jpg',
            'logo_url' => 'https://cms.example.test/assets/brand/logo.png',
        ]);

        Storage::disk('assets')->deleteDirectory('brand');
    }

    public function test_site_and_assessment_404_when_globals_are_empty(): void
    {
        $this->getJson('/api/v1/site')->assertNotFound()->assertJson(['message' => 'Pengaturan situs belum diisi.']);
        $this->getJson('/api/v1/assessment')->assertNotFound()->assertJson(['message' => 'Asesmen belum tersedia.']);
    }

    public function test_faqs_returns_only_general_published_faqs_in_order(): void
    {
        $this->standardCatalog();

        $data = $this->getJson('/api/v1/faqs')->assertOk()->json('data');

        $this->assertSame(['Umum 1', 'Umum 2'], array_column($data, 'question'));
        $this->assertSame([null, null], array_column($data, 'program_slug'));
        $this->assertSame('<p>Jawaban <em>Umum 1</em></p>', $data[0]['answer_html']);
    }

    public function test_testimonials(): void
    {
        ['promo' => $promo] = $this->standardCatalog();

        Entry::make()->collection('testimonials')->blueprint('testimonial')->published(true)
            ->data(['name' => 'Rina', 'role' => 'PM', 'quote' => 'Bagus', 'program' => $promo->id(), 'sort_order' => 1])->save();
        Entry::make()->collection('testimonials')->blueprint('testimonial')->published(false)
            ->data(['name' => 'Draft', 'quote' => 'x'])->save();

        $data = $this->getJson('/api/v1/testimonials')->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame(['id', 'name', 'role', 'company', 'quote', 'program_slug'], array_keys($data[0]));
        $this->assertSame(['Rina', 'PM', null, 'Bagus', 'promo-aktif'], [$data[0]['name'], $data[0]['role'], $data[0]['company'], $data[0]['quote'], $data[0]['program_slug']]);
    }

    public function test_cache_is_invalidated_when_content_is_saved(): void
    {
        ['promo' => $promo] = $this->standardCatalog();

        $this->assertSame(7500000, $this->getJson('/api/v1/programs/promo-aktif')->json('data.price'));

        // Ubah langsung di DB tanpa event: respons masih dari cache.
        $promo->set('price', 9000000)->saveQuietly();
        $this->assertSame(7500000, $this->getJson('/api/v1/programs/promo-aktif')->json('data.price'));

        // Simpan normal (seperti dari CP) memicu EntrySaved → versi cache naik.
        $promo->set('price', 9100000)->save();
        $this->assertSame(9100000, $this->getJson('/api/v1/programs/promo-aktif')->json('data.price'));
    }

    public function test_cors_headers_for_astro_origin(): void
    {
        $this->category('pm', 'Manajemen Proyek');

        $this->getJson('/api/v1/categories', ['Origin' => 'http://localhost:4321'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:4321');
    }
}
