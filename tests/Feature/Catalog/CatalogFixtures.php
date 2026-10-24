<?php

namespace Tests\Feature\Catalog;

use Illuminate\Support\Carbon;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Term;

/** Membuat struktur konten (via catalog:install) dan fixture minimal yang deterministik. */
trait CatalogFixtures
{
    protected function setUpCatalog(): void
    {
        // "Hari ini" = 2026-10-24 di Asia/Jakarta.
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00', 'Asia/Jakarta'));
        config(['catalog.astro.deploy_hook_url' => null]);

        $this->artisan('catalog:install')->assertSuccessful();
    }

    protected function category(string $slug, string $title, int $order = 0): void
    {
        Term::make()->taxonomy('categories')->slug($slug)->blueprint('category')
            ->data(['title' => $title, 'description' => "Deskripsi {$title}", 'sort_order' => $order])
            ->save();
    }

    protected function program(string $slug, array $data = [], bool $published = true)
    {
        $entry = Entry::make()->collection('programs')->locale('default')->blueprint('program')
            ->slug($slug)->published($published)
            ->data(array_merge([
                'title' => ucwords(str_replace('-', ' ', $slug)),
                'sku' => strtoupper($slug),
                'category' => 'pm',
                'issuer' => 'internal',
                'summary' => 'Ringkasan '.$slug,
                'description' => "## Judul\n\nIsi **tebal**.",
                'benefits' => ['Manfaat 1', 'Manfaat 2'],
                'price' => 5000000,
            ], $data));
        $entry->save();

        return $entry;
    }

    protected function batch($program, string $code, string $start, array $data = [], bool $published = true)
    {
        $entry = Entry::make()->collection('batches')->locale('default')->blueprint('batch')->published($published)
            ->data(array_merge([
                'program' => $program->id(),
                'code' => $code,
                'start_date' => $start,
                'end_date' => $start,
                'mode' => 'online',
                'location' => 'Zoom',
                'capacity' => 20,
                'status' => 'open',
            ], $data));
        $entry->save();

        return $entry;
    }

    protected function faq(string $question, $program = null, int $order = 0, bool $published = true)
    {
        $entry = Entry::make()->collection('faqs')->locale('default')->blueprint('faq')->published($published)
            ->data(array_filter([
                'question' => $question,
                'answer' => 'Jawaban *'.$question.'*',
                'program' => $program?->id(),
                'sort_order' => $order,
            ], fn ($v) => $v !== null));
        $entry->save();

        return $entry;
    }

    protected function siteGlobals(): void
    {
        GlobalSet::findByHandle('site')->inDefaultSite()->data([
            'name' => 'Sertifika',
            'tagline' => 'Tagline',
            'hero_title' => 'Hero',
            'hero_subtitle' => 'Sub',
            'hero_cta_label' => 'Lihat Program',
            'stats' => [['id' => 'a1', 'value' => '10+', 'label' => 'Alumni']],
            'steps' => [['id' => 's1', 'title' => 'Daftar', 'description' => 'Isi formulir']],
            'contact_email' => 'halo@example.test',
            'contact_phone' => '021',
            'contact_whatsapp' => '62811',
            'contact_address' => 'Jakarta',
        ])->save();
    }

    protected function standardCatalog(): array
    {
        $this->category('pm', 'Manajemen Proyek', 1);
        $this->category('sec', 'Keamanan', 2);

        $promo = $this->program('promo-aktif', ['price' => 7500000, 'promo_price' => 6000000, 'promo_until' => '2026-11-15', 'featured' => true]);
        $expired = $this->program('promo-lewat', ['price' => 3000000, 'promo_price' => 2500000, 'promo_until' => '2026-10-23', 'category' => 'sec', 'issuer' => 'bnsp']);
        $draft = $this->program('draft', [], published: false);

        $this->batch($promo, 'P-PAST', '2026-10-01');
        $this->batch($promo, 'P-CLOSED', '2026-11-01', ['status' => 'closed']);
        $this->batch($promo, 'P-OVR', '2026-12-01', ['price_override' => 8000000]);
        $this->batch($promo, 'P-JAN', '2027-01-10');
        $this->batch($promo, 'P-DRAFT', '2026-11-05', [], published: false);
        $this->batch($expired, 'E-NOV', '2026-11-20', ['mode' => 'offline', 'location' => 'Jakarta']);

        $this->faq('Umum 2', null, 2);
        $this->faq('Umum 1', null, 1);
        $this->faq('Umum draft', null, 3, published: false);
        $this->faq('Khusus promo', $promo);

        return compact('promo', 'expired', 'draft');
    }
}
