<?php

namespace App\Providers;

use App\Nova\Dashboards\Main;
use App\Nova\Metrics\FilesPerDay;
use App\Nova\Metrics\NewFiles;
use App\Nova\Metrics\NewUsers;
use App\Nova\Metrics\UsersPerDay;
use App\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Marianvlad\NovaSslCard\NovaSslCard;
use NovaCards\SystemInformationCard\SystemInformationCard;
use PhpJunior\NovaLogViewer\Tool as NovaLogViewerTool;
use Radermacher\NovaCurrentEnvironmentCard\NovaCurrentEnvironmentCard;
use Themsaid\CashierTool\CashierTool;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            new NovaLogViewerTool,
            new CashierTool,
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function (User $user) {
            return $user->hasRole('admin');
        });
    }

    /**
     * Get the cards that should be displayed on the default Nova dashboard.
     *
     * @return array
     */
    protected function cards()
    {
        return [
            new NewUsers,
            new UsersPerDay,
            new NewFiles,
            new FilesPerDay,
            // new FilesQueued,
            // new Help,
            new NovaCurrentEnvironmentCard,
            new NovaSslCard,
            new SystemInformationCard,
        ];
    }

    /**
     * Get the extra dashboards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [];
    }
}
