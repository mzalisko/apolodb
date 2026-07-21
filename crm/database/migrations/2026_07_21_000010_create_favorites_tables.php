<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Обране — позначка на сайті/групі, окремий швидкий доступ (design brief §1).
     * Per-user, тож у сайдбарі кожен оператор бачить свій набір.
     */
    public function up(): void
    {
        Schema::create('site_favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->primary(['user_id', 'site_id']);
        });

        Schema::create('group_favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->primary(['user_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_favorites');
        Schema::dropIfExists('group_favorites');
    }
};
