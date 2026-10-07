<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\Invitation;
use App\Support\MediaAttachment;
use App\Support\InvitationPublication;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditInvitation extends EditRecord
{
    protected static string $resource = InvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Lihat Undangan')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Invitation $record): string => $record->publicUrl())
                ->openUrlInNewTab()
                ->visible(fn (Invitation $record): bool => $record->status->rendersInvitation()),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Kembaran sisi server dari field slug yang dikunci. Filament sendiri
     * mengingatkan bahwa `disabled()` bisa ditembus dari sisi klien, dan slug
     * yang sudah tersebar ke tamu tidak boleh berubah karena satu request
     * palsu.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        $data = InvitationPublication::prepare($data, $record);

        if ($record->isSlugLocked()) {
            unset($data['slug']);
            unset($data['template_id'], $data['template_version']);
        }

        return $data;
    }

    /**
     * Foto cerita dan logo bank ditukar menjadi id baris media oleh mutator
     * repeater, yang berjalan sebelum baris lain tentu tersimpan. Di titik ini
     * seluruh repeater sudah selesai, jadi daftar penunjuknya lengkap dan baris
     * media yang tidak ditunjuk siapa pun aman dibuang.
     */
    protected function afterSave(): void
    {
        MediaAttachment::pruneRowCollections($this->getRecord());
    }
}
