<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Support\MediaAttachment;
use App\Support\InvitationPublication;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    /** Status preview dan aktif menerbitkan undangan saat record dibuat. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return InvitationPublication::prepare($data);
    }

    /**
     * Direktori asset memuat id undangan, jadi seluruh field unggah baru muncul
     * di halaman Edit. Mengantar admin ke sana adalah kelanjutan pekerjaan yang
     * wajar, bukan kembali ke daftar.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    /** Sama seperti di EditInvitation; lihat catatannya di sana. */
    protected function afterCreate(): void
    {
        MediaAttachment::pruneRowCollections($this->getRecord());
    }
}
