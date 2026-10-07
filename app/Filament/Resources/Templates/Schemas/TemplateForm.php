<?php

namespace App\Filament\Resources\Templates\Schemas;

use App\Enums\InvitationFeature;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\Template;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Template')
                            ->required()
                            ->maxLength(255)
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
                            // Slug me-resolve direktori view. Mengubahnya setelah
                            // ada undangan yang memakai template ini membuat
                            // seluruh undangan itu menunjuk folder yang tidak ada.
                            ->disabled(fn (?Template $record): bool => $record?->invitations()->exists() ?? false)
                            ->helperText(fn (?Template $record): string => $record?->invitations()->exists()
                                ? 'Terkunci: sudah ada undangan yang memakai template ini.'
                                : 'Menentukan folder view: resources/views/invitations/{slug}/v{n}/'),

                        Select::make('template_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),

                        TextInput::make('current_version')
                            ->label('Versi Aktif')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->helperText('Dipakai undangan baru. Undangan lama tetap pada versinya sendiri.'),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Harga')
                    ->description('Rupiah bulat tanpa sen. Harga promo tampil sebagai harga aktif dengan harga asli dicoret.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->label('Harga Normal')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->live(onBlur: true),

                        TextInput::make('promo_price')
                            ->label('Harga Promo')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            // Promo yang lebih tinggi dari harga normal akan
                            // dirender sebagai "diskon" negatif di katalog.
                            ->lte('price')
                            ->helperText('Kosongkan bila tidak sedang promo.'),

                        TagsInput::make('badges')
                            ->label('Badge')
                            ->placeholder('Tambah badge')
                            ->helperText('Label kecil pada kartu katalog, mis. "Gratis Template Story IG".')
                            ->columnSpanFull(),
                    ]),

                Section::make('Katalog')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('thumbnails')
                            ->label('Thumbnail')
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->image()
                            ->acceptedFileTypes(config('invitation.upload.image_mimes'))
                            ->maxSize((int) config('invitation.upload.image_max_kb'))
                            ->maxFiles(4)
                            // Thumbnail milik template, bukan undangan, jadi tidak
                            // ikut prefiks invitations/{id}/ dan tidak tersentuh
                            // cleanup asset.
                            ->directory('templates')
                            ->visibility('public')
                            ->panelLayout('grid')
                            ->helperText('Gambar pertama dipakai sebagai gambar utama kartu katalog.')
                            ->columnSpanFull(),

                        CheckboxList::make('supported_features')
                            ->label('Fitur yang Didukung')
                            ->options(InvitationFeature::options())
                            ->descriptions(InvitationFeature::descriptions())
                            ->columns(2)
                            ->bulkToggleable()
                            ->helperText('Undangan hanya bisa menyalakan fitur yang juga didukung templatenya.')
                            ->columnSpanFull(),

                        Select::make('demo_invitation_id')
                            ->label('Undangan Demo')
                            ->relationship(
                                'demoInvitation',
                                'slug',
                                // Demo dibuka pengunjung katalog yang tidak dikenal,
                                // jadi hanya undangan berstatus preview yang layak.
                                fn ($query) => $query->where('status', InvitationStatus::Preview),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Invitation $record): string => "{$record->coupleNames()} ({$record->slug})",
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Tujuan tombol "Lihat" di katalog. Hanya undangan berstatus Preview.'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Template tidak aktif tetap merender undangan yang sudah ada, hanya hilang dari katalog.'),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ]),
            ]);
    }
}
