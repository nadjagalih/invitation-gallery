<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Nomor')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor order disalin')
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->description(fn (Order $record): string => $record->customer_phone)
                    ->searchable(['customer_name', 'customer_phone', 'customer_email'])
                    ->sortable(),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    // Rupiah disimpan sebagai integer utuh, jadi tidak ada
                    // pembagi dan tidak ada angka desimal.
                    ->money('IDR', decimalPlaces: 0)
                    ->alignRight()
                    ->sortable()
                    ->summarize(Sum::make()->label('Jumlah')->money('IDR', decimalPlaces: 0)),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('payment_channel')
                    ->label('Metode')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->dateTime('j M Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('invitation.slug')
                    ->label('Undangan')
                    ->placeholder('Belum dibuat')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Masuk')
                    ->dateTime('j M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(OrderStatus::options())
                    ->multiple(),

                SelectFilter::make('payment_channel')
                    ->label('Metode')
                    ->options(PaymentChannel::options()),

                SelectFilter::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('unfulfilled')
                    ->label('Sudah dibayar, undangan belum dibuat')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('invitation_id')
                        ->whereIn('status', [
                            OrderStatus::Paid,
                            OrderStatus::InProgress,
                        ])),
            ])
            ->recordActions([
                self::markPaid(),

                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->color('gray')
                    ->url(fn (Order $record): string => 'https://wa.me/'.preg_replace('/\D/', '', $record->customer_phone))
                    ->openUrlInNewTab(),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Jalur transfer manual: admin melihat bukti transfer lalu menandai satu
     * order lunas. Tanggal bayar hanya diisi bila belum ada, supaya menekan
     * tombol dua kali tidak menggeser catatan waktunya.
     */
    private static function markPaid(): Action
    {
        return Action::make('markPaid')
            ->label('Tandai Lunas')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Tandai order ini sudah dibayar?')
            ->visible(fn (Order $record): bool => in_array($record->status, [
                OrderStatus::Pending,
                OrderStatus::WaitingPayment,
            ], true))
            ->action(function (Order $record): void {
                $record->update([
                    'status' => OrderStatus::Paid,
                    'paid_at' => $record->paid_at ?? now(),
                ]);

                Notification::make()->success()->title('Order ditandai lunas')->send();
            });
    }
}
