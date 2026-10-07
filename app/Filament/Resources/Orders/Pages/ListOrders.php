<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Alur kerjanya: masuk → dibayar → dikerjakan → selesai. Tab pertama menjadi
     * tab aktif bawaan, dan di daftar order yang dicari admin bisa order mana
     * pun — jadi yang dibuka lebih dulu adalah seluruhnya.
     */
    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),

            'baru' => Tab::make('Menunggu Bayar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    OrderStatus::Pending,
                    OrderStatus::WaitingPayment,
                ])),

            'dikerjakan' => Tab::make('Sudah Dibayar')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    OrderStatus::Paid,
                    OrderStatus::InProgress,
                ])),

            'selesai' => Tab::make('Terkirim')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::Delivered)),

            'batal' => Tab::make('Batal / Refund')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    OrderStatus::Cancelled,
                    OrderStatus::Refunded,
                ])),
        ];
    }
}
