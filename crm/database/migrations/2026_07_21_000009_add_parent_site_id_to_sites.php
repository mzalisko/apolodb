<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Піддомен завжди закріплений за основним сайтом (design brief §1 «Сайти та ієрархія»,
     * вкладена структура в списку й breadcrumbs). Self-referential FK, nullable = сайт верхнього рівня.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('parent_site_id')
                ->nullable()
                ->after('domain')
                ->constrained('sites')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_site_id');
        });
    }
};
