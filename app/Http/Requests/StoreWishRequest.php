<?php

namespace App\Http\Requests;

use App\Support\SpamGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWishRequest extends FormRequest
{
    /** @var string */
    protected $errorBag = 'wishes';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'message' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            fn (Validator $validator) => SpamGuard::validate($validator),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'message' => 'ucapan',
        ];
    }
}
