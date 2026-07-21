<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Циклічний FK: sites.active_credential_id -> site_credentials.id (обидві таблиці вже існують).
        Schema::table('sites', function (Blueprint $table) {
            $table->foreign('active_credential_id')
                ->references('id')->on('site_credentials')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['active_credential_id']);
        });
    }
};
