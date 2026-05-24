<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $cookies;
    private array $files;
    private array $server;
    private array $params = [];

    public function __construct(string $method, string $path, array $query, array $post, array $cookies, array $files, array $server)
    {
        $this->method  = strtoupper($method);
        $this->path    = $path;
        $this->query   = $query;
        $this->post    = $post;
        $this->cookies = $cookies;
        $this->files   = $files;
        $this->server  = $server;
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($path !== '/') $path = rtrim($path, '/') ?: '/';
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $path,
            $_GET, $_POST, $_COOKIE, $_FILES, $_SERVER
        );
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function query(?string $key = null, $default = null) {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }
    public function post(?string $key = null, $default = null) {
        if ($key === null) return $this->post;
        return $this->post[$key] ?? $default;
    }
    public function input(?string $key = null, $default = null) {
        $merged = array_merge($this->query, $this->post);
        if ($key === null) return $merged;
        return $merged[$key] ?? $default;
    }
    public function file(string $key) { return $this->files[$key] ?? null; }
    public function cookie(string $key, $default = null) { return $this->cookies[$key] ?? $default; }
    public function server(string $key, $default = null) { return $this->server[$key] ?? $default; }

    public function setParam(string $key, $value): void { $this->params[$key] = $value; }
    public function param(string $key, $default = null) { return $this->params[$key] ?? $default; }
    public function params(): array { return $this->params; }

    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    public function userAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    public function isPost(): bool { return $this->method === 'POST'; }
    public function isJson(): bool {
        $ct = $this->server['CONTENT_TYPE'] ?? '';
        return stripos($ct, 'application/json') !== false;
    }

    public function isAdminPath(): bool
    {
        $admin = '/' . trim((string) config('app.admin_path', 'admin'), '/');
        return $this->path === $admin || str_starts_with($this->path, $admin . '/');
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($this->server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public function isHttps(): bool
    {
        if (($this->server['HTTPS'] ?? 'off') !== 'off' && $this->server['HTTPS'] !== '') return true;
        if (($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
        return false;
    }
}
