<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Enums\InvitationStatus;
use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\Invitation;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvitations extends ListRecords
{
    protected static string $resource = InvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Tab yang membawa badge dibatasi pada dua yang menuntut tindakan: undangan
     * yang masa aktifnya hampir habis dan yang sudah berakhir. Setiap badge
     * adalah satu query tambahan di tiap muat halaman, jadi tidak semua tab
     * diberi angka.
     */
    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),

            'aktif' => Tab::make('Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InvitationStatus::Active)),

            'segera_berakhir' => Tab::make('Segera Berakhir')
                ->modifyQueryUsing(fn (Builder $query) => self::expiringSoon($query))
                ->badge(fn (): ?string => self::countOrNull(self::expiringSoon(Invitation::query())))
                ->badgeColor('warning'),

            'berakhir' => Tab::make('Masa Aktif Berakhir')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InvitationStatus::Expired))
                ->badge(fn (): ?string => self::countOrNull(
                    Invitation::query()->where('status', InvitationStatus::Expired),
                ))
                ->badgeColor('danger'),

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InvitationStatus::Draft)),

            'demo' => Tab::make('Preview / Demo')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InvitationStatus::Preview)),

            'arsip' => Tab::make('Diarsipkan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InvitationStatus::Archived)),
        ];
    }

    /** Masih aktif tapi berakhir dalam dua minggu — jendela untuk menawarkan perpanjangan. */
    private static function expiringSoon(Builder $query): Builder
    {
        return $query
            ->where('status', InvitationStatus::Active)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(14)]);
    }

    /** Badge 0 tidak memberi informasi apa pun, jadi tidak ditampilkan. */
    private static function countOrNull(Builder $query): ?string
    {
        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }
}
