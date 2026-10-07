<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_gift_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_name');
            $table->unsignedBigInteger('amount')->nullable();
            $table->foreignId('invitation_bank_account_id')->nullable()
                ->constrained('invitation_bank_accounts')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['invitation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_gift_confirmations');
    }
};
