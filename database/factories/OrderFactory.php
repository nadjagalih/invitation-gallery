<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Models\Order;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $basePrice = fake()->numberBetween(10, 30) * 10_000;

        return [
            'order_number' => Order::generateOrderNumber(),
            'user_id' => null,
            'invitation_id' => null,
            'template_id' => Template::factory(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('08##########'),
            'customer_email' => fake()->safeEmail(),
            'base_price' => $basePrice,
            'addons' => [],
            'discount' => 0,
            'total' => $basePrice,
            'status' => OrderStatus::Pending,
            'payment_channel' => PaymentChannel::ManualTransfer,
            'payment_ref' => null,
            'paid_at' => null,
            'proof_path' => null,
            'notes' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    /**
     * Add-on menyimpan harga saat transaksi, bukan referensi ke harga sekarang.
     *
     * @param  array<string, int>  $addons
     */
    public function withAddons(array $addons): static
    {
        return $this->state(function (array $attributes) use ($addons) {
            $payload = [];
            foreach ($addons as $key => $price) {
                $payload[$key] = ['price' => $price, 'label' => str($key)->headline()->value()];
            }

            $total = $attributes['base_price'] + array_sum($addons) - $attributes['discount'];

            return ['addons' => $payload, 'total' => $total];
        });
    }
}
