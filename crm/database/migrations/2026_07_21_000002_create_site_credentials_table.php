<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_credentials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->text('secret_encrypted');                    // encrypted-at-rest (Конституція v2.0.1) — НЕ хеш
            $table->string('sig_version', 8)->default('v1');
            $table->string('state', 16)->default('active');
            $table->timestampTz('issued_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE site_credentials ADD CONSTRAINT site_credentials_state_check CHECK (state IN ('active','revoked'))");
        // Один активний секрет на сайт (data-model §4).
        DB::statement("CREATE UNIQUE INDEX site_credentials_one_active ON site_credentials (site_id) WHERE state = 'active'");
    }

    public function down(): void
    {
        Schema::dropIfExists('site_credentials');
    }
};
