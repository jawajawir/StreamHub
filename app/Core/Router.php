<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<int, array{method:string, path:string, regex:string, params:array, handler:mixed, middleware:array}> */
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, $handler): self    { return $this->add('GET',    $path, $handler); }
    public function post(string $path, $handler): self   { return $this->add('POST',   $path, $handler); }
    public function any(string $path, $handler): self    { return $this->add('ANY',    $path, $handler); }

    public function group(array $opts, callable $callback): void
    {
        $this->groupStack[] = $opts;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function currentMiddleware(): array
    {
        $mw = [];
        foreach ($this->groupStack as $g) {
            foreach (($g['middleware'] ?? []) as $m) $mw[] = $m;
        }
        return $mw;
    }

    private function currentPrefix(): string
    {
        $prefix = '';
        foreach ($this->groupStack as $g) {
            if (!empty($g['prefix'])) {
                $prefix .= '/' . trim($g['prefix'], '/');
            }
        }
        return $prefix;
    }

    public function add(string $method, string $path, $handler): self
    {
        $path = $this->currentPrefix() . '/' . ltrim($path, '/');
        if ($path === '') $path = '/';
        if ($path !== '/') $path = rtrim($path, '/');
        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path) ?? $path;
        $regex = '#^' . $regex . '$#';
        $this->routes[] = [
            'method' => strtoupper($method),
            'path'   => $path,
            'regex'  => $regex,
            'params' => $params,
            'handler' => $handler,
            'middleware' => $this->currentMiddleware(),
        ];
        return $this;
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) continue;
            if (!preg_match($route['regex'], $path, $matches)) continue;

            array_shift($matches);
            foreach ($route['params'] as $i => $name) {
                $request->setParam($name, $matches[$i] ?? null);
            }

            // Run middleware chain
            $middlewareNames = $route['middleware'];
            $core = function (Request $req) use ($route) {
                $this->invoke($route['handler'], $req);
            };
            $stack = array_reduce(array_reverse($middlewareNames), function ($next, $mw) {
                return function (Request $req) use ($mw, $next) {
                    $instance = new $mw();
                    $instance->handle($req, $next);
                };
            }, $core);
            $stack($request);
            return;
        }

        Response::notFound('Route not found.');
    }

    private function invoke($handler, Request $request): void
    {
        if (is_callable($handler)) {
            $handler($request);
            return;
        }
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $controller = new $class();
            $controller->$method($request);
            return;
        }
        throw new \RuntimeException('Invalid route handler');
    }

    public function all(): array { return $this->routes; }
}
