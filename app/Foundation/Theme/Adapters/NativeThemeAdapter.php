<?php

declare(strict_types=1);

namespace App\Foundation\Theme\Adapters;

use App\Foundation\Theme\Contracts\ThemeAdapterInterface;
use App\Foundation\Theme\Theme;

class NativeThemeAdapter implements ThemeAdapterInterface
{
    protected Theme $theme;

    public function boot(Theme $theme): void
    {
        $this->theme = $theme;
        
        $helpersPath = $theme->getPath() . '/src/helpers.php';
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    public function render(string $view, array $data = []): string
    {
        $viewPath = $this->theme->getPath() . '/src/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            return "Native View [{$view}] not found.";
        }

        extract($data);
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }

    public function getType(): string
    {
        return 'native';
    }
}
