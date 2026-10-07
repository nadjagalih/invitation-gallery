<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();

            $table->string('customer_name');
            $table->string('customer_phone', 32);
            $table->string('customer_email')->nullable();

            $table->unsignedInteger('base_price');
            // Fitur berbayar yang dibeli beserta harganya saat transaksi. Nilai inilah
            // yang menurunkan invitations.features, sehingga perubahan harga add-on
            // di kemudian hari tidak mengubah hak fitur pelanggan lama.
            $table->json('addons')->nullable();
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');

            $table->enum('status', array_column(OrderStatus::cases(), 'value'))
                ->default(OrderStatus::Pending->value);
            $table->enum('payment_channel', array_column(PaymentChannel::cases(), 'value'))
                ->default(PaymentChannel::ManualTransfer->value);
            $table->string('payment_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
