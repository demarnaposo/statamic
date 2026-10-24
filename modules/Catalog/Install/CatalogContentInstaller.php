<?php

namespace Modules\Catalog\Install;

use Illuminate\Console\OutputStyle;
use Modules\Catalog\Contracts\ContentInstaller;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;

/**
 * Membuat model konten katalog lewat PHP API Statamic (tersimpan di database
 * melalui eloquent driver) beserta data demo.
 */
final class CatalogContentInstaller implements ContentInstaller
{
    /** Teks bawaan v1 yang tidak berlaku lagi di v1.1; diganti hanya jika belum diubah admin. */
    private const STALE_DEFAULTS = [
        'promo_until' => ['instructions' => [
            'Termasuk tanggal ini. Tanpa tanggal, promo dianggap tidak aktif.',
            'Termasuk tanggal ini. Kosongkan agar promo berlaku terus sampai harga promo dihapus.',
        ]],
    ];

    public function installStructure(OutputStyle $output, bool $force = false): void
    {
        $this->taxonomy('categories', 'Kategori', $this->categoryBlueprint(), $output, $force);

        $this->collection('programs', 'Program Sertifikasi', $this->programBlueprint(), $output, $force,
            fn ($c) => $c->sortField('title')->sortDirection('asc'));

        $this->collection('batches', 'Batch / Jadwal', $this->batchBlueprint(), $output, $force,
            fn ($c) => $c->titleFormats('{{ code }}')->requiresSlugs(false)->sortField('start_date')->sortDirection('asc'));

        $this->collection('testimonials', 'Testimoni', $this->testimonialBlueprint(), $output, $force,
            fn ($c) => $c->titleFormats('{{ name }}')->requiresSlugs(false)->sortField('sort_order')->sortDirection('asc'));

        $this->collection('faqs', 'FAQ', $this->faqBlueprint(), $output, $force,
            fn ($c) => $c->titleFormats('{{ question }}')->requiresSlugs(false)->sortField('sort_order')->sortDirection('asc'));

        $this->globalSet('site', 'Pengaturan Situs', $this->siteBlueprint(), $output, $force);
    }

    public function installDemo(OutputStyle $output, bool $force = false): void
    {
        $demo = new DemoCatalog;

        foreach ($demo->categories() as $i => $category) {
            $term = Term::find('categories::'.$category['slug']);
            if ($term && ! $force) {
                continue;
            }
            $term ??= Term::make()->taxonomy('categories')->slug($category['slug']);
            $term->blueprint('category')
                ->data(['title' => $category['title'], 'description' => $category['description'], 'sort_order' => $i + 1])
                ->save();
        }
        $output->writeln('  • Kategori demo: '.count($demo->categories()));

        $programIds = [];
        foreach ($demo->programs() as $program) {
            $entry = $this->upsertEntry('programs', 'program', ['slug' => $program['slug']], $program['data'], $force, $program['slug']);
            $programIds[$program['slug']] = $entry->id();

            foreach ($program['batches'] as $batch) {
                $this->upsertEntry('batches', 'batch', ['code' => $batch['code']], $batch + ['program' => $entry->id()], $force);
            }
        }
        $output->writeln('  • Program demo: '.count($programIds).' (beserta batch)');

        foreach ($demo->testimonials() as $i => $t) {
            $t['sort_order'] = $i + 1;
            $t['program'] = isset($t['program']) ? ($programIds[$t['program']] ?? null) : null;
            $this->upsertEntry('testimonials', 'testimonial', ['name' => $t['name']], array_filter($t, fn ($v) => $v !== null), $force);
        }
        $output->writeln('  • Testimoni demo: '.count($demo->testimonials()));

        foreach ($demo->faqs() as $i => $f) {
            $f['program'] = isset($f['program']) ? ($programIds[$f['program']] ?? null) : null;
            $f['sort_order'] = $i + 1;
            $this->upsertEntry('faqs', 'faq', ['question' => $f['question']], array_filter($f, fn ($v) => $v !== null), $force);
        }
        $output->writeln('  • FAQ demo: '.count($demo->faqs()));

        $vars = GlobalSet::findByHandle('site')->inDefaultSite();
        if ($force || ! $vars->get('name')) {
            $vars->data($demo->site())->save();
            $output->writeln('  • Global "site" diisi');
        }
    }

