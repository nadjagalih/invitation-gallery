<?php

namespace App\Filament\Resources\Invitations\RelationManagers;

use App\Enums\Attendance;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Daftar baca. RSVP hanya lahir dari form tamu di halaman undangan, jadi tidak
 * ada tombol tambah maupun ubah di sini — admin hanya membaca dan, kalau ada
 * kiriman sampah, menghapus.
 */
class RsvpsRelationManager extends RelationManager
{
    protected static string $relationship = 'rsvps';

    protected static ?string $title = 'RSVP';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Tamu')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attendance')
                    ->label('Kehadiran')
                    ->badge()
                    ->sortable(),

                TextColumn::make('party_size')
                    ->label('Jumlah Orang')
                    ->alignRight()
                    ->sortable()
                    // Yang dibutuhkan mempelai untuk menghitung kursi adalah
                    // totalnya, bukan angka per baris.
                    ->summarize(Sum::make()->label('Total')),

                TextColumn::make('message')
                    ->label('Pesan')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(),

                TextColumn::make('guest.name')
                    ->label('Dari Tautan')
                    ->placeholder('—')
                    ->description(fn ($record): ?string => $record->guest?->slug)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime('j M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('attendance')
                    ->label('Kehadiran')
                    ->options(Attendance::options())
                    ->multiple(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedEnvelopeOpen)
            ->emptyStateHeading('Belum ada RSVP')
            ->emptyStateDescription('Konfirmasi kehadiran dari tamu akan muncul di sini.');
    }
}
