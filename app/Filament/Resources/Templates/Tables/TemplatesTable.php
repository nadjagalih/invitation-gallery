<?php

namespace App\Filament\Resources\Templates\Tables;

use App\Models\Template;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('thumbnails')
                    ->label('Thumbnail')
                    ->disk(config('filesystems.default'))
                    ->imageWidth(48)
                    ->imageHeight(64)
                    // Kolom menyimpan seluruh thumbnail; yang mewakili kartu
                    // katalog hanya yang pertama.
                    ->limit(1),

                TextColumn::make('name')
                    ->label('Nama')
                    ->description(fn (Template $record): string => $record->slug)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),

                TextColumn::make('current_version')
                    ->label('Versi')
                    ->prefix('v')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga')
                    // Rupiah tidak punya pecahan sen; tanpa decimalPlaces intl
                    // mencetak "Rp 350.000,00".
                    ->money('IDR', decimalPlaces: 0)
                    ->sortable(),

                TextColumn::make('promo_price')
                    ->label('Promo')
                    ->money('IDR', decimalPlaces: 0)
                    ->description(fn (Template $record): ?string => $record->hasPromo()
                        ? "-{$record->discountPercent()}%"
                        : null)
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('invitations_count')
                    ->label('Undangan')
                    ->counts('invitations')
                    ->badge()
                    ->alignRight(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('template_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                TernaryFilter::make('promo_price')
                    ->label('Sedang Promo')
                    ->nullable()
                    ->trueLabel('Ada harga promo')
                    ->falseLabel('Tanpa promo'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
