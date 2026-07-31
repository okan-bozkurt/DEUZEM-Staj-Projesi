-- DEUZEM Faaliyet Yönetim Sistemi - Veritabani Yapisi
-- Bu dosyayi phpMyAdmin uzerinden import edin

-- Veritabani olustur
CREATE DATABASE IF NOT EXISTS `deuzem_etkinlik` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `deuzem_etkinlik`;

-- Birimler tablosu (paydas birimler)
CREATE TABLE `birimler` (
  `birim_id` int(11) NOT NULL AUTO_INCREMENT,
  `birim_adi` varchar(100) NOT NULL,
  `birim_kodu` varchar(50) NOT NULL,
  PRIMARY KEY (`birim_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- Diller tablosu
CREATE TABLE `diller` (
  `dil_id` int(11) NOT NULL AUTO_INCREMENT,
  `dil_adi` varchar(100) NOT NULL,
  `dil_kodu` varchar(50) NOT NULL,
  PRIMARY KEY (`dil_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- Kapsamlar tablosu (ulusal, uluslararasi vb.)
CREATE TABLE `kapsamlar` (
  `kapsam_id` int(11) NOT NULL AUTO_INCREMENT,
  `kapsam_adi` varchar(100) NOT NULL,
  PRIMARY KEY (`kapsam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- Toplumsal faydalar tablosu
CREATE TABLE `toplumsal_faydalar` (
  `fayda_id` int(11) NOT NULL AUTO_INCREMENT,
  `fayda_adi` varchar(100) NOT NULL,
  PRIMARY KEY (`fayda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- SKA tablosu (Surdurulebilir Kalkinma Amaclari)
CREATE TABLE `ska` (
  `ska_id` int(11) NOT NULL AUTO_INCREMENT,
  `ska_aciklama` text NOT NULL,
  PRIMARY KEY (`ska_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- Kullanicilar tablosu
CREATE TABLE `kullanicilar` (
  `kullanici_id` int(11) NOT NULL AUTO_INCREMENT,
  `ad` varchar(100) DEFAULT NULL,
  `soyad` varchar(100) DEFAULT NULL,
  `eposta` varchar(191) DEFAULT NULL,
  `sifre` varchar(255) NOT NULL,
  `yetki` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`kullanici_id`),
  UNIQUE KEY `eposta` (`eposta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- Faaliyetler tablosu (ana tablo)
CREATE TABLE `faaliyetler` (
  `faaliyet_id` int(11) NOT NULL AUTO_INCREMENT,
  `faaliyet_icerigi` varchar(255) NOT NULL,
  `icerik_detayi` text DEFAULT NULL,
  `faaliyet_turu` varchar(255) NOT NULL,
  `baslangic_tarihi` date NOT NULL,
  `bitis_tarihi` date NOT NULL,
  `katilimci_sayisi` int(10) unsigned DEFAULT NULL,
  `faaliyet_yeri` varchar(255) NOT NULL,
  `yil` int(11) NOT NULL,
  `donem` varchar(20) NOT NULL,
  `olusturma_tarihi` timestamp NULL DEFAULT current_timestamp(),
  `kullanici_id` int(11) DEFAULT NULL,
  `kapsam_id` int(11) DEFAULT NULL,
  `fayda_id` int(11) DEFAULT NULL,
  `dil_id` int(11) DEFAULT NULL,
  `birim_id` int(11) DEFAULT NULL,
  `ska_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`faaliyet_id`),
  KEY `fk_faaliyet_kapsam` (`kapsam_id`),
  KEY `fk_faaliyet_fayda` (`fayda_id`),
  KEY `fk_faaliyet_dil` (`dil_id`),
  KEY `fk_faaliyet_birim` (`birim_id`),
  KEY `idx_tarih` (`baslangic_tarihi`,`bitis_tarihi`),
  KEY `ska_id` (`ska_id`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `faaliyetler_ibfk_1` FOREIGN KEY (`ska_id`) REFERENCES `ska` (`ska_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `faaliyetler_ibfk_2` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`kullanici_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_faaliyet_birim` FOREIGN KEY (`birim_id`) REFERENCES `birimler` (`birim_id`),
  CONSTRAINT `fk_faaliyet_dil` FOREIGN KEY (`dil_id`) REFERENCES `diller` (`dil_id`),
  CONSTRAINT `fk_faaliyet_fayda` FOREIGN KEY (`fayda_id`) REFERENCES `toplumsal_faydalar` (`fayda_id`),
  CONSTRAINT `fk_faaliyet_kapsam` FOREIGN KEY (`kapsam_id`) REFERENCES `kapsamlar` (`kapsam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;


-- Ornek veriler

-- Birimler
INSERT INTO `birimler` (`birim_adi`, `birim_kodu`) VALUES
('Bilgisayar Mühendisliği', 'BM'),
('Elektrik-Elektronik Mühendisliği', 'EEM');

-- Diller
INSERT INTO `diller` (`dil_adi`, `dil_kodu`) VALUES
('Türkçe', 'TR'),
('İngilizce', 'EN');

-- Kapsamlar
INSERT INTO `kapsamlar` (`kapsam_adi`) VALUES
('Ulusal'),
('Uluslararası'),
('Yerel');

-- Toplumsal faydalar
INSERT INTO `toplumsal_faydalar` (`fayda_adi`) VALUES
('Eğitim'),
('Sağlık'),
('Çevre');

-- SKA (Surdurulebilir Kalkinma Amaclari)
INSERT INTO `ska` (`ska_aciklama`) VALUES
('1 - Yoksulluğa Son'),
('2 - Açlığa Son'),
('3 - Sağlık ve Kaliteli Yaşam'),
('4 - Nitelikli Eğitim'),
('5 - Toplumsal Cinsiyet Eşitliği'),
('6 - Temiz Su ve Sanitasyon'),
('7 - Erişilebilir ve Temiz Enerji'),
('8 - İnsana Yakışır İş ve Ekonomik Büyüme'),
('9 - Sanayi, Yenilikçilik ve Altyapı'),
('10 - Eşitsizliklerin Azaltılması'),
('11 - Sürdürülebilir Şehirler ve Topluluklar'),
('12 - Sorumlu Üretim ve Tüketim'),
('13 - İklim Eylemi'),
('14 - Sudaki Yaşam'),
('15 - Karasal Yaşam'),
('16 - Barış, Adalet ve Güçlü Kurumlar'),
('17 - Amaçlar İçin Ortaklıklar');

-- Not: Bilinçli olarak `kullanicilar` tablosuna varsayilan/hazir bir kayit eklenmemistir.
-- Ilk yonetici (admin) hesabini elle olusturmak icin README.md -> "Kurulum" -> 5. adimi izleyin.
