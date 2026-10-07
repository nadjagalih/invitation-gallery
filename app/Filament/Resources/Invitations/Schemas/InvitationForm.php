<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Enums\AssetState;
use App\Enums\InvitationFeature;
use App\Enums\InvitationStatus;
use App\Enums\MediaCollection;
use App\Filament\Forms\Components\MediaRowUpload;
use App\Filament\Forms\Components\MediaUpload;
use App\Filament\Forms\StateCasts\FeatureMapStateCast;
use App\Models\Invitation;
use App\Models\Template;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class InvitationForm
{
    /**
     * Indonesia punya tiga zona waktu. Countdown atau file kalender yang salah
     * satu jam akan terlihat langsung oleh ratusan tamu.
     */
    private const TIMEZONES = [
        'Asia/Jakarta' => 'WIB — Waktu Indonesia Barat',
        'Asia/Makassar' => 'WITA — Waktu Indonesia Tengah',
        'Asia/Jayapura' => 'WIT — Waktu Indonesia Timur',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Undangan')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        self::invitationTab(),
                        self::coupleTab(),
                        self::eventsTab(),
                        self::storiesTab(),
                        self::giftTab(),
                        self::wordsTab(),
                        self::mediaTab(),
                        self::lifecycleTab(),
                    ]),
            ]);
    }

    private static function invitationTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Undangan')
            ->icon(Heroicon::OutlinedEnvelope)
            ->columns(2)
            ->schema([
                Select::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                        if ($operation !== 'create') {
                            return;
                        }

                        $set('template_version', Template::query()->whereKey($state)->value('current_version') ?? 1);
                    }),

                Select::make('template_version')
                    ->label('Versi Template')
                    ->required()
                    // Versi dibekukan per undangan: template boleh terus
                    // berkembang tanpa mengubah undangan yang sudah tersebar.
                    // Pilihannya dibatasi versi yang benar-benar ada agar
                    // viewName() tidak pernah menunjuk folder kosong.
                    ->options(fn (Get $get): array => self::versionOptions($get('template_id')))
                    ->default(1)
                    ->helperText('Undangan tetap dirender oleh versi ini walau template sudah naik versi.'),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    // Setelah link tersebar ke ratusan tamu slug tidak bisa
                    // ditarik kembali, jadi publikasi membekukannya.
                    ->disabled(fn (?Invitation $record): bool => $record?->isSlugLocked() ?? false)
                    ->helperText(fn (?Invitation $record): string => $record?->isSlugLocked()
                        ? 'Terkunci sejak publikasi. Mengubahnya akan mematikan link yang sudah dibagikan.'
                        : 'Alamat publik: /undangan/{slug}')
                    ->suffixAction(
                        Action::make('generateSlug')
                            ->label('Buat dari nama mempelai')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->visible(fn (?Invitation $record): bool => ! ($record?->isSlugLocked() ?? false))
                            ->action(function (Get $get, Set $set, ?Invitation $record): void {
                                $set('slug', Invitation::generateSlug(
                                    (string) $get('groom_nickname'),
                                    (string) $get('bride_nickname'),
                                    $record?->getKey(),
                                ));
                            }),
                    ),

                Select::make('user_id')
                    ->label('Pemilik')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Belum dipakai di MVP; disiapkan untuk dashboard klien.'),

                Select::make('status')
                    ->label('Status')
                    ->options(InvitationStatus::options())
                    ->required()
                    ->default(InvitationStatus::Draft->value)
                    ->helperText('Preview dan Aktif langsung menerbitkan undangan. Hanya Aktif yang menerima RSVP dan ucapan.'),

                Select::make('asset_state')
                    ->label('Status File')
                    ->options(AssetState::options())
                    ->required()
                    ->default(AssetState::Retained->value)
                    ->helperText('Biasanya diatur scheduler. Ubah manual hanya untuk memperbaiki keadaan.'),

                Select::make('asset_disk')
                    ->label('Disk Upload')
                    ->options(fn (?Invitation $record): array => self::diskOptions($record))
                    ->required()
                    ->default(config('invitation.asset_disk'))
                    ->helperText('Asset undangan harus publik. Berkas lama tetap dilayani dari disk yang tercatat pada masing-masing file.'),

                Toggle::make('moderate_wishes')
                    ->label('Moderasi Ucapan')
                    ->default(true)
                    ->helperText('Bila menyala, ucapan baru menunggu persetujuan sebelum tampil.'),

                CheckboxList::make('features')
                    ->label('Fitur Aktif')
                    ->stateCast(new FeatureMapStateCast)
                    ->options(InvitationFeature::options())
                    ->descriptions(InvitationFeature::descriptions())
                    ->columns(2)
                    ->bulkToggleable()
                    ->default(array_keys(array_filter(InvitationFeature::defaults())))
                    // Fitur hanya benar-benar tampil bila templatenya juga
                    // mendukung; hasFeature() menuntut keduanya setuju. Yang
                    // tidak didukung dimatikan di sini supaya admin tidak
                    // menyalakan sesuatu yang tidak akan pernah muncul.
                    ->disableOptionWhen(self::unsupportedByTemplate())
                    ->helperText('Pilihan yang tidak bisa dicentang berarti template terpilih tidak mendukungnya.')
                    ->columnSpanFull(),
            ]);
    }

    private static function coupleTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Mempelai')
            ->icon(Heroicon::OutlinedHeart)
            ->schema([
                self::coupleFieldset('Mempelai Pria', 'groom'),
                self::coupleFieldset('Mempelai Wanita', 'bride'),
            ]);
    }

    private static function coupleFieldset(string $label, string $prefix): Fieldset
    {
        $noun = $prefix === 'groom' ? 'Putra' : 'Putri';

        return Fieldset::make($label)
            ->columns(2)
            ->schema([
                TextInput::make("{$prefix}_nickname")
                    ->label('Nama Panggilan')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Dipakai di cover, judul, dan slug.'),

                TextInput::make("{$prefix}_full_name")
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),

                TextInput::make("{$prefix}_child_order")
                    ->label('Anak ke-')
                    ->maxLength(60)
                    ->placeholder('Pertama')
                    ->helperText("Dicetak sebagai \"{$noun} Pertama dari …\". Kosongkan bila tidak perlu."),

                TextInput::make("{$prefix}_instagram")
                    ->label('Instagram')
                    ->maxLength(255)
                    ->placeholder('@namapengguna')
                    ->helperText('Boleh @nama, nama saja, atau tempelan URL penuh.'),

                TextInput::make("{$prefix}_father")
                    ->label('Nama Ayah')
                    ->required()
                    ->maxLength(255),

                TextInput::make("{$prefix}_mother")
                    ->label('Nama Ibu')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    private static function eventsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Acara')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->schema([
                DatePicker::make('event_date')
                    ->label('Tanggal Utama')
                    ->required()
                    ->native(false)
                    ->displayFormat('l, j F Y')
                    ->helperText('Dipakai cover, urutan di daftar admin, dan penghitungan masa aktif.'),

                Repeater::make('events')
                    ->label('Rangkaian Acara')
                    ->relationship()
                    ->orderColumn('sort_order')
                    ->columns(2)
                    ->collapsible()
                    ->cloneable()
                    ->defaultItems(1)
                    ->addActionLabel('Tambah acara')
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->schema([
                        TextInput::make('title')
                            ->label('Nama Acara')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Akad Nikah'),

                        // Diletakkan sebelum kedua picker karena keduanya
                        // menampilkan jam dinding menurut zona ini.
                        Select::make('timezone')
                            ->label('Zona Waktu')
                            ->options(self::TIMEZONES)
                            ->required()
                            ->selectablePlaceholder(false)
                            ->default('Asia/Jakarta'),

                        DateTimePicker::make('starts_at')
                            ->label('Mulai')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('j F Y, H:i')
                            ->timezone(fn (Get $get): string => self::timezoneOf($get))
                            ->helperText('Jam dinding di zona waktu acara.'),

                        DateTimePicker::make('ends_at')
                            ->label('Selesai')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('j F Y, H:i')
                            ->timezone(fn (Get $get): string => self::timezoneOf($get))
                            ->after('starts_at')
                            ->helperText('Kosongkan bila tidak dibatasi; file kalender memakai durasi default.'),

                        TextInput::make('venue_name')
                            ->label('Nama Tempat')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('maps_url')
                            ->label('Tautan Google Maps')
                            ->url()
                            ->maxLength(2048),

                        Textarea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->placeholder('Mohon hadir 15 menit lebih awal.')
                            ->columnSpanFull(),

                        Toggle::make('is_primary')
                            ->label('Acara Utama')
                            ->helperText('Target countdown dan Save The Date. Bila lebih dari satu ditandai, yang teratas dipakai.')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function storiesTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Cerita')
            ->icon(Heroicon::OutlinedBookOpen)
            ->schema([
                MediaRowUpload::attachTo(
                    Repeater::make('stories')
                        ->label('Love Story')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->columns(2)
                        ->collapsible()
                        ->collapsed()
                        ->defaultItems(0)
                        ->addActionLabel('Tambah babak cerita')
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->schema([
                            TextInput::make('title')
                                ->label('Judul')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Pertemuan Pertama'),

                            DatePicker::make('happened_on')
                                ->label('Tanggal')
                                ->native(false)
                                ->displayFormat('j F Y')
                                ->helperText('Kosongkan bila tidak ingin menampilkan tanggal.'),

                            Textarea::make('body')
                                ->label('Cerita')
                                ->required()
                                ->rows(4)
                                ->columnSpanFull(),

                            MediaUpload::rowImage(MediaCollection::Story, 'Foto')
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                    MediaCollection::Story,
                    'media_id',
                ),
            ]);
    }

    private static function giftTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Kado')
            ->icon(Heroicon::OutlinedGift)
            ->schema([
                Textarea::make('gift_address')
                    ->label('Alamat Kirim Kado')
                    ->rows(3)
                    ->helperText('Tampil dengan tombol salin. Kosongkan bila hanya menerima transfer.'),

                MediaRowUpload::attachTo(
                    Repeater::make('bankAccounts')
                        ->label('Rekening')
                        ->relationship()
                        ->orderColumn('sort_order')
                        ->columns(3)
                        ->collapsible()
                        ->defaultItems(0)
                        ->addActionLabel('Tambah rekening')
                        ->itemLabel(fn (array $state): ?string => $state['bank_name'] ?? null)
                        ->schema([
                            TextInput::make('bank_name')
                                ->label('Bank')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('BCA'),

                            TextInput::make('account_number')
                                ->label('Nomor Rekening')
                                ->required()
                                ->maxLength(64)
                                // Disimpan sebagai teks: nol di depan harus utuh,
                                // dan nomor rekening bukan bilangan yang dihitung.
                                ->helperText('Nol di depan tidak akan hilang.'),

                            TextInput::make('account_holder')
                                ->label('Atas Nama')
                                ->required()
                                ->maxLength(255),

                            MediaUpload::rowImage(MediaCollection::BankLogo, 'Logo Bank')
                                ->helperText('Opsional.')
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                    MediaCollection::BankLogo,
                    'logo_media_id',
                ),
            ]);
    }

    private static function wordsTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Teks')
            ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
            ->columns(2)
            ->schema([
                Textarea::make('quote_text')
                    ->label('Kutipan')
                    ->rows(4)
                    ->placeholder('Dan di antara tanda-tanda kekuasaan-Nya…'),

                TextInput::make('quote_source')
                    ->label('Sumber Kutipan')
                    ->maxLength(255)
                    ->placeholder('QS. Ar-Rum: 21'),

                Textarea::make('opening_words')
                    ->label('Kata Pembuka')
                    ->rows(3),

                Textarea::make('closing_words')
                    ->label('Kata Penutup')
                    ->rows(3),

                Fieldset::make('Preview WhatsApp')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        Text::make('Kosongkan untuk memakai judul dan deskripsi otomatis dari nama pasangan serta tanggal acara. Nama tamu dari ?to= tetap ditambahkan di belakang judul.')
                            ->color('gray'),

                        TextInput::make('meta.og_title')
                            ->label('Judul OG')
                            ->maxLength(255),

                        Textarea::make('meta.og_description')
                            ->label('Deskripsi OG')
                            ->rows(2)
                            ->maxLength(500),
                    ]),
            ]);
    }

    private static function mediaTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Media')
            ->icon(Heroicon::OutlinedPhoto)
            ->columns(2)
            ->schema([
                // Berkas disimpan ke direktori berisi id undangan, dan id itu
                // baru ada setelah simpan pertama.
                Text::make('Simpan undangan ini lebih dulu. Berkas disimpan ke direktori yang memuat id undangan, jadi unggahan baru bisa dilakukan setelah undangan tersimpan.')
                    ->color('warning')
                    ->visibleOn('create')
                    ->columnSpanFull(),

                MediaUpload::image(MediaCollection::Cover),
                MediaUpload::image(MediaCollection::OgImage)
                    ->helperText('Gambar preview WhatsApp. Bila kosong, cover yang dipakai.'),
                MediaUpload::image(MediaCollection::GroomPhoto),
                MediaUpload::image(MediaCollection::BridePhoto),
                MediaUpload::gallery()->columnSpanFull(),
                MediaUpload::music()->columnSpanFull(),
            ]);
    }

    private static function lifecycleTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Masa Aktif')
            ->icon(Heroicon::OutlinedClock)
            ->columns(2)
            ->schema([
                DateTimePicker::make('published_at')
                    ->label('Dipublikasikan')
                    ->native(false)
                    ->seconds(false)
                    ->helperText('Terisi otomatis saat status menjadi Aktif; saat itu pula slug dibekukan.'),

                DateTimePicker::make('expires_at')
                    ->label('Masa Aktif Berakhir')
                    ->native(false)
                    ->seconds(false)
                    ->helperText(sprintf('Default %d hari setelah tanggal acara.', (int) config('invitation.active_days'))),

                TextInput::make('grace_days')
                    ->label('Masa Tenggang (hari)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(365)
                    ->default((int) config('invitation.grace_days'))
                    ->helperText('Jeda antara berakhirnya masa aktif dan penghapusan berkas. Klien masih bisa memperpanjang.'),

                DateTimePicker::make('asset_delete_at')
                    ->label('Berkas Dihapus Pada')
                    ->native(false)
                    ->seconds(false)
                    ->helperText('Setelah tanggal ini seluruh berkas undangan dihapus permanen oleh scheduler.'),

                DateTimePicker::make('expiry_notified_at')
                    ->label('Peringatan Terakhir Dikirim')
                    ->native(false)
                    ->seconds(false)
                    ->helperText('Kosongkan untuk membuat scheduler mengirim peringatan sekali lagi.')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Zona waktu acara dibaca dari baris repeater yang sama. Nilai kosong hanya
     * terjadi sesaat sebelum default terpasang.
     */
    private static function timezoneOf(Get $get): string
    {
        $timezone = $get('timezone');

        return is_string($timezone) && array_key_exists($timezone, self::TIMEZONES)
            ? $timezone
            : 'Asia/Jakarta';
    }

    /**
     * Predikat untuk mematikan fitur yang tidak didukung template terpilih.
     * Dipanggil sekali per opsi, jadi hasil query ditahan dalam closure —
     * tanpa itu satu render berarti delapan query yang sama.
     */
    private static function unsupportedByTemplate(): Closure
    {
        $cache = [];

        return function (string $value, Get $get) use (&$cache): bool {
            $key = (string) $get('template_id');

            $cache[$key] ??= Template::query()->whereKey($key)->first()?->supported_features ?? [];

            return ! in_array($value, $cache[$key], true);
        };
    }

    /** @return array<int, string> */
    private static function versionOptions(mixed $templateId): array
    {
        $current = (int) (Template::query()->whereKey($templateId)->value('current_version') ?? 1);

        return array_reduce(
            range(1, max($current, 1)),
            fn (array $carry, int $version) => $carry + [$version => "v{$version}"],
            [],
        );
    }

    /** @return array<string, string> */
    private static function diskOptions(?Invitation $record = null): array
    {
        $options = ['public' => 'public'];

        // Record lama mungkin masih memiliki asset di local. Tetap tampilkan
        // disk tersebut saat edit supaya admin tidak tanpa sengaja memutus URL
        // asset lama; record baru tidak boleh memilih disk private/cloud.
        if ($record?->asset_disk && ! array_key_exists($record->asset_disk, $options)) {
            $options[$record->asset_disk] = $record->asset_disk.' (asset lama)';
        }

        return $options;
    }
}
