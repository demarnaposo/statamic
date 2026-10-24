<?php

namespace Modules\Catalog\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Catalog\Application\AstroDeployHook;
use Modules\Catalog\Contracts\ContentCache;
use Statamic\Events;

/**
 * Saat konten katalog berubah di Statamic: naikkan versi cache (semua kunci
 * lama basi) lalu jadwalkan deploy hook Astro (debounce).
 */
final class ContentChangedSubscriber
{
    public function __construct(
        private readonly ContentCache $cache,
        private readonly AstroDeployHook $deployHook,
    ) {}

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntrySaved::class => 'onEntry',
            Events\EntryDeleted::class => 'onEntry',
            Events\TermSaved::class => 'onTerm',
            Events\TermDeleted::class => 'onTerm',
            Events\LocalizedTermSaved::class => 'onTerm',
            Events\LocalizedTermDeleted::class => 'onTerm',
            Events\GlobalVariablesSaved::class => 'onGlobalVariables',
            Events\GlobalVariablesDeleted::class => 'onGlobalVariables',
            Events\GlobalSetSaved::class => 'onGlobalSet',
            Events\GlobalSetDeleted::class => 'onGlobalSet',
            Events\AssetSaved::class => 'changed',
            Events\AssetDeleted::class => 'changed',
        ];
    }

    public function onEntry(Events\EntrySaved|Events\EntryDeleted $event): void
    {
        if (in_array($event->entry->collectionHandle(), config('catalog.watch.collections', []), true)) {
            $this->changed();
        }
    }

    public function onTerm(object $event): void
    {
        $term = $event->term;

        if (in_array($term->taxonomyHandle(), config('catalog.watch.taxonomies', []), true)) {
            $this->changed();
        }
    }

    public function onGlobalVariables(Events\GlobalVariablesSaved|Events\GlobalVariablesDeleted $event): void
    {
        if (in_array($event->variables->handle(), config('catalog.watch.globals', []), true)) {
            $this->changed();
        }
    }

    public function onGlobalSet(Events\GlobalSetSaved|Events\GlobalSetDeleted $event): void
    {
        if (in_array($event->globals->handle(), config('catalog.watch.globals', []), true)) {
            $this->changed();
        }
    }

    public function changed(): void
    {
        $this->cache->flush();
        $this->deployHook->schedule();
    }
}
