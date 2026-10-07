<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\Concerns\PreparesOrderData;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    use PreparesOrderData;

    protected static string $resource = OrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->withOrderNumber($this->prepareOrderData($data));
    }
}
