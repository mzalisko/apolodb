<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_site_access', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->smallInteger('level');    // 0 немає / 1 перегляд / 2 редагування
            $table->primary(['user_id', 'site_id']);
        });

        DB::statement('ALTER TABLE user_site_access ADD CONSTRAINT user_site_access_level_check CHECK (level IN (0,1,2))');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_site_access');
    }
};
