<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('subject_id', 64)->index();
            $table->char('token_hash', 64)->unique();
            $table->char('previous_token_hash', 64)->nullable()->index();
            $table->string('label', 120)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('last_used_at');
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
