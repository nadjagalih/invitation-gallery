<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Dipakai me-resolve direktori view: resources/views/invitations/{slug}/v{n}/
            $table->string('slug')->unique();
            $table->foreignId('template_category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            // Versi terbaru untuk undangan baru. Undangan lama tetap di versinya sendiri.
            $table->unsignedSmallInteger('current_version')->default(1);
            // Harga disimpan sebagai integer rupiah, bukan decimal: tidak ada pecahan
            // sen dan pembulatan float pada harga adalah sumber bug yang tidak perlu.
            $table->unsignedInteger('price');
            $table->unsignedInteger('promo_price')->nullable();
            $table->json('badges')->nullable();
            $table->json('thumbnails')->nullable();
            $table->json('supported_features')->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
