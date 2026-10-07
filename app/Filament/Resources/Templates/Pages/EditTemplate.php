<?php

namespace App\Filament\Resources\Templates\Pages;

use App\Filament\Resources\Templates\TemplateResource;
use App\Models\Template;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTemplate extends EditRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Field slug memang dinonaktifkan di form, tetapi status disabled itu hidup
     * di sisi browser dan bisa dilepas. Slug menentukan direktori view, jadi
     * penolakannya harus ada juga di server.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Template $template */
        $template = $this->getRecord();

        if ($template->invitations()->exists()) {
            unset($data['slug']);
        }

        return $data;
    }
}
