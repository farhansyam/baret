-- Skema database BARET (MySQL 8 / MariaDB 10.4+), InnoDB, utf8mb4
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS password_resets, throttle, settings, activity_logs, aspirasi, event_photos, events,
    news, news_categories, pengurus, nia_sequences, member_status_history, members, users, wilayah, roles;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(40)  NOT NULL UNIQUE,
    nama        VARCHAR(60)  NOT NULL,
    deskripsi   VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wilayah (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    kode        VARCHAR(12)  NOT NULL UNIQUE,
    nama        VARCHAR(120) NOT NULL,
    tingkat     ENUM('pusat','wilayah','cabang','ranting') NOT NULL,
    parent_id   INT UNSIGNED NULL,
    alamat      VARCHAR(255) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_wilayah_parent (parent_id),
    KEY idx_wilayah_tingkat (tingkat),
    CONSTRAINT fk_wilayah_parent FOREIGN KEY (parent_id) REFERENCES wilayah (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nama          VARCHAR(120) NOT NULL,
    email         VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id       TINYINT UNSIGNED NOT NULL,
    wilayah_id    INT UNSIGNED NULL,
    aktif         TINYINT(1) NOT NULL DEFAULT 1,
    last_login    DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_users_role (role_id),
    KEY idx_users_wilayah (wilayah_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id),
    CONSTRAINT fk_users_wilayah FOREIGN KEY (wilayah_id) REFERENCES wilayah (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE members (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    no_registrasi      VARCHAR(24)  NOT NULL UNIQUE,
    nia                VARCHAR(40)  NULL UNIQUE,
    user_id            INT UNSIGNED NULL,
    nik_enc            TEXT NOT NULL,              -- NIK terenkripsi AES-256-GCM
    nik_hash           CHAR(64) NOT NULL UNIQUE,   -- blind index untuk cek duplikasi
    nama               VARCHAR(120) NOT NULL,
    tempat_lahir       VARCHAR(80)  NOT NULL,
    tgl_lahir          DATE NOT NULL,
    jk                 ENUM('L','P') NOT NULL,
    pekerjaan          VARCHAR(80)  NULL,
    telepon            VARCHAR(20)  NOT NULL,
    email              VARCHAR(160) NULL,
    alamat             TEXT NOT NULL,
    wilayah_id         INT UNSIGNED NOT NULL,
    foto               VARCHAR(64)  NULL,
    ktp_file           VARCHAR(64)  NULL,
    status             ENUM('menunggu','aktif','ditolak','nonaktif') NOT NULL DEFAULT 'menunggu',
    catatan_verifikasi VARCHAR(255) NULL,
    verified_by        INT UNSIGNED NULL,
    verified_at        DATETIME NULL,
    consent_at         DATETIME NULL,
    deleted_at         DATETIME NULL,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_members_status (status),
    KEY idx_members_wilayah (wilayah_id),
    KEY idx_members_nama (nama),
    KEY idx_members_created (created_at),
    KEY idx_members_deleted (deleted_at),
    CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_members_wilayah FOREIGN KEY (wilayah_id) REFERENCES wilayah (id) ON DELETE RESTRICT,
    CONSTRAINT fk_members_verifier FOREIGN KEY (verified_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE member_status_history (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id    INT UNSIGNED NOT NULL,
    status_lama  VARCHAR(20) NULL,
    status_baru  VARCHAR(20) NOT NULL,
    catatan      VARCHAR(255) NULL,
    oleh         INT UNSIGNED NULL,
    waktu        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_msh_member (member_id),
    CONSTRAINT fk_msh_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    CONSTRAINT fk_msh_user FOREIGN KEY (oleh) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nomor urut NIA per kode wilayah per tahun (dikunci FOR UPDATE agar unik)
CREATE TABLE nia_sequences (
    kode     VARCHAR(12) NOT NULL,
    tahun    SMALLINT UNSIGNED NOT NULL,
    last_no  INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (kode, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pengurus (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id       INT UNSIGNED NOT NULL,
    wilayah_id      INT UNSIGNED NOT NULL,
    jabatan         VARCHAR(80) NOT NULL,
    urutan          SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    periode_mulai   DATE NOT NULL,
    periode_selesai DATE NULL,
    sk_nomor        VARCHAR(80) NULL,
    sk_file         VARCHAR(64) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pengurus_wilayah (wilayah_id),
    KEY idx_pengurus_member (member_id),
    CONSTRAINT fk_pengurus_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pengurus_wilayah FOREIGN KEY (wilayah_id) REFERENCES wilayah (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news_categories (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nama  VARCHAR(60) NOT NULL,
    slug  VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    judul        VARCHAR(200) NOT NULL,
    slug         VARCHAR(220) NOT NULL UNIQUE,
    kategori_id  INT UNSIGNED NULL,
    ringkasan    VARCHAR(300) NULL,
    isi          MEDIUMTEXT NOT NULL,
    gambar       VARCHAR(64) NULL,
    status       ENUM('draft','publish') NOT NULL DEFAULT 'draft',
    author_id    INT UNSIGNED NULL,
    published_at DATETIME NULL,
    views        INT UNSIGNED NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_news_status (status, published_at),
    KEY idx_news_kategori (kategori_id),
    CONSTRAINT fk_news_kategori FOREIGN KEY (kategori_id) REFERENCES news_categories (id) ON DELETE RESTRICT,
    CONSTRAINT fk_news_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    judul            VARCHAR(200) NOT NULL,
    slug             VARCHAR(220) NOT NULL UNIQUE,
    deskripsi        MEDIUMTEXT NOT NULL,
    lokasi           VARCHAR(160) NOT NULL,
    mulai            DATETIME NOT NULL,
    selesai          DATETIME NOT NULL,
    wilayah_id       INT UNSIGNED NULL,
    status           ENUM('draft','publish') NOT NULL DEFAULT 'draft',
    penanggung_jawab VARCHAR(120) NULL,
    gambar           VARCHAR(64) NULL,
    created_by       INT UNSIGNED NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_events_status (status, mulai),
    KEY idx_events_wilayah (wilayah_id),
    CONSTRAINT fk_events_wilayah FOREIGN KEY (wilayah_id) REFERENCES wilayah (id) ON DELETE RESTRICT,
    CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_photos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_id    INT UNSIGNED NOT NULL,
    file        VARCHAR(64) NOT NULL,
    caption     VARCHAR(160) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_photos_event (event_id),
    CONSTRAINT fk_photos_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE aspirasi (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tiket          VARCHAR(24) NOT NULL UNIQUE,
    nama           VARCHAR(120) NOT NULL,
    kontak         VARCHAR(160) NOT NULL,
    kategori       VARCHAR(40) NOT NULL,
    judul          VARCHAR(160) NOT NULL,
    isi            TEXT NOT NULL,
    lampiran       VARCHAR(64) NULL,
    status         ENUM('baru','diproses','selesai','ditolak') NOT NULL DEFAULT 'baru',
    ditangani_oleh INT UNSIGNED NULL,
    tanggapan      TEXT NULL,
    ip             VARCHAR(45) NULL,
    archived_at    DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_aspirasi_status (status),
    KEY idx_aspirasi_archived (archived_at),
    CONSTRAINT fk_aspirasi_user FOREIGN KEY (ditangani_oleh) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    aksi        VARCHAR(40) NOT NULL,
    tabel       VARCHAR(40) NOT NULL,
    record_id   VARCHAR(40) NULL,
    data_lama   JSON NULL,
    data_baru   JSON NULL,
    ip          VARCHAR(45) NULL,
    user_agent  VARCHAR(255) NULL,
    waktu       DATETIME NOT NULL,
    KEY idx_logs_waktu (waktu),
    KEY idx_logs_user (user_id),
    KEY idx_logs_tabel (tabel, record_id),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    `key`   VARCHAR(60) NOT NULL PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE throttle (
    `key`        CHAR(64) NOT NULL PRIMARY KEY,
    hits         INT UNSIGNED NOT NULL DEFAULT 0,
    first_at     DATETIME NOT NULL,
    locked_until DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
