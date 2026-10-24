<?php

namespace Tests\Unit\Catalog;

use Modules\Catalog\Contracts\Data\BatchSummary;
use Modules\Catalog\Domain\CatalogRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CatalogRulesTest extends TestCase
{
    public static function promoCases(): array
    {
        return [
            'promo aktif' => [6000000, '2026-11-15', true, 6000000],
            'berakhir hari ini masih aktif' => [6000000, '2026-10-24', true, 6000000],
            'sudah lewat' => [6000000, '2026-10-23', false, 7500000],
            'tanpa tanggal = aktif sampai dihapus' => [6000000, null, true, 6000000],
            'tanpa harga promo' => [null, '2026-12-31', false, 7500000],
            'tanpa harga promo & tanpa tanggal' => [null, null, false, 7500000],
            'harga promo nol' => [0, '2026-12-31', false, 7500000],
            'harga promo sama dengan harga normal' => [7500000, '2026-12-31', false, 7500000],
            'harga promo lebih besar' => [8000000, null, false, 7500000],
        ];
    }

    #[DataProvider('promoCases')]
    public function test_effective_price_and_promo(?int $promo, ?string $until, bool $active, int $expected): void
    {
        $this->assertSame($active, CatalogRules::promoActive(7500000, $promo, $until, '2026-10-24'));
        $this->assertSame($expected, CatalogRules::effectivePrice(7500000, $promo, $until, '2026-10-24'));
    }

    public function test_batch_price_uses_override_when_set(): void
    {
        $this->assertSame(8000000, CatalogRules::batchPrice(8000000, 6000000));
        $this->assertSame(6000000, CatalogRules::batchPrice(null, 6000000));
        $this->assertSame(6000000, CatalogRules::batchPrice(0, 6000000));
    }

    public function test_batch_status(): void
    {
        $this->assertSame('open', CatalogRules::batchStatus('open', 20, 0));
        $this->assertSame('open', CatalogRules::batchStatus('open', 20, 19));
        $this->assertSame('full', CatalogRules::batchStatus('open', 20, 20));
        $this->assertSame('full', CatalogRules::batchStatus('open', 20, 25));
        $this->assertSame('closed', CatalogRules::batchStatus('closed', 20, 0));
        $this->assertSame('closed', CatalogRules::batchStatus('closed', 20, 20));
    }

    public function test_next_batch_is_nearest_upcoming_open_batch(): void
    {
        $batches = [
            $this->batch('C', '2027-01-10', 'open'),
            $this->batch('PAST', '2026-10-01', 'open'),
            $this->batch('A', '2026-11-01', 'closed'),
            $this->batch('B', '2026-12-01', 'full'),
            $this->batch('TODAY', '2026-10-24', 'closed'),
        ];

        $this->assertSame(['TODAY', 'A', 'B', 'C'], array_map(fn ($b) => $b->code, CatalogRules::upcomingBatches($batches, '2026-10-24')));
        $this->assertSame('C', CatalogRules::nextBatch($batches, '2026-10-24')?->code);
        $this->assertNull(CatalogRules::nextBatch([$this->batch('X', '2026-01-01', 'open')], '2026-10-24'));
    }

    private function batch(string $code, string $start, string $status): BatchSummary
    {
        return new BatchSummary($code, $code, $code, $start, $start, 'online', null, 20, 1000, $status);
    }
}
