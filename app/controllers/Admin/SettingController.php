<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\ActivityLog;
use App\Core\Upload;
use App\Core\Validator;
use App\Models\Setting;

final class SettingController extends Controller
{
    public const FIELDS = [
        'nama_organisasi' => ['Nama organisasi', 'required|max:120'],
        'singkatan' => ['Singkatan', 'required|max:20'],
        'tagline' => ['Tagline', 'nullable|max:200'],
        'alamat' => ['Alamat sekretariat', 'required|max:255'],
        'telepon' => ['Telepon', 'nullable|max:30'],
        'whatsapp' => ['WhatsApp (format 62…)', ['nullable', 'regex:/^62\d{8,13}$/']],
        'email' => ['Email', 'nullable|email'],
        'instagram' => ['URL Instagram', 'nullable|url|max:200'],
        'facebook' => ['URL Facebook', 'nullable|url|max:200'],
        'youtube' => ['URL YouTube', 'nullable|url|max:200'],
        'map_lat' => ['Lintang (latitude)', ['nullable', 'regex:/^-?\d{1,2}(\.\d+)?$/']],
        'map_lng' => ['Bujur (longitude)', ['nullable', 'regex:/^-?\d{1,3}(\.\d+)?$/']],
        'nama_ketua' => ['Nama Ketua Umum', 'required|max:120'],
        'sambutan_ketua' => ['Sambutan ketua', 'required|max:2000'],
        'sejarah' => ['Sejarah', 'required|max:5000'],
        'visi' => ['Visi', 'required|max:500'],
        'misi' => ['Misi (satu per baris)', 'required|max:3000'],
        'nilai' => ['Nilai (Judul|Deskripsi per baris)', 'required|max:3000'],
        'legalitas' => ['Legalitas (satu per baris)', 'nullable|max:3000'],
    ];

    public function edit(): void
    {
        $this->admin('pengaturan', ['title' => 'Pengaturan Situs', 'values' => Setting::all()]);
    }

    public function update(): void
    {
        $data = $this->only(array_keys(self::FIELDS));
        $rules = array_map(fn($f) => $f[1], self::FIELDS);
        $labels = array_map(fn($f) => $f[0], self::FIELDS);
        $v = Validator::make($data, $rules, $labels);
        if ($v->fails()) {
            $this->failWith($v->errors(), $data, '/admin/pengaturan');
        }
        $old = Setting::all();
        if (Upload::has('ad_art_file')) {
            [$file, $err] = Upload::document($_FILES['ad_art_file'], 'docs');
            if ($err) {
                $this->failWith(['ad_art_file' => $err], $data, '/admin/pengaturan');
            }
            $data['ad_art_file'] = $file;
            Upload::delete('docs', $old['ad_art_file'] ?? null);
        } elseif (input('hapus_ad_art') === '1') {
            $data['ad_art_file'] = '';
            Upload::delete('docs', $old['ad_art_file'] ?? null);
        }
        foreach ($data as $k => $val) {
            Setting::set($k, (string) $val);
        }
        ActivityLog::record('update', 'settings', null, array_intersect_key($old, $data), $data);
        flash('success', 'Pengaturan situs disimpan.');
        redirect('/admin/pengaturan');
    }
}
