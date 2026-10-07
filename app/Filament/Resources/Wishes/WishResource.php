<?php

namespace App\Filament\Resources\Wishes;

use App\Filament\Resources\Wishes\Pages\ListWishes;
use App\Filament\Resources\Wishes\Tables\WishesTable;
use App\Models\Wish;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Antrean moderasi lintas undangan. Relation manager di halaman undangan
 * mengurus satu undangan; resource ini ada supaya admin yang memegang puluhan
 * undangan tidak perlu membuka satu per satu untuk mencari ucapan yang
 * menunggu.
 *
 * Ucapan hanya lahir dari form tamu, jadi resource ini tidak punya halaman
 * tambah maupun ubah — hanya daftar dengan aksi moderasi.
 */
class WishResource extends Resource
{
    protected static ?string $model = Wish::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Undangan';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'ucapan';

    protected static ?string $pluralModelLabel = 'ucapan';

    protected static ?string $navigationLabel = 'Moderasi Ucapan';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return WishesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWishes::route('/'),
        ];
    }

    /** Tidak ada halaman tambah, dan ucapan tidak boleh dibuat dari panel. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** @param  ?Wish  $record */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** Angka di sidebar adalah alasan resource ini ada: yang menunggu moderasi. */
    public static function getNavigationBadge(): ?string
    {
        $pending = Wish::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Ucapan menunggu moderasi';
    }
}
