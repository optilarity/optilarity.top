<?php

declare(strict_types=1);

namespace App\Http;

use Witals\Framework\Application;
use Witals\Framework\Contracts\Http\Kernel as KernelContract;
use Witals\Framework\Http\Request;
use Witals\Framework\Http\Response;
use Psr\Log\LoggerInterface;

/**
 * HTTP Kernel
 * Handles HTTP request processing and middleware
 */
class Kernel implements KernelContract
{


    protected Application $app;
    protected array $middleware = [];
    protected LoggerInterface $logger;

    public function __construct(Application $app, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->logger = $logger;
    }

    /**
     * Handle an incoming HTTP request
     */
    public function handle(Request $request): Response
    {
        $this->logger->info("Incoming request: {method} {uri}", [
            'method' => $request->method(),
            'uri' => $request->uri(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);

        try {
            // Simple routing example
            $path = $request->path();

            // Route: Home
            if ($path === '/' || $path === '') {
                return $this->handleHome($request);
            }

            // Route: Health check
            if ($path === '/health') {
                return $this->handleHealth($request);
            }

            // Route: Info
            if ($path === '/info') {
                return $this->handleInfo($request);
            }

            // 404 Not Found
            return Response::json([
                'error' => 'Not Found',
                'path' => $path,
            ], 404);

        } catch (\Throwable $e) {
            return Response::json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle home route
     */
    protected function handleHome(Request $request): Response
    {
        $modules = [];
        if (app()->has(\App\Foundation\Module\ModuleManager::class)) {
            $manager = app(\App\Foundation\Module\ModuleManager::class);
            foreach ($manager->all() as $module) {
                $modules[] = [
                    'name' => $module->getName(),
                    'version' => $module->getVersion(),
                    'priority' => $module->getPriority(),
                    'enabled' => $module->isEnabled() ? 'Yes' : 'No',
                    'loaded' => $manager->isLoaded($module->getName()) ? 'Yes' : 'No',
                    'path' => $module->getPath(),
                    'type' => $module->getType(),
                ];
            }
        }

        return Response::json([
            'message' => 'Welcome to PrestoWorld Native!',
            'runtime' => $this->getEnvironmentName(),
            'modules' => $modules,
            'wordpress_enabled' => config('modules.enabled.wordpress') ? 'Yes' : 'No',
        ]);
    }

    /**
     * Handle health check route
     */
    protected function handleHealth(Request $request): Response
    {
        return Response::json([
            'status' => 'healthy',
            'environment' => $this->getEnvironmentName(),
            'timestamp' => time(),
            'uptime' => $this->getUptime(),
        ]);
    }

    /**
     * Handle info route
     */
    protected function handleInfo(Request $request): Response
    {
        return Response::json([
            'app' => [
                'name' => 'Witals Framework',
                'environment' => $this->getEnvironmentName(),
                'is_roadrunner' => $this->app->isRoadRunner(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'server' => [
                'software' => $this->getServerInfo(),
                'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            ],
            'performance' => [
                'memory_usage' => $this->getMemoryUsage(),
                'peak_memory' => $this->getPeakMemory(),
                'uptime' => $this->getUptime(),
            ],
        ]);
    }

    protected function getEnvironmentName(): string
    {
        if ($this->app->isRoadRunner()) return 'RoadRunner';
        if ($this->app->isReactPhp()) return 'ReactPHP';
        if ($this->app->isSwoole()) return 'Swoole';
        if ($this->app->isOpenSwoole()) return 'OpenSwoole';
        
        return 'Traditional Web Server';
    }

    protected function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    protected function getServerInfo(): string
    {
        if ($this->app->isRoadRunner()) return 'RoadRunner';
        if ($this->app->isReactPhp()) return 'ReactPHP (Event Loop)';
        if ($this->app->isSwoole()) return 'Swoole Server';
        if ($this->app->isOpenSwoole()) return 'OpenSwoole Server';

        return $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown SAPI';
    }

    protected function getMemoryUsage(): string
    {
        return $this->formatBytes(memory_get_usage(true));
    }

    protected function getPeakMemory(): string
    {
        return $this->formatBytes(memory_get_peak_usage(true));
    }

    protected function getUptime(): string
    {
        if (!defined('WITALS_START')) {
            return 'N/A';
        }
        $uptime = microtime(true) - WITALS_START;
        return number_format($uptime, 3) . 's';
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
