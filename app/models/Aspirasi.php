<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Aspirasi
{
    public const KATEGORI = ['Saran', 'Pengaduan', 'Laporan Kejadian', 'Permohonan Bantuan', 'Lainnya'];
    public const STATUS = ['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'];

    public static function newTicket(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $rand = '';
            for ($i = 0; $i < 6; $i++) {
                $rand .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $t = 'ASP-' . date('ymd') . '-' . $rand;
        } while (Database::value('SELECT 1 FROM aspirasi WHERE tiket = ?', [$t]));
        return $t;
    }

    public static function findByTicket(string $tiket): ?array
    {
        return Database::one(
            'SELECT tiket, kategori, judul, status, tanggapan, created_at, updated_at FROM aspirasi WHERE tiket = ?',
            [strtoupper(trim($tiket))]
        );
    }
}
