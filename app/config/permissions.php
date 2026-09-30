<?php
/**
 * Peta hak akses per peran (RBAC).
 * 'scoped' => true berarti data dibatasi ke wilayah pengguna + turunannya (di sisi query).
 */
return [
    'super_admin' => [
        'scoped' => false,
        'perms'  => ['*'],
    ],
    'ketua_umum' => [
        'scoped' => false,
        'perms'  => [
            'dashboard.view', 'members.view', 'members.verify', 'wilayah.view', 'pengurus.view',
            'news.manage', 'events.manage', 'aspirasi.view', 'logs.view',
        ],
    ],
    'sekretaris' => [
        'scoped' => false,
        'perms'  => [
            'dashboard.view', 'members.view', 'members.manage', 'members.verify', 'members.export',
            'wilayah.view', 'wilayah.manage', 'pengurus.view', 'pengurus.manage',
            'news.manage', 'events.manage', 'aspirasi.view', 'aspirasi.manage',
        ],
    ],
    'bendahara' => [
        'scoped' => false,
        'perms'  => ['dashboard.view', 'members.view', 'wilayah.view', 'pengurus.view'],
    ],
    'pengurus_wilayah' => [
        'scoped' => true,
        'perms'  => [
            'dashboard.view', 'members.view', 'members.manage', 'members.verify', 'members.export',
            'wilayah.view', 'pengurus.view', 'events.manage',
        ],
    ],
    'anggota' => [
        'scoped' => false,
        'perms'  => ['self.view'],
    ],
];
