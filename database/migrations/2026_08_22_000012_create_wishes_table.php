<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('message');
            // Default-nya mengikuti invitations.moderate_wishes saat baris dibuat.
            $table->boolean('is_approved')->default(false);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['invitation_id', 'is_approved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishes');
    }
};
