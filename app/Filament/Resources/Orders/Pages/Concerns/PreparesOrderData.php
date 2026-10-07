<?php

namespace App\Filament\Resources\Orders\Pages\Concerns;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\OrderAddons;

/**
 * Penyesuaian data order yang sama untuk halaman Create dan Edit.
 *
 * Tiga hal tidak boleh datang dari kiriman browser karena ketiganya adalah
 * turunan, bukan masukan: bentuk `addons`, `total`, dan `order_number`. Total
 * yang ditulis tangan bisa tidak cocok dengan komponennya, dan nomor order yang
 * bisa dikirim klien bisa ditabrakkan dengan nomor yang sudah ada.
 */
trait PreparesOrderData
{
    /**
     * Kolom `addons` berbentuk peta, sedangkan repeater bekerja dengan daftar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['addons'] = OrderAddons::toList($data['addons'] ?? null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareOrderData(array $data): array
    {
        $addons = OrderAddons::toMap($data['addons'] ?? []);

        $data['addons'] = $addons === [] ? null : $addons;
        $data['total'] = max(
            0,
            self::amount($data, 'base_price')
                + array_sum(array_column($addons, OrderAddons::PRICE))
                - self::amount($data, 'discount'),
        );

        // Tanggal bayar adalah catatan kapan uang masuk. Admin yang mengubah
        // status menjadi lunas jarang ingat mengisinya, dan yang sudah terisi
        // tidak boleh digeser.
        $status = OrderStatus::tryFrom((string) ($data['status'] ?? ''));

        if ($status?->isPaid() && blank($data['paid_at'] ?? null)) {
            $data['paid_at'] = now();
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    protected function withOrderNumber(array $data): array
    {
        $data['order_number'] = Order::generateOrderNumber();

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    private static function amount(array $data, string $key): int
    {
        $value = $data[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }
}
