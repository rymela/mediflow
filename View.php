<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = []): void
    {
        $viewPath = __DIR__ . '/../Views/' . str_replace('..', '', $view) . '.php';
        if (!is_file($viewPath)) {
            Response::text('View not found', 500);
        }

        extract($data, EXTR_SKIP);
        require $viewPath;
    }
}
