<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Pengurus
{
    /** Pengurus yang masa jabatannya masih berlaku, dikelompokkan per wilayah_id. */
    public static function currentByWilayah(): array
    {
        $rows = Database::all(
            "SELECT p.id, p.wilayah_id, p.jabatan, p.urutan, p.periode_mulai, p.periode_selesai, m.nama
             FROM pengurus p JOIN members m ON m.id = p.member_id
             WHERE p.periode_mulai <= CURDATE() AND (p.periode_selesai IS NULL OR p.periode_selesai >= CURDATE())
               AND m.deleted_at IS NULL
             ORDER BY p.urutan, p.id"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['wilayah_id']][] = $r;
        }
        return $out;
    }
}
