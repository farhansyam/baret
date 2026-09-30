<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router sederhana. Pola: /berita/{slug}, /admin/anggota/{id:\d+}.
 * Middleware: 'auth', 'guest', 'perm:members.view'.
 */
final class Router
{
    private array $routes = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    public function __construct(private string $basePath)
    {
    }

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        [$oldPrefix, $oldMw] = [$this->prefix, $this->groupMiddleware];
        $this->prefix .= $prefix;
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
        $callback($this);
        [$this->prefix, $this->groupMiddleware] = [$oldPrefix, $oldMw];
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $full = rtrim($this->prefix . $path, '/') ?: '/';
        $regex = preg_replace_callback('/\{(\w+)(?::((?:[^{}]|\{[^{}]*\})+))?\}/', function ($m) {
            return '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')';
        }, $full);
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
        }
        $path = '/' . trim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $allowed = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed = true;
                continue;
            }
            if ($method === 'POST' && !Csrf::verify($_POST['_token'] ?? null)) {
                throw new HttpException('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.', 419);
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            foreach ($route['middleware'] as $mw) {
                $this->runMiddleware($mw);
            }
            [$class, $action] = $route['handler'];
            (new $class())->$action(...$params);
            return;
        }
        throw new HttpException('', $allowed ? 405 : 404);
    }

    private function runMiddleware(string $mw): void
    {
        if ($mw === 'auth') {
            if (!Auth::check()) {
                $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? null;
                redirect('/login');
            }
            return;
        }
        if ($mw === 'guest') {
            if (Auth::check()) {
                redirect('/admin');
            }
            return;
        }
        if (str_starts_with($mw, 'perm:')) {
            Auth::authorize(substr($mw, 5));
        }
    }
}
