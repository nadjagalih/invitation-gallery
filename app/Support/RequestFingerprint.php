<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * IP tamu tidak disimpan mentah. Yang dibutuhkan moderasi hanyalah kemampuan
 * mengenali dua kiriman dari sumber yang sama — bukan alamatnya. Hash memakai
 * APP_KEY sebagai kunci HMAC sehingga tidak bisa dibalik dengan rainbow table
 * atas ruang alamat IPv4 yang kecil.
 */
final class RequestFingerprint
{
    public static function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        if (! $ip) {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    /** Dipotong 512 karakter mengikuti lebar kolom. */
    public static function userAgent(Request $request): ?string
    {
        $agent = $request->userAgent();

        return $agent ? mb_substr($agent, 0, 512) : null;
    }
}
