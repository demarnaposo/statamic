<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('token_hash', 64)->unique(); // sha256 attempt_token; token asli tidak disimpan
            $table->unsignedInteger('version');
            $table->json('answers');
            $table->json('scores');
            $table->string('recommended_category');
            // Tanpa foreign key: tabel customers milik modul Identity.
            $table->string('customer_id', 26)->nullable()->index();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempts');
    }
};
