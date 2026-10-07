<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kosong di MVP. Terisi ketika add-on RSVPKIT dijual — kolom per-guest link
 * beserta check-in sudah siap sehingga tidak butuh migration lagi nanti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->string('token', 64)->nullable()->unique();
            $table->string('group_label')->nullable();
            $table->unsignedSmallInteger('quota')->default(1);
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();

            $table->index('invitation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
