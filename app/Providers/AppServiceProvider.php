<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * RSVP dan ucapan adalah form terbuka tanpa autentikasi pada halaman yang
     * dibagikan ke ratusan orang, jadi dibatasi dari tiga sisi sekaligus:
     * per IP, per undangan, dan kombinasi keduanya pada rentang lebih panjang.
     *
     * Satu batas saja tidak cukup. Batas per IP tidak menahan serangan dari
     * banyak IP, sedangkan batas per undangan bisa dihabiskan satu IP nakal
     * sehingga tamu yang sah ikut terblokir.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('public-orders', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('invitation-writes', function (Request $request) {
            $limits = config('invitation.rate_limits');
            $slug = (string) $request->route('slug');

            return [
                Limit::perMinute($limits['per_ip_per_minute'])->by('ip:'.$request->ip()),
                Limit::perMinute($limits['per_invitation_per_minute'])->by('inv:'.$slug),
                Limit::perHour($limits['per_ip_per_invitation_per_hour'])->by('ip-inv:'.$request->ip().'|'.$slug),
            ];
        });
    }
}
