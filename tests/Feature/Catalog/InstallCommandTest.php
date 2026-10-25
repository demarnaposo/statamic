<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Term;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_demo_is_idempotent_and_serves_api(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00', 'Asia/Jakarta'));

        $this->artisan('catalog:install', ['--demo' => true])->assertSuccessful();
        $this->artisan('catalog:install', ['--demo' => true])->assertSuccessful();

        $this->assertSame(5, Term::query()->where('taxonomy', 'categories')->count());
        $this->assertSame(8, Entry::query()->where('collection', 'programs')->count());
        $this->assertSame(19, Entry::query()->where('collection', 'batches')->count());
        $this->assertSame(11, Entry::query()->where('collection', 'faqs')->count());

        $this->getJson('/api/v1/programs')->assertOk()->assertJsonCount(8, 'data');
        $this->getJson('/api/v1/faqs')->assertOk()->assertJsonCount(6, 'data');
        $this->getJson('/api/v1/site')->assertOk()->assertJsonPath('data.name', 'Sertifika');
        $this->getJson('/api/v1/assessment')->assertOk()->assertJsonCount(7, 'data.questions');
    }

    public function test_reinstall_merges_new_blueprint_fields_without_touching_admin_changes(): void
    {
        $this->artisan('catalog:install', ['--demo' => true])->assertSuccessful();

        // Simulasikan blueprint v1 yang sudah diedit admin: tanpa field SEO, label diubah, field tambahan.
        $site = Blueprint::find('globals.site');
        $contents = $site->contents();
        $contents['tabs']['main']['sections'] = array_values(array_filter(
            $contents['tabs']['main']['sections'],
            fn ($s) => ($s['display'] ?? '') !== 'SEO & Logo',
        ));
        $contents['tabs']['main']['sections'][0]['fields'][0]['field']['display'] = 'Nama Brand (admin)';
        $contents['tabs']['main']['sections'][0]['fields'][] = ['handle' => 'custom_admin', 'field' => ['type' => 'text', 'display' => 'Custom']];
        $site->setContents($contents)->save();
        // Field SEO belum ada di kode versi sebelumnya → belum pernah dipasang.
        $this->forgetInstalled('globals.site', ['logo', 'default_og_image']);

        $program = Blueprint::find('collections.programs.program');
        $contents = $program->contents();
        foreach ($contents['tabs']['pricing']['sections'][1]['fields'] as $i => $field) {
            if ($field['handle'] === 'promo_until') {
                $contents['tabs']['pricing']['sections'][1]['fields'][$i]['field']['instructions'] = 'Termasuk tanggal ini. Tanpa tanggal, promo dianggap tidak aktif.';
            }
        }
        $program->setContents($contents)->save();

        GlobalSet::findByHandle('site')->inDefaultSite()->set('name', 'Nama Edit Admin')->save();
        $entry = Entry::query()->where('collection', 'programs')->where('slug', 'analis-data-python-sql')->first();
        $entry->set('summary', 'Ringkasan edit admin')->save();

        $this->artisan('catalog:install', ['--demo' => true])->assertSuccessful();

        $fields = Blueprint::find('globals.site')->fields()->all();
        $this->assertTrue($fields->has('logo') && $fields->has('default_og_image'), 'field baru ditambahkan');
        $this->assertTrue($fields->has('custom_admin'), 'field admin tetap ada');
        $this->assertSame('Nama Brand (admin)', $fields->get('name')->display());
        $this->assertSame('assets', $fields->get('logo')->type());

        $promoUntil = Blueprint::find('collections.programs.program')->field('promo_until');
        $this->assertStringContainsString('Kosongkan agar promo berlaku terus', $promoUntil->get('instructions'));

        $this->assertSame('Nama Edit Admin', GlobalSet::findByHandle('site')->inDefaultSite()->get('name'));
        $this->assertSame('Ringkasan edit admin', Entry::find($entry->id())->get('summary'));
        $this->getJson('/api/v1/site')->assertOk()
            ->assertJsonPath('data.name', 'Nama Edit Admin')
            ->assertJsonPath('data.seo', ['default_og_image_url' => null, 'logo_url' => null]);
    }

    public function test_fields_deleted_by_admin_are_not_reinstalled_and_new_fields_are_added_once(): void
    {
        $this->artisan('catalog:install')->assertSuccessful();

        $this->removeField('collections.programs.program', 'level');
        $this->artisan('catalog:install')->assertSuccessful();
        $this->assertFalse($this->hasField('collections.programs.program', 'level'), 'field yang dihapus admin tidak muncul lagi');

        // Field baru di kode (belum tercatat) → ditambahkan sekali; dihapus admin → tidak kembali.
        $this->removeField('globals.site', 'logo');
        $this->forgetInstalled('globals.site', ['logo']);
        $this->artisan('catalog:install')->expectsOutputToContain('globals.site digabung: +logo')->assertSuccessful();
        $this->assertTrue($this->hasField('globals.site', 'logo'));

        $this->removeField('globals.site', 'logo');
        $this->artisan('catalog:install')->doesntExpectOutputToContain('+logo')->assertSuccessful();
        $this->assertFalse($this->hasField('globals.site', 'logo'));
    }

    public function test_first_run_on_existing_blueprint_treats_missing_default_fields_as_deleted(): void
    {
        $this->artisan('catalog:install')->assertSuccessful();
        $this->removeField('collections.programs.program', 'level');
        DB::table('catalog_installed_fields')->delete(); // instalasi lama sebelum fitur pencatatan

        $this->artisan('catalog:install')->doesntExpectOutputToContain('+level')->assertSuccessful();

        $this->assertFalse($this->hasField('collections.programs.program', 'level'));
        $this->assertTrue(DB::table('catalog_installed_fields')->where(['blueprint' => 'collections.programs.program', 'handle' => 'level'])->exists());
        $this->assertTrue(DB::table('catalog_installed_fields')->where(['blueprint' => 'collections.programs.program', 'handle' => 'price'])->exists());
    }

    public function test_dry_run_reports_changes_without_saving(): void
    {
        $this->artisan('catalog:install')->assertSuccessful();
        $this->removeField('globals.site', 'logo');
        $this->removeField('globals.site', 'tagline');
        $this->forgetInstalled('globals.site', ['logo']);

        $this->artisan('catalog:install', ['--dry-run' => true])
            ->expectsOutputToContain('globals.site digabung: +logo')
            ->doesntExpectOutputToContain('+tagline')
            ->assertSuccessful();

        $this->assertFalse($this->hasField('globals.site', 'logo'), 'dry-run tidak menyimpan blueprint');
        $this->assertFalse(DB::table('catalog_installed_fields')->where(['blueprint' => 'globals.site', 'handle' => 'logo'])->exists());

        $this->artisan('catalog:install')->expectsOutputToContain('globals.site digabung: +logo')->assertSuccessful();
        $this->assertTrue($this->hasField('globals.site', 'logo'));
    }

    private function removeField(string $blueprint, string $handle): void
    {
        $bp = Blueprint::find($blueprint);
        $contents = $bp->contents();
        foreach ($contents['tabs'] as $t => $tab) {
            foreach ($tab['sections'] as $s => $section) {
                $contents['tabs'][$t]['sections'][$s]['fields'] = array_values(array_filter(
                    $section['fields'] ?? [],
                    fn ($f) => ($f['handle'] ?? null) !== $handle,
                ));
            }
        }
        $bp->setContents($contents)->save();
    }

    private function hasField(string $blueprint, string $handle): bool
    {
        return Blueprint::find($blueprint)->fields()->all()->has($handle);
    }

    /** @param  list<string>  $handles */
    private function forgetInstalled(string $blueprint, array $handles): void
    {
        DB::table('catalog_installed_fields')->where('blueprint', $blueprint)->whereIn('handle', $handles)->delete();
    }
}
