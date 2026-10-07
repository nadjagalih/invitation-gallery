<?php

namespace App\Filament\Resources\TemplateCategories\Pages;

use App\Filament\Resources\TemplateCategories\TemplateCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTemplateCategory extends EditRecord
{
    protected static string $resource = TemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
