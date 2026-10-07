<?php

namespace App\Filament\Resources\Invitations\RelationManagers;

use App\Filament\Actions\WishModeration;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Daftar baca dengan aksi moderasi. Ucapan hanya lahir dari form tamu, jadi
 * yang tersedia di sini adalah menyetujui, menyembunyikan, dan menghapus.
 */
class WishesRelationManager extends RelationManager
{
    protected static string $relationship = 'wishes';

    protected static ?string $title = 'Ucapan';

    protected static ?string $recordTitleAttribute = 'name';

    /** Jumlah yang menunggu moderasi, supaya tabnya sendiri yang memanggil admin. */
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $pending = $ownerRecord->wishes()->where('is_approved', false)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            // Yang menunggu moderasi didahulukan, lalu yang terbaru.
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_approved')
                    ->label('Tampil')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('message')
                    ->label('Ucapan')
                    ->searchable()
                    ->wrap()
                    ->limit(120)
                    ->tooltip(fn (?string $state): ?string => $state),

                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime('j M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_approved')
                    ->label('Status moderasi')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah tampil')
                    ->falseLabel('Menunggu moderasi'),
            ])
            ->recordActions([
                WishModeration::approve(),
                WishModeration::unapprove(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    WishModeration::approveBulk(),
                    WishModeration::unapproveBulk(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::OutlinedChatBubbleLeftRight)
            ->emptyStateHeading('Belum ada ucapan')
            ->emptyStateDescription('Ucapan dan doa dari tamu akan muncul di sini.');
    }
}
