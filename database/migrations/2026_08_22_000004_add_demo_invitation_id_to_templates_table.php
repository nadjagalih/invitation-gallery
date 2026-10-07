<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dipisah dari pembuatan tabel `templates` karena FK-nya menunjuk `invitations`,
 * sementara `invitations` sendiri menunjuk `templates`. Kolomnya ditambahkan
 * setelah kedua tabel ada agar tidak ada ketergantungan melingkar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            // Target tombol "Lihat" pada kartu katalog.
            $table->foreignId('demo_invitation_id')->nullable()->constrained('invitations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('demo_invitation_id');
        });
    }
};
