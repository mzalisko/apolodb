<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_statuses', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->primary();       // 1:1 із sites
            $table->foreign('site_id')->references('id')->on('sites')->cascadeOnDelete();
            $table->string('status', 16)->default('pending');       // FR-013
            $table->timestampTz('last_seen_at')->nullable();        // FR-012, база детекції офлайну (FR-014)
            $table->timestampTz('last_status_change_at');           // A-7 «час останнього оновлення» в UI
            $table->timestampTz('updated_at');
        });

        DB::statement("ALTER TABLE site_statuses ADD CONSTRAINT site_statuses_status_check CHECK (status IN ('pending','online','offline','inactive'))");
        // Частковий індекс детектора офлайну (research: offline detection).
        DB::statement("CREATE INDEX site_statuses_offline_sweep ON site_statuses (last_seen_at) WHERE status = 'online'");
        // Індекс фільтра «N із M» (FR-018).
        DB::statement('CREATE INDEX site_statuses_status_idx ON site_statuses (status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('site_statuses');
    }
};
