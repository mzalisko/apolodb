<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_log_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestampTz('occurred_at');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->foreign('actor_user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('actor_label');                       // денормалізоване «хто» (стабільне)
            $table->unsignedBigInteger('site_id')->nullable();
            $table->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
            $table->string('site_domain')->nullable();           // денормалізований домен
            $table->string('event_type', 32);
            $table->string('field', 64)->nullable();
            $table->text('old_value')->nullable();               // «було» (секрети НІКОЛИ не пишуться)
            $table->text('new_value')->nullable();               // «стало»
            $table->jsonb('metadata')->nullable();               // correlation-id (FR-033) тощо

            $table->index(['site_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
            $table->index('occurred_at');
        });

        DB::statement("ALTER TABLE event_log_entries ADD CONSTRAINT event_log_type_check CHECK (event_type IN ('site_registered','token_issued','token_revoked','token_reissued','status_changed','site_deactivated','site_reactivated'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('event_log_entries');
    }
};
