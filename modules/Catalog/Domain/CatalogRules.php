<?php

namespace Modules\Catalog\Domain;

use Modules\Catalog\Contracts\Data\BatchSummary;

/**
 * Aturan bisnis katalog (harga efektif, promo, status batch, batch berikutnya).
 * Murni (tanpa I/O); semua tanggal berupa string `YYYY-MM-DD` sehingga
 * perbandingan leksikografis = perbandingan kronologis.
 */
final class CatalogRules
{
    /**
     * Promo aktif jika harga promo terisi (> 0), lebih kecil dari harga normal, dan
     * promo_until kosong (berlaku sampai dihapus admin) atau promo_until >= hari ini.
     */
    public static function promoActive(int $price, ?int $promoPrice, ?string $promoUntil, string $today): bool
    {
        return $promoPrice !== null
            && $promoPrice > 0
            && $promoPrice < $price
            && ($promoUntil === null || $promoUntil >= $today);
    }

    public static function effectivePrice(int $price, ?int $promoPrice, ?string $promoUntil, string $today): int
    {
        return self::promoActive($price, $promoPrice, $promoUntil, $today) ? $promoPrice : $price;
    }

    /** Harga per kursi batch: price_override jika diisi, selain itu harga efektif program. */
    public static function batchPrice(?int $priceOverride, int $programEffectivePrice): int
    {
        return ($priceOverride !== null && $priceOverride > 0) ? $priceOverride : $programEffectivePrice;
    }

    /** @return 'open'|'closed'|'full' */
    public static function batchStatus(string $status, int $capacity, int $seatsTaken): string
    {
        if ($status !== 'open') {
            return 'closed';
        }

        return $seatsTaken >= $capacity ? 'full' : 'open';
    }

    public static function isUpcoming(string $startDate, string $today): bool
    {
        return $startDate >= $today;
    }

    /**
     * @param  list<BatchSummary>  $batches
     * @return list<BatchSummary> batch mendatang, urut start_date lalu kode
     */
    public static function upcomingBatches(array $batches, string $today): array
    {
        $upcoming = array_values(array_filter(
            $batches,
            fn (BatchSummary $b) => self::isUpcoming($b->startDate, $today),
        ));

        usort($upcoming, fn (BatchSummary $a, BatchSummary $b) => [$a->startDate, $a->code] <=> [$b->startDate, $b->code]);

        return $upcoming;
    }

    /** @param  list<BatchSummary>  $batches */
    public static function nextBatch(array $batches, string $today): ?BatchSummary
    {
        foreach (self::upcomingBatches($batches, $today) as $batch) {
            if ($batch->status === 'open') {
                return $batch;
            }
        }

        return null;
    }
}
