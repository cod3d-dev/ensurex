<?php

namespace App\Providers;

use App\HealthChecks\DatabaseHealthCheck;
use App\HealthChecks\FailingHealthCheck;
use Carbon\Carbon;
use Filament\Forms\Components\TextInput;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\CpuLoadCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

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
        // Set Spanish as the default locale for Carbon
        Carbon::setLocale('es');

        FilamentColor::register([
            'danger' => Color::Red,
            'gray' => Color::Zinc,
            'info' => Color::Blue,
            'primary' => Color::Amber,
            'success' => Color::Green,
            'warning' => Color::Amber,
            'violet' => Color::Violet,
            'pending' => Color::Yellow,
        ]);

        TextInput::configureUsing(function (TextInput $component): void {
            $component->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/');
        });

        FilamentAsset::register([
            Css::make('custom-stylesheet2', __DIR__.'/../../resources/css/custom.css'),
        ]);

        Model::unguard();

        // Register health checks
        Health::checks([
            UsedDiskSpaceCheck::new(),
            DatabaseHealthCheck::new(),
            FailingHealthCheck::new(),
            OptimizedAppCheck::new(),
            // CpuLoadCheck::new()
            // ->failWhenLoadIsHigherInTheLast5Minutes(2.0)
            // ->failWhenLoadIsHigherInTheLast15Minutes(1.5),
        ]);
    }
}
