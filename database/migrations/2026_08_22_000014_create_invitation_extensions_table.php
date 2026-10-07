<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kecil, tetapi menyangkut uang yang masuk dan umur file pelanggan.
 * Riwayatnya perlu bisa dibaca ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('previous_expires_at')->nullable();
            $table->timestamp('new_expires_at');
            $table->unsignedSmallInteger('days');
            $table->timestamps();

            $table->index(['invitation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_extensions');
    }
};
