<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('domain')->unique();                 // FR-006 (нормалізований lowercase)
            $table->string('site_identifier', 64)->unique();    // FR-002 публічний, стабільний (A-4)
            $table->unsignedBigInteger('active_credential_id')->nullable(); // FK додається пізніше (циклічність)
            $table->timestampTz('deactivated_at')->nullable();  // латч inactive
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
