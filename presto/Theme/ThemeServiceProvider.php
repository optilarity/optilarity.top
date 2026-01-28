<?php

declare(strict_types=1);

namespace PrestoWorld\Theme;

use App\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(ThemeManager::class, function ($app) {
            $manager = new ThemeManager($app);
            
            // Register native themes path
            $manager->addDiscoveryPath($app->basePath('themes'));
            
            return $manager;
        });
    }

    public function boot(): void
    {
        $manager = $this->app->make(ThemeManager::class);
        
        $manager->discover();
        
        // Set default theme from config or env
        $defaultTheme = env('THEME_ACTIVE', 'tucnguyen');
        $manager->setActiveTheme($defaultTheme);
    }
}
