<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TemplateDemoController;
use App\Http\Controllers\WishController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Katalog
|--------------------------------------------------------------------------
*/
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');

Route::get('/demo/{template:slug}', TemplateDemoController::class)->name('template.demo');

Route::get('/order/{template:slug}', [OrderController::class, 'create'])->name('order.create');
Route::post('/order/{template:slug}', [OrderController::class, 'store'])
    ->middleware('throttle:public-orders')
    ->name('order.store');
Route::get('/order/confirmation/{order}', [OrderController::class, 'thankYou'])->name('order.thank-you');

/*
|--------------------------------------------------------------------------
| Undangan
|--------------------------------------------------------------------------
| Slug tidak dipakai sebagai route binding model karena undangan berstatus
| draft, expired, dan archived tetap harus ditemukan — masing-masing punya
| jawaban sendiri, bukan 404 seragam dari binding.
*/
Route::get('/undangan/{slug}', [InvitationController::class, 'show'])->name('invitation.show');
Route::get('/undangan/{slug}/ics', [InvitationController::class, 'ics'])->name('invitation.ics');

Route::middleware('throttle:invitation-writes')->group(function () {
    Route::post('/undangan/{slug}/rsvp', [RsvpController::class, 'store'])->name('invitation.rsvp');
    Route::post('/undangan/{slug}/wishes', [WishController::class, 'store'])->name('invitation.wishes');
});