    /** @param  array<string, string>  $match  pasangan field => nilai untuk mencari entry yang sudah ada */
    private function upsertEntry(string $collection, string $blueprint, array $match, array $data, bool $force, ?string $slug = null)
    {
        $query = Entry::query()->where('collection', $collection);
        foreach ($match as $field => $value) {
            $query->where($field, $value);
        }

        $entry = $query->first();
        if ($entry && ! $force) {
            return $entry;
        }

        $entry ??= Entry::make()->collection($collection)->locale('default');
        if ($slug) {
            $entry->slug($slug);
        }

        $entry->blueprint($blueprint)->published($data['published'] ?? true);
        unset($data['published']);
        $entry->data($data)->save();

        return $entry;
    }

    private function taxonomy(string $handle, string $title, array $blueprint, OutputStyle $output, bool $force): void
    {
        $taxonomy = Taxonomy::findByHandle($handle);
        if (! $taxonomy) {
            Taxonomy::make($handle)->title($title)->save();
            $output->writeln("  • Taxonomy <info>{$handle}</info> dibuat");
        }

        $this->blueprint('taxonomies.'.$handle, 'category', $blueprint, $output, $force);
    }

    private function collection(string $handle, string $title, array $blueprint, OutputStyle $output, bool $force, callable $configure): void
    {
        $collection = Collection::findByHandle($handle);
        if (! $collection || $force) {
            $collection ??= Collection::make($handle);
            $configure($collection->title($title));
            $collection->save();
            $output->writeln("  • Collection <info>{$handle}</info> ".($force ? 'diperbarui' : 'dibuat'));
        }

        $this->blueprint('collections.'.$handle, $blueprint['handle'], $blueprint['contents'], $output, $force);
    }

    private function globalSet(string $handle, string $title, array $blueprint, OutputStyle $output, bool $force): void
    {
        if (! GlobalSet::findByHandle($handle)) {
            GlobalSet::make($handle)->title($title)->save();
            $output->writeln("  • Global set <info>{$handle}</info> dibuat");
        }

        $this->blueprint('globals', $handle, $blueprint, $output, $force);
    }

    private function blueprint(string $namespace, string $handle, array $contents, OutputStyle $output, bool $force): void
    {
        $existing = Blueprint::find($namespace.'.'.$handle);
        if ($existing && ! $force) {
            [$merged, $changes] = BlueprintMerger::merge($existing->contents(), $contents, self::STALE_DEFAULTS);
            if ($changes !== []) {
                $existing->setContents($merged)->save();
                $output->writeln("  • Blueprint <info>{$namespace}.{$handle}</info> digabung: ".implode(', ', $changes));
            }

            return;
        }

        ($existing ?? Blueprint::make($handle)->setNamespace($namespace))
            ->setContents($contents)
            ->save();

        $output->writeln("  • Blueprint <info>{$namespace}.{$handle}</info> ".($existing ? 'diperbarui' : 'dibuat'));
    }

    private function categoryBlueprint(): array
    {
        return [
            'title' => 'Kategori',
            'tabs' => [
                'main' => Fields::tab('Kategori', [
                    Fields::section('Kategori Program', [
                        Fields::make('title', 'text', 'Nama Kategori', ['validate' => ['required', 'max:100'], 'instructions' => 'Nama yang tampil di website, mis. "Manajemen Proyek".']),
                        Fields::make('description', 'textarea', 'Deskripsi', ['instructions' => 'Satu-dua kalimat penjelasan kategori untuk halaman daftar program.']),
                        Fields::make('sort_order', 'integer', 'Urutan Tampil', ['default' => 0, 'instructions' => 'Angka kecil tampil lebih dulu. Juga menentukan prioritas rekomendasi asesmen jika skor seri.']),
                    ]),
                ]),
                'sidebar' => Fields::tab('Sidebar', [
                    Fields::section('', [
                        Fields::make('slug', 'slug', 'Slug', ['validate' => ['required', 'max:100'], 'instructions' => 'Kunci URL & filter API. Jangan diubah setelah tayang.']),
                    ]),
                ]),
            ],
        ];
    }

