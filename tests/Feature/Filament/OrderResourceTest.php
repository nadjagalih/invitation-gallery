<?php

namespace Tests\Feature\Filament;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Invitation;
use App\Models\Order;
use App\Models\Template;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\PanelTestCase;

/**
 * Tiga nilai pada order tidak boleh datang dari kiriman browser karena
 * ketiganya turunan: bentuk `addons`, `total`, dan `order_number`. Yang diuji di
 * sini adalah bahwa ketiganya benar-benar dihitung server.
 */
class OrderResourceTest extends PanelTestCase
{
    #[Test]
    public function order_baru_menghitung_total_dan_nomornya_sendiri(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'template_id' => $template->getKey(),
                'customer_name' => 'Bagas Prasetyo',
                'customer_phone' => '628123456789',
                'base_price' => 150_000,
                'discount' => 20_000,
                'addons' => [
                    ['feature' => 'music', 'price' => 50_000],
                    ['feature' => 'rsvpkit', 'price' => 75_000],
                ],
                'status' => OrderStatus::Pending->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::query()->sole();

        $this->assertSame(255_000, $order->total);
        $this->assertMatchesRegularExpression('/^INV-\d{8}-[A-Z0-9]{6}$/', $order->order_number);

        // Kunci petanya inilah yang menurunkan `invitations.features`.
        $this->assertSame(['music', 'rsvpkit'], array_keys($order->addons));
        $this->assertSame(50_000, $order->addons['music']['price']);
    }

    #[Test]
    public function total_kiriman_form_tidak_dipakai(): void
    {
        $template = Template::factory()->create();

        // `total` bukan field di form, jadi nilai palsu hanya bisa datang dari
        // kiriman yang dikarang. Yang tersimpan tetap hasil hitungan server.
        Livewire::test(CreateOrder::class)
            ->fillForm([
                'template_id' => $template->getKey(),
                'customer_name' => 'Sari',
                'customer_phone' => '628111111111',
                'base_price' => 200_000,
                'discount' => 0,
                'total' => 1,
                'status' => OrderStatus::Pending->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(200_000, Order::query()->sole()->total);
    }

    #[Test]
    public function diskon_lebih_besar_dari_tagihan_tidak_membuat_total_minus(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'template_id' => $template->getKey(),
                'customer_name' => 'Sari',
                'customer_phone' => '628111111111',
                'base_price' => 100_000,
                'discount' => 500_000,
                'status' => OrderStatus::Pending->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(0, Order::query()->sole()->total);
    }

    #[Test]
    public function status_lunas_mengisi_tanggal_bayar(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'template_id' => $template->getKey(),
                'customer_name' => 'Bagas',
                'customer_phone' => '628123456789',
                'base_price' => 150_000,
                'discount' => 0,
                'status' => OrderStatus::Paid->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNotNull(Order::query()->sole()->paid_at);
    }

    #[Test]
    public function tanggal_bayar_yang_sudah_ada_tidak_digeser(): void
    {
        // Picker-nya ->seconds(false), jadi detik tidak ikut bolak-balik.
        $paidAt = now()->subDays(3)->startOfMinute();

        $order = Order::factory()
            ->for(Template::factory())
            ->create(['status' => OrderStatus::Paid, 'paid_at' => $paidAt]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => OrderStatus::InProgress->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($paidAt->equalTo($order->refresh()->paid_at));
    }

    #[Test]
    public function add_on_yang_tersimpan_terbaca_kembali_sebagai_baris(): void
    {
        $order = Order::factory()
            ->for(Template::factory())
            ->withAddons(['music' => 50_000])
            ->create();

        // Kolomnya peta, formnya daftar. Bila jembatannya lepas, repeaternya
        // tampil kosong dan menyimpan ulang akan menghapus add-on yang dibeli.
        $rows = Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->get('data.addons');

        // Repeater memberi setiap barisnya kunci uuid sendiri; yang diperiksa
        // isinya, bukan kuncinya. assertEquals karena field numeric mengubah
        // harga menjadi float saat dihidrasi ke form.
        $this->assertEquals(
            [['feature' => 'music', 'price' => 50_000]],
            array_values($rows),
        );
    }

    #[Test]
    public function menyimpan_ulang_tidak_menghilangkan_add_on(): void
    {
        $order = Order::factory()
            ->for(Template::factory())
            ->withAddons(['music' => 50_000])
            ->create(['base_price' => 150_000, 'discount' => 0]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $order->refresh();

        $this->assertSame(['music'], array_keys($order->addons));
        $this->assertSame(200_000, $order->total);
    }

    #[Test]
    public function nomor_order_tidak_berubah_saat_disimpan_ulang(): void
    {
        $order = Order::factory()->for(Template::factory())->create();
        $number = $order->order_number;

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['order_number' => 'INV-00000000-PALSU'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($number, $order->refresh()->order_number);
    }

    #[Test]
    public function add_on_kembar_ditolak_form(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'template_id' => $template->getKey(),
                'customer_name' => 'Bagas',
                'customer_phone' => '628123456789',
                'base_price' => 150_000,
                'discount' => 0,
                'addons' => [
                    ['feature' => 'music', 'price' => 50_000],
                    ['feature' => 'music', 'price' => 70_000],
                ],
                'status' => OrderStatus::Pending->value,
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function aksi_tandai_lunas_mengisi_status_dan_tanggal(): void
    {
        $order = Order::factory()
            ->for(Template::factory())
            ->create(['status' => OrderStatus::WaitingPayment]);

        // Jalur transfer manual: admin melihat bukti transfer lalu menandai lunas
        // dari daftar, tanpa membuka halaman ubah.
        Livewire::test(ListOrders::class)
            ->callAction(TestAction::make('markPaid')->table($order));

        $order->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    #[Test]
    public function aksi_tandai_lunas_tidak_muncul_untuk_order_yang_sudah_lunas(): void
    {
        $order = Order::factory()->for(Template::factory())->paid()->create();

        Livewire::test(ListOrders::class)
            ->assertActionHidden(TestAction::make('markPaid')->table($order));
    }

    #[Test]
    public function daftar_order_bisa_disaring_ke_yang_belum_punya_undangan(): void
    {
        $belum = Order::factory()->for(Template::factory())->paid()->create();

        $sudah = Order::factory()
            ->for(Template::factory())
            ->paid()
            ->for(Invitation::factory()->for(Template::factory()))
            ->create();

        Livewire::test(ListOrders::class)
            ->filterTable('unfulfilled')
            ->assertCanSeeTableRecords([$belum])
            ->assertCanNotSeeTableRecords([$sudah]);
    }
}
