<?php

namespace App\Filament\Resources\Wishes\Tables;

use App\Filament\Actions\WishModeration;
use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\Wish;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WishesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_approved')
                    ->label('Tampil')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('invitation.slug')
                    ->label('Undangan')
                    ->description(fn (Wish $record): ?string => $record->invitation?->coupleNames())
                    ->searchable()
                    ->sortable()
                    // Antrean lintas undangan; tautan ke undangannya menghemat
                    // pencarian ulang saat admin perlu konteks.
                    ->url(fn (Wish $record): ?string => $record->invitation
                        ? InvitationResource::getUrl('edit', ['record' => $record->invitation])
                        : null),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('message')
                    ->label('Ucapan')
                    ->searchable()
                    ->wrap()
                    ->limit(140)
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

                SelectFilter::make('invitation_id')
                    ->label('Undangan')
                    ->relationship('invitation', 'slug')
                    ->searchable()
                    ->preload(),
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
            ->emptyStateHeading('Tidak ada ucapan')
            ->emptyStateDescription('Antrean moderasi sedang kosong.');
    }
}
