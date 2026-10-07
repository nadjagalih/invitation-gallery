<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicOrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function calon_pelanggan_dapat_membuat_order_dari_template_aktif(): void
    {
        $template = Template::factory()->create([
            'price' => 350_000,
            'promo_price' => 149_000,
        ]);

        $response = $this->post(route('order.store', $template), [
            'customer_name' => 'Bagas Prasetyo',
            'customer_phone' => '628123456789',
            'customer_email' => 'bagas@example.test',
            'notes' => 'Butuh informasi add-on musik.',
        ]);

        $order = Order::query()->sole();

        $response->assertRedirect(route('order.thank-you', $order));
        $this->assertSame($template->getKey(), $order->template_id);
        $this->assertSame(149_000, $order->base_price);
        $this->assertSame(149_000, $order->total);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('628123456789', $order->customer_phone);
    }

    #[Test]
    public function order_tidak_dapat_dibuat_dari_template_nonaktif(): void
    {
        $template = Template::factory()->inactive()->create();

        $this->get(route('order.create', $template))->assertNotFound();

        $this->post(route('order.store', $template), [
            'customer_name' => 'Bagas',
            'customer_phone' => '628123456789',
        ])->assertNotFound();

        $this->assertDatabaseCount('orders', 0);
    }
}
