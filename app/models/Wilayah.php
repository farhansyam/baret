<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Wilayah
{
    public const TINGKAT = ['pusat' => 'Pusat', 'wilayah' => 'Wilayah', 'cabang' => 'Cabang', 'ranting' => 'Ranting'];
    /** Tingkat induk yang diizinkan untuk setiap tingkat. */
    public const PARENT_OF = ['pusat' => null, 'wilayah' => 'pusat', 'cabang' => 'wilayah', 'ranting' => 'cabang'];

    private static ?array $all = null;

    public static function all(): array
    {
        return self::$all ??= Database::all("SELECT * FROM wilayah ORDER BY FIELD(tingkat, 'pusat','wilayah','cabang','ranting'), nama");
    }

    public static function flush(): void
    {
        self::$all = null;
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $w) {
            if ((int) $w['id'] === $id) {
                return $w;
            }
        }
        return null;
    }

    /** ID wilayah beserta seluruh turunannya. */
    public static function descendantIds(int $id): array
    {
        $children = [];
        foreach (self::all() as $w) {
            $children[(int) $w['parent_id']][] = (int) $w['id'];
        }
        $out = [$id];
        $stack = [$id];
        while ($stack) {
            $cur = array_pop($stack);
            foreach ($children[$cur] ?? [] as $c) {
                $out[] = $c;
                $stack[] = $c;
            }
        }
        return $out;
    }

    /** Pohon bertingkat: setiap node memiliki kunci 'children'. */
    public static function tree(?array $onlyIds = null): array
    {
        $nodes = [];
        foreach (self::all() as $w) {
            if ($onlyIds !== null && !in_array((int) $w['id'], $onlyIds, true)) {
                continue;
            }
            $w['children'] = [];
            $nodes[(int) $w['id']] = $w;
        }
        $roots = [];
        foreach ($nodes as $id => &$n) {
            $pid = (int) $n['parent_id'];
            if ($pid && isset($nodes[$pid])) {
                $nodes[$pid]['children'][] = &$n;
            } else {
                $roots[] = &$n;
            }
        }
        unset($n);
        return $roots;
    }

    /** Daftar datar dengan indentasi untuk <select>, dibatasi $onlyIds bila ada. */
    public static function options(?array $onlyIds = null): array
    {
        $out = [];
        $walk = function (array $nodes, int $depth) use (&$walk, &$out) {
            foreach ($nodes as $n) {
                $out[] = ['id' => (int) $n['id'], 'label' => str_repeat('— ', $depth) . $n['nama'], 'kode' => $n['kode'], 'tingkat' => $n['tingkat']];
                $walk($n['children'], $depth + 1);
            }
        };
        $walk(self::tree($onlyIds), 0);
        return $out;
    }

    /** Jumlah relasi yang mencegah penghapusan. */
    public static function relations(int $id): array
    {
        return array_filter([
            'sub-wilayah' => (int) Database::value('SELECT COUNT(*) FROM wilayah WHERE parent_id = ?', [$id]),
            'anggota' => (int) Database::value('SELECT COUNT(*) FROM members WHERE wilayah_id = ?', [$id]),
            'pengurus' => (int) Database::value('SELECT COUNT(*) FROM pengurus WHERE wilayah_id = ?', [$id]),
            'pengguna' => (int) Database::value('SELECT COUNT(*) FROM users WHERE wilayah_id = ?', [$id]),
            'kegiatan' => (int) Database::value('SELECT COUNT(*) FROM events WHERE wilayah_id = ?', [$id]),
        ]);
    }
}
