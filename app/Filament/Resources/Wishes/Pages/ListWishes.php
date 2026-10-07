<?php

namespace App\Filament\Resources\Wishes\Pages;

use App\Filament\Resources\Wishes\WishResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWishes extends ListRecords
{
    protected static string $resource = WishResource::class;

    /**
     * Tab pertama menjadi tab aktif bawaan, dan yang paling sering dibutuhkan
     * di antrean moderasi adalah yang belum diputuskan.
     */
    public function getTabs(): array
    {
        return [
            'menunggu' => Tab::make('Menunggu Moderasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_approved', false)),

            'tampil' => Tab::make('Sudah Tampil')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_approved', true)),

            'semua' => Tab::make('Semua'),
        ];
    }
}
