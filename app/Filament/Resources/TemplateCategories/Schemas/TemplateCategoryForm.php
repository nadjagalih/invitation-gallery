<?php

namespace App\Filament\Resources\TemplateCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TemplateCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255)
                    // Slug diusulkan dari nama, tetapi tetap bisa disunting: ia
                    // muncul di URL filter katalog dan tidak selalu ingin
                    // mengikuti perubahan nama.
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Dipakai pada URL filter katalog.'),

                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Angka lebih kecil tampil lebih dulu.'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Kategori tidak aktif disembunyikan dari katalog publik.'),
            ]);
    }
}
