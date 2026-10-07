<?php

namespace App\Filament\Resources\TemplateCategories\Pages;

use App\Filament\Resources\TemplateCategories\TemplateCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTemplateCategories extends ListRecords
{
    protected static string $resource = TemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
