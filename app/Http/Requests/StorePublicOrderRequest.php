<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:255'],
            'customer_phone' => ['required', 'string', 'min:8', 'max:32'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'website_url' => ['nullable', 'string', 'max:0'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'customer_name' => 'nama',
            'customer_phone' => 'nomor WhatsApp',
            'customer_email' => 'email',
            'website_url' => 'website',
        ];
    }
}
