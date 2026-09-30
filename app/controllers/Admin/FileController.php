<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Upload;

/** Menyajikan file privat (foto/KTP anggota, lampiran aspirasi, SK) dengan cek hak akses. */
final class FileController extends Controller
{
    public function show(string $area, string $name): void
    {
        if (!Upload::validName($name)) {
            abort(404);
        }
        $allowed = match ($area) {
            'members' => $this->memberFile('foto', $name),
            'ktp' => $this->memberFile('ktp_file', $name) && Auth::can('members.manage'),
            'aspirasi' => Auth::can('aspirasi.view') && (bool) Database::value('SELECT 1 FROM aspirasi WHERE lampiran = ?', [$name]),
            'sk' => $this->skFile($name),
            default => false,
        };
        if (!$allowed) {
            abort(404);
        }
        $path = Upload::dir('private/' . $area) . '/' . $name;
        if (!is_file($path)) {
            abort(404);
        }
        $mime = str_ends_with($name, '.pdf') ? 'application/pdf' : 'image/webp';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . $area . '-' . substr($name, 0, 8) . substr($name, -5) . '"');
        header('Cache-Control: private, max-age=600');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; object-src 'self'");
        readfile($path);
        exit;
    }

    private function memberFile(string $col, string $name): bool
    {
        if (!Auth::can('members.view')) {
            return false;
        }
        $params = [$name];
        $scope = Auth::scopeSql('wilayah_id', $params);
        return (bool) Database::value("SELECT 1 FROM members WHERE `$col` = ? $scope", $params);
    }

    private function skFile(string $name): bool
    {
        if (!Auth::can('pengurus.view')) {
            return false;
        }
        $params = [$name];
        $scope = Auth::scopeSql('wilayah_id', $params);
        return (bool) Database::value("SELECT 1 FROM pengurus WHERE sk_file = ? $scope", $params);
    }
}
