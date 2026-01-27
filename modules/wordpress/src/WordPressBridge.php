<?php

declare(strict_types=1);

namespace PrestoWorld\WordPress;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;
use Witals\Framework\Application;

/**
 * WordPress Bridge
 */
class WordPressBridge implements MiddlewareInterface
{
    private Application $app;
    private WordPressLoader $loader;

    public function __construct(Application $app, WordPressLoader $loader)
    {
        $this->app = $app;
        $this->loader = $loader;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->loader->load()) {
            return new Response(500, [], 'WordPress could not be loaded.');
        }

        $this->setupWordPressEnvironment($request);

        ob_start();
        try {
            $this->handleWordPressRequest();
            $content = ob_get_clean();
            
            return new Response(200, ['Content-Type' => 'text/html'], $content);
        } catch (\Throwable $e) {
            ob_end_clean();
            return new Response(500, [], $e->getMessage());
        }
    }

    private function setupWordPressEnvironment(ServerRequestInterface $request): void
    {
        $uri = $request->getUri();
        $_SERVER['REQUEST_URI'] = $uri->getPath() . ($uri->getQuery() ? '?' . $uri->getQuery() : '');
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['HTTP_HOST'] = $uri->getHost();
        
        $_GET = $request->getQueryParams();
        $_POST = $request->getParsedBody() ?? [];
        $_COOKIE = $request->getCookieParams();
        $_REQUEST = array_merge($_GET, $_POST);
    }

    private function handleWordPressRequest(): void
    {
        global $wp, $wp_query, $wp_did_header;

        if (!$wp_did_header) {
            if (!defined('WP_USE_THEMES')) {
                define('WP_USE_THEMES', true);
            }
            require_once $this->loader->getWordPressPath() . '/wp-blog-header.php';
        } else {
            if (isset($wp)) {
                $wp->main();
            }
        }
    }
}
