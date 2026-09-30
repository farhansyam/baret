<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Core\Validator;
use App\Core\View;

abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'layouts/public'): void
    {
        echo View::render($view, $data, $layout);
    }

    protected function admin(string $view, array $data = []): void
    {
        $this->render('admin/' . $view, $data, 'layouts/admin');
    }

    /**
     * Validasi; bila gagal kembali ke form dengan pesan per field dan isian lama (kecuali sandi/NIK).
     * $extra(Validator $v) boleh menambah error khusus.
     */
    protected function validate(array $data, array $rules, array $labels = [], ?callable $extra = null, ?string $backTo = null): array
    {
        $v = Validator::make($data, $rules, $labels);
        if ($extra) {
            $extra($v);
        }
        if ($v->fails()) {
            $this->failWith($v->errors(), $data, $backTo);
        }
        return $data;
    }

    protected function failWith(array $errors, array $data, ?string $backTo = null, string $message = 'Periksa kembali isian yang ditandai.'): never
    {
        $old = array_diff_key($data, array_flip(['password', 'password_confirmation', 'password_lama', '_token']));
        Session::withInput($old, $errors);
        Session::flash('error', $message);
        $backTo ? redirect($backTo) : back();
    }

    /** Honeypot + waktu isi minimum untuk form publik (anti-bot sederhana). */
    protected function isBot(): bool
    {
        if (!empty($_POST['website'])) {
            return true;
        }
        $ts = (int) ($_POST['_ts'] ?? 0);
        return $ts > 0 && time() - $ts < 3;
    }

    protected function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $v = $_POST[$k] ?? null;
            $out[$k] = is_string($v) ? trim($v) : $v;
        }
        return $out;
    }
}
