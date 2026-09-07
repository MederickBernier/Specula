<?php

namespace App\Providers;

use App\Models\ItemLink;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureLinkableModules();
    }

    /**
     * Register the modules that can appear at either end of an ItemLink.
     *
     * Enforcing the map keeps the stored type a stable alias rather than a
     * class name, so the linkable modules survive a namespace move. Add a
     * module here and it becomes linkable everywhere at once.
     */
    protected function configureLinkableModules(): void
    {
        Relation::enforceMorphMap(ItemLink::modules());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
