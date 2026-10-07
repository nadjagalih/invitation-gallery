<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\Concerns\PreparesOrderData;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditOrder extends EditRecord
{
    use PreparesOrderData;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openInvitation')
                ->label('Buka Undangan')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->url(fn (Order $record): ?string => $record->invitation
                    ? InvitationResource::getUrl('edit', ['record' => $record->invitation])
                    : null)
                ->visible(fn (Order $record): bool => $record->invitation !== null),

            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Nomor order tidak ada di antara data yang dikirim form, jadi tidak ada
        // yang perlu dilindungi di sini — cukup turunan yang dihitung ulang.
        return $this->prepareOrderData($data);
    }
}
