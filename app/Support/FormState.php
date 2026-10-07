<?php

namespace App\Support;

use Illuminate\Support\ViewErrorBag;

/**
 * Halaman undangan memuat dua form dengan nama field yang sama (`name`,
 * `message`). Tanpa penanda lingkup, `old('name')` sisa kegagalan validasi form
 * ucapan akan ikut mengisi ulang form RSVP — tamu melihat teksnya berpindah
 * kotak, lalu terkirim ke endpoint yang salah begitu ia menekan kirim.
 *
 * Karena itu tiap form menyertakan penanda lingkupnya, dan pembacaan old input
 * hanya dilayani bila penanda pada sesi cocok dengan form yang sedang dirender.
 */
class FormState
{
    /** Nama field penanda yang disertakan setiap form publik. */
    public const FIELD = 'form';

    /** Apakah form dengan lingkup ini yang baru saja dikirim? */
    public static function submitted(string $scope): bool
    {
        return session()->getOldInput(self::FIELD) === $scope;
    }

    public static function old(string $scope, string $key, mixed $default = ''): mixed
    {
        return self::submitted($scope) ? old($key, $default) : $default;
    }

    /**
     * Apakah ada pesan atau error tertunda dari salah satu form publik?
     *
     * Dipakai untuk membuka undangan langsung setelah submit tanpa JavaScript.
     * Tanpa ini, tamu dikembalikan ke halaman sampul dan pesan hasil kiriman
     * tersembunyi di bawahnya — ia tidak tahu RSVP-nya berhasil atau gagal.
     */
    public static function hasFeedback(string ...$scopes): bool
    {
        $errors = session('errors');

        foreach ($scopes as $scope) {
            if (session()->has("{$scope}_success") || session()->has("{$scope}_error")) {
                return true;
            }

            if ($errors instanceof ViewErrorBag && $errors->getBag($scope)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }
}
