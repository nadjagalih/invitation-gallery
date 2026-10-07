<?php

namespace App\Filament\Resources\Invitations;

use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Filament\Resources\Invitations\RelationManagers\RsvpsRelationManager;
use App\Filament\Resources\Invitations\RelationManagers\WishesRelationManager;
use App\Filament\Resources\Invitations\Schemas\InvitationForm;
use App\Filament\Resources\Invitations\Tables\InvitationsTable;
use App\Models\Invitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Undangan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'undangan';

    protected static ?string $pluralModelLabel = 'undangan';

    protected static ?string $recordTitleAttribute = 'slug';

    public static function form(Schema $schema): Schema
    {
        return InvitationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvitationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RsvpsRelationManager::class,
            WishesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvitations::route('/'),
            'create' => CreateInvitation::route('/create'),
            'edit' => EditInvitation::route('/{record}/edit'),
        ];
    }

    /**
     * Admin mencari undangan dengan nama mempelai, bukan dengan slug, jadi
     * keempat kolom nama ikut dicari.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'slug',
            'groom_nickname',
            'bride_nickname',
            'groom_full_name',
            'bride_full_name',
        ];
    }

    /** @param  Invitation  $record */
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->coupleNames();
    }

    /**
     * @param  Invitation  $record
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Slug' => $record->slug,
            'Status' => $record->status->label(),
            'Acara' => $record->eventDateLabel(),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
