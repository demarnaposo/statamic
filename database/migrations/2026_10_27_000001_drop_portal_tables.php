<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Akun customer & attempt assessment pindah ke aplikasi portal (database `portal` terpisah, data mulai kosong).
 * Backup sebelum migrasi ini: storage/app/backups/statamic-before-portal-split-*.sql.gz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customers');
    }

    public function down(): void
    {
        // Tidak dibuat ulang di sini; pulihkan dari backup bila benar-benar perlu.
    }
};
