<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::capture($layout, array_merge($data, ['content' => $content]));
    }

    public static function capture(string $__view, array $__data = []): string
    {
        $__file = APP_PATH . '/views/' . $__view . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException("View tidak ditemukan: $__view");
        }
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
