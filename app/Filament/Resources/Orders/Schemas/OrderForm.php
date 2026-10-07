<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\InvitationFeature;
use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Models\Template;
use App\Support\OrderAddons;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::customerSection(),
                self::productSection(),
                self::paymentSection(),
            ]);
    }

    private static function customerSection(): Section
    {
        return Section::make('Pelanggan')
            ->columns(2)
            ->schema([
                TextInput::make('order_number')
                    ->label('Nomor Order')
                    // Dibuat server saat order tersimpan; di sini hanya dibaca.
                    ->visibleOn('edit')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Dibuat otomatis dan tidak diubah.'),

                Select::make('user_id')
                    ->label('Akun Pelanggan')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Belum punya akun')
                    ->helperText('Boleh kosong untuk order yang masuk lewat WhatsApp.'),

                TextInput::make('customer_name')
                    ->label('Nama Pelanggan')
                    ->required()
                    ->maxLength(255),

                TextInput::make('customer_phone')
                    ->label('Nomor WhatsApp')
                    ->tel()
                    ->required()
                    ->maxLength(32)
                    ->helperText('Format 62… supaya tautan WhatsApp langsung bisa dipakai.'),

                TextInput::make('customer_email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    private static function productSection(): Section
    {
        return Section::make('Produk & Harga')
            ->columns(2)
            ->schema([
                Select::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    // Harga yang berlaku saat order dibuat disalin ke order, lalu
                    // berhenti mengikuti daftar harga. Perubahan harga template
                    // di kemudian hari tidak boleh mengubah tagihan lama.
                    ->afterStateUpdated(function (?string $state, Set $set, string $operation): void {
                        if ($operation !== 'create' || blank($state)) {
                            return;
                        }

                        $template = Template::query()->whereKey($state)->first();

                        if ($template) {
                            $set('base_price', $template->effectivePrice());
                        }
                    }),

                Select::make('invitation_id')
                    ->label('Undangan')
                    ->relationship('invitation', 'slug')
                    ->searchable()
                    ->preload()
                    ->placeholder('Belum dibuat')
                    ->helperText('Diisi setelah undangan untuk order ini dibuat.'),

                TextInput::make('base_price')
                    ->label('Harga Paket')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('Rp')
                    ->live(onBlur: true),

                TextInput::make('discount')
                    ->label('Diskon')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->prefix('Rp')
                    ->live(onBlur: true),

                Repeater::make('addons')
                    ->label('Add-on')
                    ->addActionLabel('Tambah add-on')
                    ->defaultItems(0)
                    ->columns(2)
                    ->columnSpanFull()
                    ->live()
                    ->schema([
                        Select::make(OrderAddons::FEATURE)
                            ->label('Fitur')
                            ->options(InvitationFeature::addOns())
                            ->required()
                            // Kunci peta yang tersimpan adalah nama fitur, jadi
                            // satu fitur tidak boleh muncul dua kali.
                            ->distinct(),

                        TextInput::make(OrderAddons::PRICE)
                            ->label('Harga')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->prefix('Rp'),
                    ])
                    ->helperText('Fitur di luar paket dasar. Kunci add-on inilah yang membuka fitur pada undangan.'),

                // Bukan field: totalnya dihitung server saat menyimpan, dan yang
                // ada di sini hanya cermin yang ikut berubah saat harga diubah.
                // Namanya sengaja bukan `total` supaya tidak ikut terdehidrasi
                // menimpa kolomnya.
                TextEntry::make('total_preview')
                    ->label('Total Tagihan')
                    ->state(fn (Get $get): int => self::total($get))
                    ->money('IDR', decimalPlaces: 0)
                    ->weight(FontWeight::Bold)
                    ->columnSpanFull()
                    ->helperText('Harga paket + add-on − diskon.'),
            ]);
    }

    private static function paymentSection(): Section
    {
        return Section::make('Pembayaran')
            ->columns(2)
            ->schema([
                Select::make('status')
                    ->label('Status')
                    ->options(OrderStatus::options())
                    ->default(OrderStatus::Pending->value)
                    ->required()
                    ->native(false),

                Select::make('payment_channel')
                    ->label('Metode')
                    ->options(PaymentChannel::options())
                    ->default(PaymentChannel::ManualTransfer->value)
                    ->required()
                    ->native(false),

                TextInput::make('payment_ref')
                    ->label('Referensi Pembayaran')
                    ->maxLength(255)
                    ->helperText('Order id Midtrans, atau catatan transfer manual.'),

                DateTimePicker::make('paid_at')
                    ->label('Dibayar Pada')
                    ->seconds(false)
                    ->helperText('Terisi otomatis saat status diubah menjadi sudah dibayar.'),

                FileUpload::make('proof_path')
                    ->label('Bukti Transfer')
                    // Bukti transfer memuat data rekening pengirim, jadi disimpan
                    // di disk privat dan tidak pernah ikut disk publik undangan.
                    ->disk('local')
                    ->directory('order-proofs')
                    ->visibility('private')
                    ->image()
                    ->imageEditor()
                    ->maxSize((int) config('invitation.upload.image_max_kb'))
                    ->acceptedFileTypes([...config('invitation.upload.image_mimes'), 'application/pdf'])
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Cermin dari perhitungan yang dikerjakan PreparesOrderData saat menyimpan.
     * Keduanya menjumlahkan add-on lewat OrderAddons, jadi angka yang dilihat
     * admin dan angka yang tersimpan berasal dari aturan yang sama.
     */
    private static function total(Get $get): int
    {
        return max(0, self::amount($get, 'base_price') + OrderAddons::sum($get('addons')) - self::amount($get, 'discount'));
    }

    private static function amount(Get $get, string $field): int
    {
        $value = $get($field);

        return is_numeric($value) ? (int) $value : 0;
    }
}
