<?php
declare(strict_types=1);

namespace App\Core;

/** Log aktivitas (akuntabilitas). Hanya bisa ditambah, tidak ada ubah/hapus dari UI. */
final class ActivityLog
{
    private const HIDDEN = ['password_hash', 'password', 'nik_enc', 'nik_hash', 'token', 'token_hash', '_token'];

    public static function record(string $aksi, string $tabel, int|string|null $recordId = null, ?array $old = null, ?array $new = null): void
    {
        $old = $old !== null ? self::clean($old) : null;
        $new = $new !== null ? self::clean($new) : null;

        // Untuk ubah data: simpan hanya kolom yang berubah
        if ($old !== null && $new !== null) {
            $changed = [];
            foreach ($new as $k => $v) {
                if (!array_key_exists($k, $old) || (string) $old[$k] !== (string) $v) {
                    $changed[$k] = true;
                }
            }
            $old = array_intersect_key($old, $changed);
            $new = array_intersect_key($new, $changed);
        }

        try {
            Database::insert('activity_logs', [
                'user_id'    => Auth::id(),
                'aksi'       => $aksi,
                'tabel'      => $tabel,
                'record_id'  => $recordId !== null ? (string) $recordId : null,
                'data_lama'  => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
                'data_baru'  => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
                'ip'         => client_ip(),
                'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'waktu'      => now(),
            ]);
        } catch (\Throwable $e) {
            error_log('Gagal mencatat log aktivitas: ' . $e->getMessage());
        }
    }

    private static function clean(array $data): array
    {
        foreach (self::HIDDEN as $k) {
            if (array_key_exists($k, $data)) {
                $data[$k] = '[disembunyikan]';
            }
        }
        unset($data['updated_at']);
        return $data;
    }
}
