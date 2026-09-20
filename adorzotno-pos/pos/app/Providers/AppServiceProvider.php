<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Setting;
use App\Support\BranchContext;
use App\Support\PermissionCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        date_default_timezone_set(config('app.timezone'));
        Carbon::setLocale(config('app.locale'));

        if (Schema::hasTable('permissions')) {
            Permission::query()->upsert(
                PermissionCatalog::definitions(),
                ['slug'],
                ['module', 'action', 'description']
            );
        }

        View::composer('*', function ($view) {
            $settings = Setting::first();
            $branchContext = app(BranchContext::class);
            $currentBranch = auth()->check() ? $branchContext->currentBranch(auth()->user()) : null;
            $allowedBranches = auth()->check() ? $branchContext->accessibleBranches(auth()->user()) : collect();

            $view->with([
                'settings' => $settings,
                'currentBranch' => $currentBranch,
                'allowedBranches' => $allowedBranches,
                'hasCrossBranchAccess' => auth()->check() ? $branchContext->hasCrossBranchAccess(auth()->user()) : false,
            ]);
        });
    }
}