    private function programBlueprint(): array
    {
        return ['handle' => 'program', 'contents' => [
            'title' => 'Program Sertifikasi',
            'tabs' => [
                'main' => Fields::tab('Informasi Program', [
                    Fields::section('Identitas Program', [
                        Fields::make('title', 'text', 'Nama Program', ['validate' => ['required', 'max:150'], 'instructions' => 'Nama lengkap program seperti tampil di website.']),
                        Fields::make('sku', 'text', 'SKU / Kode Produk', [
                            'validate' => ['required', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/', Fields::UNIQUE_ENTRY],
                            'instructions' => 'Kode unik program, harus sama dengan "Internal Reference" produk di Odoo. Contoh: `CRT-MPP-01`. Jangan diubah setelah ada penjualan.',
                            'width' => 50,
                        ]),
                        Fields::category('category', 'Kategori', ['validate' => ['required'], 'width' => 50, 'instructions' => 'Pilih satu kategori.']),
                        Fields::make('issuer', 'button_group', 'Penerbit Sertifikat', [
                            'options' => ['internal' => 'Internal (lembaga kami)', 'bnsp' => 'BNSP'],
                            'default' => 'internal',
                            'validate' => ['required'],
                            'width' => 50,
                        ]),
                        Fields::make('level', 'select', 'Level', [
                            'options' => ['Dasar' => 'Dasar', 'Menengah' => 'Menengah', 'Lanjut' => 'Lanjut'],
                            'clearable' => true,
                            'width' => 25,
                        ]),
                        Fields::make('duration', 'text', 'Durasi', ['placeholder' => '3 hari (24 JP)', 'width' => 25, 'instructions' => 'Teks bebas, mis. "3 hari (24 JP)".']),
                    ]),
                    Fields::section('Konten', [
                        Fields::make('summary', 'textarea', 'Ringkasan', ['validate' => ['required', 'max:300'], 'character_limit' => 300, 'instructions' => 'Maksimal 300 karakter. Tampil di kartu program.']),
                        Fields::make('description', 'markdown', 'Deskripsi Lengkap', ['instructions' => 'Penjelasan program untuk halaman detail. Mendukung format Markdown (judul, daftar, tebal).']),
                        Fields::make('image', 'assets', 'Gambar', ['container' => 'assets', 'folder' => 'programs', 'max_files' => 1, 'instructions' => 'Opsional. Rasio 16:9, minimal 1200px lebar.']),
                    ]),
                ]),
                'details' => Fields::tab('Manfaat & Syarat', [
                    Fields::section('Detail Program', [
                        Fields::make('benefits', 'list', 'Manfaat', ['instructions' => 'Satu manfaat per baris.']),
                        Fields::make('requirements', 'list', 'Persyaratan Peserta', ['instructions' => 'Satu persyaratan per baris.']),
                        Fields::make('audience', 'list', 'Cocok Untuk', ['instructions' => 'Target peserta, satu per baris (mis. "Project Manager").']),
                    ]),
                ]),
                'pricing' => Fields::tab('Harga & Promo', [
                    Fields::section('Harga', [
                        Fields::make('price', 'integer', 'Harga (Rp, sudah termasuk PPN)', [
                            'validate' => ['required', 'integer', 'min:0'],
                            'prepend' => 'Rp',
                            'instructions' => 'Harga normal per peserta dalam Rupiah, **sudah termasuk PPN**. Tulis angka saja tanpa titik, mis. `7500000`.',
                        ]),
                    ]),
                    Fields::section('Promo', [
                        Fields::make('promo_price', 'integer', 'Harga Promo (Rp, sudah termasuk PPN)', [
                            'validate' => ['nullable', 'integer', 'min:1', 'lt:price'],
                            'prepend' => 'Rp',
                            'width' => 50,
                            'instructions' => 'Opsional. Harus lebih kecil dari harga normal.',
                        ]),
                        Fields::make('promo_until', 'date', 'Promo Berlaku Sampai', [
                            'mode' => 'single',
                            'time_enabled' => false,
                            'width' => 50,
                            'instructions' => 'Termasuk tanggal ini. Kosongkan agar promo berlaku terus sampai harga promo dihapus.',
                        ]),
                    ], 'Harga promo otomatis dipakai selama lebih kecil dari harga normal dan tanggal hari ini belum melewati "Promo Berlaku Sampai" (atau tanggal dikosongkan).'),
                ]),
                'sidebar' => Fields::tab('Sidebar', [
                    Fields::section('', [
                        Fields::make('slug', 'slug', 'Slug URL', ['validate' => ['required', 'max:200', Fields::UNIQUE_ENTRY], 'instructions' => 'Alamat halaman program. Jangan diubah setelah tayang.']),
                        Fields::make('featured', 'toggle', 'Unggulan', ['instructions' => 'Tampilkan di beranda & prioritaskan di rekomendasi.']),
                    ]),
                ]),
            ],
        ]];
    }

    private function batchBlueprint(): array
    {
        return ['handle' => 'batch', 'contents' => [
            'title' => 'Batch',
            'tabs' => [
                'main' => Fields::tab('Jadwal Batch', [
                    Fields::section('Program & Kode', [
                        Fields::entries('program', 'Program', 'programs', ['validate' => ['required'], 'instructions' => 'Program sertifikasi untuk batch ini.']),
                        Fields::make('code', 'text', 'Kode Batch', [
                            'validate' => ['required', 'max:50', Fields::UNIQUE_ENTRY],
                            'instructions' => 'Unik, mis. `MPP-2611` (kode program + tahun + bulan). Dipakai sebagai judul.',
                        ]),
                    ]),
                    Fields::section('Jadwal & Lokasi', [
                        Fields::make('start_date', 'date', 'Tanggal Mulai', ['mode' => 'single', 'time_enabled' => false, 'validate' => ['required'], 'width' => 50]),
                        Fields::make('end_date', 'date', 'Tanggal Selesai', ['mode' => 'single', 'time_enabled' => false, 'validate' => ['required'], 'width' => 50]),
                        Fields::make('mode', 'button_group', 'Metode', [
                            'options' => ['online' => 'Online', 'offline' => 'Offline (tatap muka)', 'hybrid' => 'Hybrid'],
                            'default' => 'online',
                            'validate' => ['required'],
                        ]),
                        Fields::make('location', 'text', 'Lokasi', ['instructions' => 'Kota/alamat untuk offline/hybrid, mis. "Jakarta Selatan". Untuk online boleh "Zoom".']),
                    ]),
                    Fields::section('Kuota & Harga', [
                        Fields::make('capacity', 'integer', 'Kapasitas (kursi)', ['validate' => ['required', 'integer', 'min:1'], 'default' => 20, 'width' => 50]),
                        Fields::make('price_override', 'integer', 'Harga Khusus Batch (Rp, sudah termasuk PPN)', [
                            'validate' => ['nullable', 'integer', 'min:1'],
                            'prepend' => 'Rp',
                            'width' => 50,
                            'instructions' => 'Kosongkan untuk memakai harga program (termasuk promo yang sedang aktif).',
                        ]),
                    ]),
                ]),
                'sidebar' => Fields::tab('Sidebar', [
                    Fields::section('', [
                        Fields::make('status', 'button_group', 'Status Pendaftaran', [
                            'options' => ['open' => 'Dibuka', 'closed' => 'Ditutup'],
                            'default' => 'open',
                            'validate' => ['required'],
                            'instructions' => 'Status "Penuh" dihitung otomatis dari jumlah kursi terjual.',
                        ]),
                    ]),
                ]),
            ],
        ]];
    }

    private function testimonialBlueprint(): array
    {
        return ['handle' => 'testimonial', 'contents' => [
            'title' => 'Testimoni',
            'tabs' => [
                'main' => Fields::tab('Testimoni', [
                    Fields::section('Alumni', [
                        Fields::make('name', 'text', 'Nama', ['validate' => ['required', 'max:100'], 'width' => 50]),
                        Fields::make('role', 'text', 'Jabatan', ['width' => 25]),
                        Fields::make('company', 'text', 'Perusahaan', ['width' => 25]),
                        Fields::make('quote', 'textarea', 'Kutipan', ['validate' => ['required', 'max:600'], 'instructions' => 'Kesan alumni, tanpa tanda kutip.']),
                        Fields::entries('program', 'Program yang Diikuti', 'programs', ['width' => 50, 'instructions' => 'Opsional.']),
                        Fields::make('sort_order', 'integer', 'Urutan Tampil', ['default' => 0, 'width' => 50, 'instructions' => 'Angka kecil tampil lebih dulu.']),
                    ]),
                ]),
            ],
        ]];
    }

    private function faqBlueprint(): array
    {
        return ['handle' => 'faq', 'contents' => [
            'title' => 'FAQ',
            'tabs' => [
                'main' => Fields::tab('FAQ', [
                    Fields::section('Pertanyaan', [
                        Fields::make('question', 'text', 'Pertanyaan', ['validate' => ['required', 'max:200']]),
                        Fields::make('answer', 'markdown', 'Jawaban', ['validate' => ['required'], 'instructions' => 'Mendukung Markdown.']),
                        Fields::entries('program', 'Khusus Program', 'programs', ['width' => 50, 'instructions' => 'Kosongkan untuk FAQ umum (tampil di halaman FAQ).']),
                        Fields::make('sort_order', 'integer', 'Urutan Tampil', ['default' => 0, 'width' => 50, 'instructions' => 'Angka kecil tampil lebih dulu.']),
                    ]),
                ]),
            ],
        ]];
    }

    private function siteBlueprint(): array
    {
        return [
            'title' => 'Pengaturan Situs',
            'tabs' => [
                'main' => Fields::tab('Umum', [
                    Fields::section('Identitas', [
                        Fields::make('name', 'text', 'Nama Situs', ['validate' => ['required']]),
                        Fields::make('tagline', 'text', 'Tagline'),
                    ]),
                    Fields::section('Hero Beranda', [
                        Fields::make('hero_title', 'text', 'Judul Hero'),
                        Fields::make('hero_subtitle', 'textarea', 'Subjudul Hero'),
                        Fields::make('hero_cta_label', 'text', 'Label Tombol CTA', ['placeholder' => 'Lihat Program']),
                    ]),
                    Fields::section('SEO & Logo', [
                        Fields::make('logo', 'assets', 'Logo', ['container' => 'assets', 'folder' => 'brand', 'max_files' => 1, 'width' => 50, 'instructions' => 'Opsional. SVG atau PNG latar transparan.']),
                        Fields::make('default_og_image', 'assets', 'Gambar Share Default (OG Image)', ['container' => 'assets', 'folder' => 'brand', 'max_files' => 1, 'width' => 50, 'instructions' => 'Opsional. 1200×630 px; dipakai saat halaman dibagikan di media sosial.']),
                    ]),
                ]),
                'content' => Fields::tab('Statistik & Cara Kerja', [
                    Fields::section('Statistik', [
                        Fields::make('stats', 'grid', 'Statistik', [
                            'mode' => 'table',
                            'max_rows' => 6,
                            'add_row' => 'Tambah Statistik',
                            'instructions' => 'Angka pencapaian di beranda, mis. "12.000+" / "Alumni".',
                            'fields' => [
                                Fields::make('value', 'text', 'Nilai'),
                                Fields::make('label', 'text', 'Label'),
                            ],
                        ]),
                    ]),
                    Fields::section('Cara Kerja', [
                        Fields::make('steps', 'grid', 'Langkah', [
                            'mode' => 'stacked',
                            'add_row' => 'Tambah Langkah',
                            'instructions' => 'Urutan langkah dari mendaftar sampai mendapat sertifikat.',
                            'fields' => [
                                Fields::make('title', 'text', 'Judul Langkah'),
                                Fields::make('description', 'textarea', 'Penjelasan'),
                            ],
                        ]),
                    ]),
                ]),
                'contact' => Fields::tab('Kontak', [
                    Fields::section('Kontak', [
                        Fields::make('contact_email', 'text', 'Email', ['input_type' => 'email', 'width' => 50]),
                        Fields::make('contact_phone', 'text', 'Telepon', ['width' => 50]),
                        Fields::make('contact_whatsapp', 'text', 'WhatsApp', ['width' => 50, 'instructions' => 'Format internasional tanpa +, mis. 6281234567890.']),
                        Fields::make('contact_address', 'textarea', 'Alamat'),
                    ]),
                ]),
            ],
        ];
    }
}
