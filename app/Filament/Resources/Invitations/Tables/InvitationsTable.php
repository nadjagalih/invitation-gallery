<?php

namespace App\Filament\Resources\Invitations\Tables;

use App\Enums\AssetState;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class InvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('event_date', 'desc')
            ->columns([
                TextColumn::make('groom_nickname')
                    ->label('Pasangan')
                    ->formatStateUsing(fn (Invitation $record): string => $record->coupleNames())
                    ->description(fn (Invitation $record): string => $record->slug)
                    // Nama pasangan adalah gabungan dua kolom, jadi pencarian dan
                    // pengurutannya harus menyebut keduanya secara eksplisit.
                    ->searchable(['groom_nickname', 'bride_nickname', 'slug'])
                    ->sortable(),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->description(fn (Invitation $record): string => 'v'.$record->template_version)
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                // Label dan warna badge datang dari enumnya sendiri — keduanya
                // mengimplementasikan HasLabel dan HasColor.
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('asset_state')
                    ->label('File')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('event_date')
                    ->label('Tanggal Acara')
                    ->date('j M Y')
                    ->sortable(),

                TextColumn::make('rsvps_count')
                    ->label('RSVP')
                    ->counts('rsvps')
                    ->badge()
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('wishes_count')
                    ->label('Ucapan')
                    ->counts([
                        // Yang menuntut perhatian admin adalah yang belum
                        // disetujui, bukan totalnya.
                        'wishes' => fn (Builder $query) => $query->where('is_approved', false),
                    ])
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'warning' : 'gray')
                    ->tooltip('Ucapan menunggu moderasi')
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('expires_at')
                    ->label('Berakhir')
                    ->dateTime('j M Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('asset_delete_at')
                    ->label('File Dihapus')
                    ->dateTime('j M Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(InvitationStatus::options())
                    ->multiple(),

                SelectFilter::make('asset_state')
                    ->label('Status File')
                    ->options(AssetState::options())
                    ->multiple(),

                SelectFilter::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('needs_moderation')
                    ->label('Ada ucapan menunggu')
                    ->query(fn (Builder $query): Builder => $query->whereHas(
                        'wishes',
                        fn (Builder $wishes) => $wishes->where('is_approved', false),
                    )),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Lihat')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Invitation $record): string => $record->publicUrl())
                    ->openUrlInNewTab()
                    // Status lain tidak merender undangan; tombolnya hanya akan
                    // mengantar admin ke halaman kedaluwarsa.
                    ->visible(fn (Invitation $record): bool => $record->status->rendersInvitation()),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
