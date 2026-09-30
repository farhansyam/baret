<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Throttle;
use App\Models\Event;
use App\Models\Member;
use App\Models\News;
use App\Models\Pengurus;
use App\Models\Wilayah;

final class PublicController extends Controller
{
    public function home(): void
    {
        $stats = [
            'anggota' => Member::countVerified(),
            'wilayah' => (int) Database::value("SELECT COUNT(*) FROM wilayah WHERE tingkat = 'wilayah'"),
            'cabang' => (int) Database::value("SELECT COUNT(*) FROM wilayah WHERE tingkat IN ('cabang','ranting')"),
            'kegiatan' => (int) Database::value("SELECT COUNT(*) FROM events WHERE status = 'publish'"),
        ];
        $this->render('public/home', [
            'title' => null,
            'stats' => $stats,
            'news' => News::latest(3),
            'events' => Event::upcoming(4),
        ]);
    }

    public function profil(): void
    {
        $this->render('public/profil', ['title' => 'Profil Organisasi']);
    }

    public function struktur(): void
    {
        $this->render('public/struktur', [
            'title' => 'Struktur Organisasi',
            'tree' => Wilayah::tree(),
            'pengurus' => Pengurus::currentByWilayah(),
        ]);
    }

    public function kontak(): void
    {
        $this->render('public/kontak', ['title' => 'Kontak']);
    }

    /** Verifikasi keanggotaan via NIA (dipakai juga oleh QR e-KTA di Fase 2). */
    public function verifikasi(): void
    {
        $nia = strtoupper((string) query('nia', ''));
        $result = null;
        $limited = false;
        if ($nia !== '') {
            if (!Throttle::allow('verif:' . client_ip(), 30)) {
                $limited = true;
            } elseif (preg_match('/^BRT-[A-Z0-9-]{2,12}-\d{4}-\d{5}$/', $nia)) {
                $result = Member::publicLookup($nia);
            }
        }
        $this->render('public/verifikasi', [
            'title' => 'Verifikasi Anggota',
            'nia' => $nia,
            'result' => $result,
            'searched' => $nia !== '',
            'limited' => $limited,
        ]);
    }
}
