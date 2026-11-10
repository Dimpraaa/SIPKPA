<?php

namespace App\Providers;

use App\Models\AngkatanPkpa;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination.table');

        View::composer('components.layout', function (ViewInstance $view): void {
            $view->with('angkatanAktif', AngkatanPkpa::where('status_angkatan', 'berjalan')
                ->orderByDesc('tanggal_mulai')->orderByDesc('id')
                ->first(['id', 'nama_angkatan', 'tanggal_mulai', 'tanggal_selesai']));
        });
    }
}
