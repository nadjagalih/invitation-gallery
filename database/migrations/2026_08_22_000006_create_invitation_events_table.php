<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            // Indonesia punya tiga zona waktu. Countdown atau file kalender yang salah
            // satu jam akan terlihat langsung oleh ratusan tamu.
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('venue_name');
            $table->text('address');
            $table->string('maps_url', 2048)->nullable();
            $table->text('notes')->nullable();
            // Target countdown dan Save The Date.
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invitation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_events');
    }
};
