-- ================================================================
-- DEUZEM Faaliyet Yönetim Sistemi — Tam Migration
-- phpMyAdmin'den import edin: USE deuzem_etkinlik;
-- Mevcut verilere dokunulmaz — DEFAULT 1 tüm kayıtları aktif tutar.
-- ================================================================

-- BÖLÜM 1: Mevcut kategori tablolarına aktif_mi sütunu ekle
-- (Mevcut kayıtlar otomatik aktif kabul edilir)

ALTER TABLE `birimler`
    ADD COLUMN `aktif_mi` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1=Aktif, 0=Pasif';

ALTER TABLE `diller`
    ADD COLUMN `aktif_mi` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1=Aktif, 0=Pasif';

ALTER TABLE `kapsamlar`
    ADD COLUMN `aktif_mi` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1=Aktif, 0=Pasif';

ALTER TABLE `toplumsal_faydalar`
    ADD COLUMN `aktif_mi` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1=Aktif, 0=Pasif';

ALTER TABLE `ska`
    ADD COLUMN `aktif_mi` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT '1=Aktif, 0=Pasif';

-- ================================================================
-- BÖLÜM 2: Dinamik Ek Kategori Sistemi — 3 Yeni Tablo
-- ================================================================

-- 2a. Kategori tipi tanımları
--     Örn: (1, 'Hedef Kitle'), (2, 'Proje Türü')
CREATE TABLE `ek_kategori_tipleri` (
    `tip_id`           INT(11) NOT NULL AUTO_INCREMENT,
    `tip_adi`          VARCHAR(100) NOT NULL,
    `aktif_mi`         TINYINT(1) NOT NULL DEFAULT 1,
    `olusturma_tarihi` TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`tip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- 2b. Her tipin seçenek değerleri
--     Örn: (1, 1, 'Öğrenci'), (2, 1, 'Akademisyen'), (3, 2, 'Araştırma')
CREATE TABLE `ek_kategori_degerleri` (
    `deger_id`  INT(11) NOT NULL AUTO_INCREMENT,
    `tip_id`    INT(11) NOT NULL,
    `deger_adi` VARCHAR(255) NOT NULL,
    `aktif_mi`  TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`deger_id`),
    KEY `idx_tip_id` (`tip_id`),
    CONSTRAINT `fk_ekd_tip` FOREIGN KEY (`tip_id`)
        REFERENCES `ek_kategori_tipleri` (`tip_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- 2c. Faaliyet - ek kategori bağlantı tablosu (pivot)
--     Mevcut faaliyetler tablosuna yeni sütun EKLENMEMEKTEDİR.
CREATE TABLE `faaliyet_ek_kategoriler` (
    `faaliyet_id` INT(11) NOT NULL,
    `deger_id`    INT(11) NOT NULL,
    PRIMARY KEY (`faaliyet_id`, `deger_id`),
    CONSTRAINT `fk_fek_faaliyet` FOREIGN KEY (`faaliyet_id`)
        REFERENCES `faaliyetler` (`faaliyet_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_fek_deger` FOREIGN KEY (`deger_id`)
        REFERENCES `ek_kategori_degerleri` (`deger_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;
