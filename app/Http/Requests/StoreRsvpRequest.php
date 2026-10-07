<?php

namespace App\Http\Requests;

use App\Enums\Attendance;
use App\Support\SpamGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRsvpRequest extends FormRequest
{
    /**
     * RSVP dan ucapan dirender pada satu halaman. Tanpa error bag terpisah,
     * kegagalan validasi salah satu form akan memerahi keduanya.
     *
     * @var string
     */
    protected $errorBag = 'rsvp';

    public function authorize(): bool
    {
        // Endpoint publik; kelayakan menulis diputuskan controller dari status
        // undangan, bukan dari identitas pengirim.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'attendance' => ['required', Rule::enum(Attendance::class)],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('invitation.rsvp_max_party_size')],
            'message' => ['nullable', 'string', 'max:1000'],
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
            'attendance' => 'konfirmasi kehadiran',
            'party_size' => 'jumlah tamu',
            'message' => 'ucapan',
        ];
    }
}
