<?php

use App\Enums\AssetState;
use App\Enums\InvitationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            // Nullable dan belum dipakai di MVP. Ada sejak migration pertama supaya
            // dashboard klien nanti tidak butuh migration + backfill + tulis ulang policy.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedSmallInteger('template_version')->default(1);
            $table->string('slug')->unique();
            // Terisi saat publikasi; sesudahnya slug read-only.
            $table->timestamp('slug_locked_at')->nullable();

            $table->enum('status', array_column(InvitationStatus::cases(), 'value'))
                ->default(InvitationStatus::Draft->value);
            $table->enum('asset_state', array_column(AssetState::cases(), 'value'))
                ->default(AssetState::Retained->value);
            // Tujuan upload baru saja. Disk file yang sudah ada tercatat di invitation_media.disk.
            $table->string('asset_disk')->default('public');
            $table->json('features')->nullable();

            $table->string('groom_nickname');
            $table->string('groom_full_name');
            $table->string('groom_child_order', 60)->nullable();
            $table->string('groom_father');
            $table->string('groom_mother');
            $table->string('groom_instagram')->nullable();

            $table->string('bride_nickname');
            $table->string('bride_full_name');
            $table->string('bride_child_order', 60)->nullable();
            $table->string('bride_father');
            $table->string('bride_mother');
            $table->string('bride_instagram')->nullable();

            // Tanggal utama: dipakai cover, sorting admin, dan menghitung expires_at.
            $table->date('event_date');
            $table->text('quote_text')->nullable();
            $table->string('quote_source')->nullable();
            $table->text('opening_words')->nullable();
            $table->text('closing_words')->nullable();
            $table->text('gift_address')->nullable();
            $table->boolean('moderate_wishes')->default(true);

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('grace_days')->default(30);
            $table->timestamp('asset_delete_at')->nullable();
            // Mencegah notifikasi peringatan terkirim berulang setiap hari.
            $table->timestamp('expiry_notified_at')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'asset_state', 'asset_delete_at']);
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
