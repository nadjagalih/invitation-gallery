<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Pemesanan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'order';

    protected static ?string $pluralModelLabel = 'order';

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'order_number',
            'customer_name',
            'customer_phone',
            'customer_email',
        ];
    }

    /** @param  Order  $record */
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->order_number.' — '.$record->customer_name;
    }

    /**
     * @param  Order  $record
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Status' => $record->status->label(),
            'Total' => 'Rp '.number_format($record->total, 0, ',', '.'),
        ];
    }

    /** Order yang belum diputuskan; itulah yang perlu dilihat admin lebih dulu. */
    public static function getNavigationBadge(): ?string
    {
        $waiting = Order::query()
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::WaitingPayment])
            ->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Order menunggu pembayaran';
    }
}
