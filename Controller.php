<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function json(array $payload, int $status = 200): void
    {
        Response::json($payload, $status);
    }

    protected function redirect(string $url, int $status = 302): void
    {
        Response::redirect($url, $status);
    }
}
