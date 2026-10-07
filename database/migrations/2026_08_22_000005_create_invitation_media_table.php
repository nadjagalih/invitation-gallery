<?php

use App\Enums\MediaCollection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->enum('collection', array_column(MediaCollection::cases(), 'value'));
            // Disk file ini, terpisah dari invitations.asset_disk. Membuat asset lama
            // yang diunggah saat FILESYSTEM_DISK=local tetap dapat di-resolve setelah
            // default berpindah ke r2.
            $table->string('disk');
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            // Path derivative per ukuran: {"480": "...", "960": "..."}
            $table->json('variants')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invitation_id', 'collection', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_media');
    }
};
