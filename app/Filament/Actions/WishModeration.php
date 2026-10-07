<?php

namespace App\Filament\Actions;

use App\Models\Wish;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aksi moderasi ucapan, dipakai bersama oleh relation manager di halaman
 * undangan dan WishResource yang menjadi antrean lintas undangan. Keduanya
 * memoderasi hal yang sama, jadi tombolnya tidak ditulis dua kali.
 */
final class WishModeration
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Setujui')
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->visible(fn (Wish $record): bool => ! $record->is_approved)
            ->action(function (Wish $record): void {
                $record->update(['is_approved' => true]);

                Notification::make()->success()->title('Ucapan ditampilkan')->send();
            });
    }

    public static function unapprove(): Action
    {
        return Action::make('unapprove')
            ->label('Sembunyikan')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('warning')
            ->visible(fn (Wish $record): bool => $record->is_approved)
            // Menyembunyikan ucapan yang sudah tampil terlihat oleh tamu, jadi
            // dikonfirmasi dulu.
            ->requiresConfirmation()
            ->modalHeading('Sembunyikan ucapan ini?')
            ->modalDescription('Ucapan akan hilang dari halaman undangan, tapi isinya tetap tersimpan.')
            ->action(function (Wish $record): void {
                $record->update(['is_approved' => false]);

                Notification::make()->success()->title('Ucapan disembunyikan')->send();
            });
    }

    public static function approveBulk(): BulkAction
    {
        return self::bulk('approveSelected', 'Setujui terpilih', Heroicon::OutlinedCheck, 'success', true);
    }

    public static function unapproveBulk(): BulkAction
    {
        return self::bulk('unapproveSelected', 'Sembunyikan terpilih', Heroicon::OutlinedEyeSlash, 'warning', false);
    }

    private static function bulk(string $name, string $label, Heroicon $icon, string $color, bool $approved): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($approved): void {
                // Satu query untuk seluruh pilihan. Wish tidak punya observer,
                // jadi tidak ada efek samping yang hilang karena tidak
                // memuat modelnya satu per satu.
                $affected = Wish::query()
                    ->whereKey($records->modelKeys())
                    ->where('is_approved', ! $approved)
                    ->update(['is_approved' => $approved]);

                Notification::make()
                    ->success()
                    ->title($affected.' ucapan diperbarui')
                    ->send();
            });
    }
}
