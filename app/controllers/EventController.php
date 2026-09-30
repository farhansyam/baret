<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Event;

final class EventController extends Controller
{
    public function index(): void
    {
        $view = query('tampilan') === 'kalender' ? 'kalender' : 'daftar';
        $waktu = query('waktu');
        $waktu = isset(Event::WAKTU[$waktu]) ? $waktu : null;
        $q = mb_substr((string) query('q', ''), 0, 100);

        $data = ['title' => 'Kegiatan', 'view' => $view, 'waktu' => $waktu, 'q' => $q];

        if ($view === 'kalender') {
            $bulan = (string) query('bulan', date('Y-m'));
            if (!preg_match('/^(\d{4})-(\d{2})$/', $bulan, $m) || (int) $m[2] < 1 || (int) $m[2] > 12) {
                $m = [null, date('Y'), date('m')];
            }
            $data['year'] = (int) $m[1];
            $data['month'] = (int) $m[2];
            $data['monthEvents'] = Event::inMonth($data['year'], $data['month']);
        } else {
            [$data['items'], $data['pager']] = Event::publicPaginate($waktu, $q ?: null);
        }
        $this->render('public/kegiatan/index', $data);
    }

    public function show(string $slug): void
    {
        $item = Event::findPublic($slug) ?? abort(404, 'Kegiatan tidak ditemukan.');
        $this->render('public/kegiatan/show', [
            'title' => $item['judul'],
            'description' => str_limit($item['deskripsi'], 160),
            'item' => $item,
            'photos' => Event::photos((int) $item['id']),
        ]);
    }
}
