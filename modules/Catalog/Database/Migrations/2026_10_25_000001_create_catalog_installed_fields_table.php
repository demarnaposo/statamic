<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Field bawaan yang pernah dipasang `catalog:install` per blueprint (agar field yang dihapus admin tidak dipasang ulang). */
    public function up(): void
    {
        Schema::create('catalog_installed_fields', function (Blueprint $table) {
            $table->id();
            $table->string('blueprint'); // mis. "collections.programs.program"
            $table->string('handle');
            $table->timestamp('created_at')->nullable();
            $table->unique(['blueprint', 'handle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_installed_fields');
    }
};
