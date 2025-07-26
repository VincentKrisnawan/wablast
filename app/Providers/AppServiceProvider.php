<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // <-- 1. Import View Facade
use Illuminate\Support\Facades\Auth; // <-- 2. Import Auth Facade
use App\Models\UploadBatch;          // <-- 3. Import UploadBatch Model
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // if ($this->app->environment('local')) {
        //     $this->app->register(\Laravel\Pail\PailServiceProvider::class);
        // }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Membuat "View Composer" untuk komponen navbar
        // Kode ini akan berjalan setiap kali view 'components.navbar' akan dirender
        View::composer('components.navbar', function ($view) {

            // Hanya jalankan jika user sudah login
            if (Auth::check()) {
                // Ambil batch upload terakhir milik user yang sedang login
                $latestBatch = UploadBatch::where('user_id', Auth::id())
                                          ->latest()
                                          ->first();

                // Kirim ID batch (atau null jika tidak ada) ke view navbar
                $view->with('latest_batch_id', $latestBatch ? $latestBatch->id : null);
            } else {
                // Jika user belum login, kirim null
                $view->with('latest_batch_id', null);
            }
        });
        Paginator::useBootstrapFive();
    }
}
