<?php
// faaliyet islemleri
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// giris kontrolu
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.'], 401);
}

$baglanti = get_db_connection();
$metod = $_SERVER['REQUEST_METHOD'];
$islem = $_GET['action'] ?? 'list';

switch ($metod) {
    case 'GET':
        if ($islem === 'get' && isset($_GET['id'])) {
            getFaaliyetById($baglanti, $_GET['id']);
        } elseif ($islem === 'upcoming') {
            getUpcomingActivities($baglanti);
        } elseif ($islem === 'ongoing') {
            getOngoingActivities($baglanti);
        } elseif ($islem === 'recent') {
            getRecentActivities($baglanti);
        } elseif ($islem === 'stats') {
            getStats($baglanti);
        } elseif ($islem === 'calendar') {
            getCalendarEvents($baglanti);
        } else {
            getFaaliyetler($baglanti);
        }
        break;
    
    case 'POST':
        // CSRF dogrulama
        verify_csrf_or_die();
        
        $veri = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if ($islem === 'delete' && isset($veri['id'])) {
            deleteFaaliyet($baglanti, $veri['id']);
        } elseif ($islem === 'update' && isset($veri['faaliyet_id'])) {
            updateFaaliyet($baglanti, $veri);
        } else {
            createFaaliyet($baglanti, $veri);
        }
        break;
    
    default:
        json_response(['success' => false, 'message' => 'Geçersiz istek.'], 400);
}

// istatistikler (dashboard icin) - tek SQL sorgusunda birlesitirildi
function getStats($baglanti) {
    $sql = "SELECT 
                COUNT(*) as toplam,
                COUNT(CASE WHEN baslangic_tarihi > CURDATE() THEN 1 END) as yaklasan,
                COUNT(CASE WHEN baslangic_tarihi <= CURDATE() AND bitis_tarihi >= CURDATE() THEN 1 END) as devam_eden,
                COUNT(CASE WHEN bitis_tarihi >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND bitis_tarihi <= CURDATE() THEN 1 END) as bu_ay_tamamlanan
            FROM faaliyetler";
    
    $sonuc = $baglanti->query($sql);
    $stats = $sonuc ? $sonuc->fetch_assoc() : [
        'toplam' => 0,
        'yaklasan' => 0,
        'devam_eden' => 0,
        'bu_ay_tamamlanan' => 0
    ];
    
    json_response([
        'success' => true,
        'data' => [
            'toplam' => (int)($stats['toplam'] ?? 0),
            'yaklasan' => (int)($stats['yaklasan'] ?? 0),
            'devam_eden' => (int)($stats['devam_eden'] ?? 0),
            'bu_ay_tamamlanan' => (int)($stats['bu_ay_tamamlanan'] ?? 0)
        ]
    ]);
}

// faaliyetleri getir (sayfalama + filtre)
function getFaaliyetler($baglanti) {
    $sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
    $offset = ($sayfa - 1) * $limit;
    
    $kosullar = [];
    $parametreler = [];
    $tipler = '';
    
    // tarih filtresi
    if (!empty($_GET['baslangic_tarihi']) && !empty($_GET['bitis_tarihi'])) {
        $kosullar[] = 'f.baslangic_tarihi >= ? AND f.bitis_tarihi <= ?';
        $parametreler[] = $_GET['baslangic_tarihi'];
        $parametreler[] = $_GET['bitis_tarihi'];
        $tipler .= 'ss';
    } elseif (!empty($_GET['baslangic_tarihi'])) {
        $kosullar[] = 'f.baslangic_tarihi >= ?';
        $parametreler[] = $_GET['baslangic_tarihi'];
        $tipler .= 's';
    } elseif (!empty($_GET['bitis_tarihi'])) {
        $kosullar[] = 'f.bitis_tarihi <= ?';
        $parametreler[] = $_GET['bitis_tarihi'];
        $tipler .= 's';
    }
    
    // arama filtresi
    if (!empty($_GET['search'])) {
        $kosullar[] = 'f.faaliyet_icerigi LIKE ?';
        $parametreler[] = '%' . trim($_GET['search']) . '%';
        $tipler .= 's';
    }
    
    $filtreSQL = $kosullar ? 'WHERE ' . implode(' AND ', $kosullar) : '';
    
    // Toplam kayit sayisi
    $sayimSQL = "SELECT COUNT(*) as toplam FROM faaliyetler f $filtreSQL";
    if ($parametreler) {
        $sayim_sorgusu = $baglanti->prepare($sayimSQL);
        $sayim_sorgusu->bind_param($tipler, ...$parametreler);
        $sayim_sorgusu->execute();
        $toplam = (int)$sayim_sorgusu->get_result()->fetch_assoc()['toplam'];
    } else {
        $toplamRes = $baglanti->query($sayimSQL);
        $toplam = $toplamRes ? (int)$toplamRes->fetch_assoc()['toplam'] : 0;
    }
    
    // Ana sorgu
    $sql = "SELECT f.*, k.kapsam_adi, tf.fayda_adi, d.dil_adi, b.birim_adi, 
                   s.ska_aciklama, u.ad, u.soyad
            FROM faaliyetler f
            LEFT JOIN kapsamlar k ON f.kapsam_id = k.kapsam_id
            LEFT JOIN toplumsal_faydalar tf ON f.fayda_id = tf.fayda_id
            LEFT JOIN diller d ON f.dil_id = d.dil_id
            LEFT JOIN birimler b ON f.birim_id = b.birim_id
            LEFT JOIN ska s ON f.ska_id = s.ska_id
            LEFT JOIN kullanicilar u ON f.kullanici_id = u.kullanici_id
            $filtreSQL
            ORDER BY f.olusturma_tarihi DESC
            LIMIT ? OFFSET ?";
    
    $sorgu_parametreler = $parametreler;
    $sorgu_parametreler[] = $limit;
    $sorgu_parametreler[] = $offset;
    $sorgu_tipler = $tipler . 'ii';
    
    $sorgu = $baglanti->prepare($sql);
    $sorgu->bind_param($sorgu_tipler, ...$sorgu_parametreler);
    $sorgu->execute();
    $veriler = $sorgu->get_result()->fetch_all(MYSQLI_ASSOC);
    
    json_response([
        'success' => true,
        'data' => $veriler,
        'pagination' => calculate_pagination($toplam, $sayfa, $limit)
    ]);
}

// tek faaliyet getir
function getFaaliyetById($baglanti, $id) {
    $id = (int)$id;
    $sorgu = $baglanti->prepare("SELECT f.*, k.kapsam_adi, tf.fayda_adi, d.dil_adi, b.birim_adi, s.ska_aciklama
                            FROM faaliyetler f
                            LEFT JOIN kapsamlar k ON f.kapsam_id = k.kapsam_id
                            LEFT JOIN toplumsal_faydalar tf ON f.fayda_id = tf.fayda_id
                            LEFT JOIN diller d ON f.dil_id = d.dil_id
                            LEFT JOIN birimler b ON f.birim_id = b.birim_id
                            LEFT JOIN ska s ON f.ska_id = s.ska_id
                            WHERE f.faaliyet_id = ?");
    $sorgu->bind_param("i", $id);
    $sorgu->execute();
    $sonuc = $sorgu->get_result();
    
    if ($sonuc->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Faaliyet bulunamadı.'], 404);
    }
    
    json_response(['success' => true, 'data' => $sonuc->fetch_assoc()]);
}

// widget ortak sorgulama fonksiyonu (unnecessary JOINs removed for speed)
function getWidgetActivities($baglanti, $whereCondition, $orderBy = 'baslangic_tarihi ASC') {
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 4;
    $sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $baslangic = ($sayfa - 1) * $limit;
    
    $sql = "SELECT faaliyet_id, faaliyet_icerigi, faaliyet_yeri, baslangic_tarihi, bitis_tarihi
            FROM faaliyetler
            WHERE {$whereCondition}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?";
    
    $sorgu = $baglanti->prepare($sql);
    $sorgu->bind_param("ii", $limit, $baslangic);
    $sorgu->execute();
    $veriler = $sorgu->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $countSql = "SELECT COUNT(*) as toplam FROM faaliyetler WHERE {$whereCondition}";
    $toplamRes = $baglanti->query($countSql);
    $toplam = $toplamRes ? (int)$toplamRes->fetch_assoc()['toplam'] : 0;
    
    json_response([
        'success' => true,
        'data' => $veriler,
        'pagination' => calculate_pagination($toplam, $sayfa, $limit)
    ]);
}

// yaklasan etkinlikler
function getUpcomingActivities($baglanti) {
    getWidgetActivities($baglanti, "baslangic_tarihi > CURDATE()", "baslangic_tarihi ASC");
}

// devam eden faaliyetler
function getOngoingActivities($baglanti) {
    getWidgetActivities($baglanti, "baslangic_tarihi <= CURDATE() AND bitis_tarihi >= CURDATE()", "baslangic_tarihi ASC");
}

// son tamamlanan faaliyetler
function getRecentActivities($baglanti) {
    getWidgetActivities($baglanti, "bitis_tarihi >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND bitis_tarihi <= CURDATE()", "bitis_tarihi DESC");
}

// yeni faaliyet ekle — validasyonlu
function createFaaliyet($baglanti, $veri) {
    $zorunlu_alanlar = ['yil', 'donem', 'faaliyet_icerigi', 'baslangic_tarihi', 'bitis_tarihi', 'faaliyet_yeri', 'faaliyet_turu'];
    $kontrol = check_required_fields($veri, $zorunlu_alanlar);
    
    if (!$kontrol['valid']) {
        json_response(['success' => false, 'message' => 'Zorunlu alanlar eksik: ' . implode(', ', $kontrol['missing'])], 400);
    }
    
    // input uzunluk kontrolleri
    if (!validate_length($veri['faaliyet_icerigi'], 1, 255)) {
        json_response(['success' => false, 'message' => 'Faaliyet içeriği en fazla 255 karakter olabilir.'], 400);
    }
    if (!validate_length($veri['donem'], 1, 20)) {
        json_response(['success' => false, 'message' => 'Dönem en fazla 20 karakter olabilir.'], 400);
    }
    if (!validate_length($veri['faaliyet_yeri'], 1, 255)) {
        json_response(['success' => false, 'message' => 'Faaliyet yeri en fazla 255 karakter olabilir.'], 400);
    }
    if (!validate_length($veri['faaliyet_turu'], 1, 255)) {
        json_response(['success' => false, 'message' => 'Faaliyet türü en fazla 255 karakter olabilir.'], 400);
    }
    
    // yil kontrolu
    $yil = (int)$veri['yil'];
    if ($yil < 2000 || $yil > 2100) {
        json_response(['success' => false, 'message' => 'Yıl 2000-2100 arasında olmalıdır.'], 400);
    }
    
    if (!is_valid_date_range($veri['baslangic_tarihi'], $veri['bitis_tarihi'])) {
        json_response(['success' => false, 'message' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.'], 400);
    }
    
    // katilimci sayisi kontrolu
    $katilimci_sayisi = !empty($veri['katilimci_sayisi']) ? (int)$veri['katilimci_sayisi'] : 0;
    if ($katilimci_sayisi < 0) {
        json_response(['success' => false, 'message' => 'Katılımcı sayısı negatif olamaz.'], 400);
    }
    
    $kullanici_id = $_SESSION['kullanici_id'];
    
    // girdileri temizle
    $faaliyet_icerigi = sanitize_input($veri['faaliyet_icerigi']);
    $donem = sanitize_input($veri['donem']);
    $faaliyet_yeri = sanitize_input($veri['faaliyet_yeri']);
    $faaliyet_turu = sanitize_input($veri['faaliyet_turu']);
    $icerik_detayi = !empty($veri['icerik_detayi']) ? sanitize_input($veri['icerik_detayi']) : null;
    
    // opsiyonel alanlari ayarla
    $kapsam_id = !empty($veri['kapsam_id']) ? (int)$veri['kapsam_id'] : null;
    $fayda_id = !empty($veri['fayda_id']) ? (int)$veri['fayda_id'] : null;
    $dil_id = !empty($veri['dil_id']) ? (int)$veri['dil_id'] : null;
    $birim_id = !empty($veri['birim_id']) ? (int)$veri['birim_id'] : null;
    $ska_id = !empty($veri['ska_id']) ? (int)$veri['ska_id'] : null;
    
    $sorgu = $baglanti->prepare("INSERT INTO faaliyetler (yil, donem, faaliyet_icerigi, icerik_detayi, baslangic_tarihi, bitis_tarihi, 
                            kapsam_id, fayda_id, dil_id, birim_id, ska_id, katilimci_sayisi, faaliyet_yeri, faaliyet_turu, kullanici_id)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $sorgu->bind_param("isssssiiiiiissi",
        $yil, $donem, $faaliyet_icerigi, $icerik_detayi,
        $veri['baslangic_tarihi'], $veri['bitis_tarihi'],
        $kapsam_id, $fayda_id, $dil_id, $birim_id, $ska_id, $katilimci_sayisi,
        $faaliyet_yeri, $faaliyet_turu, $kullanici_id
    );
    
    if ($sorgu->execute()) {
        $yeni_id = $baglanti->insert_id;
        kaydet_ek_kategoriler($baglanti, $yeni_id, $veri);
        json_response(['success' => true, 'message' => 'Faaliyet başarıyla oluşturuldu.', 'data' => ['faaliyet_id' => $yeni_id]], 201);
    } else {
        json_response(['success' => false, 'message' => 'Faaliyet oluşturulurken hata oluştu.'], 500);
    }
}

// faaliyet guncelle — yetki ve zorunlu alan kontrollu
function updateFaaliyet($baglanti, $veri) {
    if (empty($veri['faaliyet_id']) || !is_numeric($veri['faaliyet_id'])) {
        json_response(['success' => false, 'message' => 'Geçersiz faaliyet ID.'], 400);
    }

    $id = (int)$veri['faaliyet_id'];
    
    // yetkilendirme: admin her seyi, normal kullanici sadece kendininkini
    if (!is_admin()) {
        $yetki_sorgu = $baglanti->prepare("SELECT kullanici_id FROM faaliyetler WHERE faaliyet_id = ?");
        $yetki_sorgu->bind_param("i", $id);
        $yetki_sorgu->execute();
        $faaliyet = $yetki_sorgu->get_result()->fetch_assoc();
        
        if (!$faaliyet || $faaliyet['kullanici_id'] != $_SESSION['kullanici_id']) {
            json_response(['success' => false, 'message' => 'Bu faaliyeti düzenleme yetkiniz yok.'], 403);
        }
    }
    
    $zorunlu_alanlar = ['yil', 'donem', 'faaliyet_icerigi', 'baslangic_tarihi', 'bitis_tarihi', 'faaliyet_yeri', 'faaliyet_turu'];
    $kontrol = check_required_fields($veri, $zorunlu_alanlar);
    
    if (!$kontrol['valid']) {
        json_response(['success' => false, 'message' => 'Zorunlu alanlar eksik: ' . implode(', ', $kontrol['missing'])], 400);
    }
    
    // input uzunluk kontrolleri
    if (!validate_length($veri['faaliyet_icerigi'], 1, 255)) {
        json_response(['success' => false, 'message' => 'Faaliyet içeriği en fazla 255 karakter olabilir.'], 400);
    }
    if (!validate_length($veri['donem'], 1, 20)) {
        json_response(['success' => false, 'message' => 'Dönem en fazla 20 karakter olabilir.'], 400);
    }
    if (!validate_length($veri['faaliyet_yeri'], 1, 255)) {
        json_response(['success' => false, 'message' => 'Faaliyet yeri en fazla 255 karakter olabilir.'], 400);
    }
    if (!validate_length($veri['faaliyet_turu'], 1, 255)) {
        json_response(['success' => false, 'message' => 'Faaliyet türü en fazla 255 karakter olabilir.'], 400);
    }
    
    // yil kontrolu
    $yil = (int)$veri['yil'];
    if ($yil < 2000 || $yil > 2100) {
        json_response(['success' => false, 'message' => 'Yıl 2000-2100 arasında olmalıdır.'], 400);
    }
    
    if (!is_valid_date_range($veri['baslangic_tarihi'], $veri['bitis_tarihi'])) {
        json_response(['success' => false, 'message' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.'], 400);
    }
    
    // katilimci sayisi kontrolu
    $katilimci_sayisi = !empty($veri['katilimci_sayisi']) ? (int)$veri['katilimci_sayisi'] : 0;
    if ($katilimci_sayisi < 0) {
        json_response(['success' => false, 'message' => 'Katılımcı sayısı negatif olamaz.'], 400);
    }
    
    // girdileri temizle
    $faaliyet_icerigi = sanitize_input($veri['faaliyet_icerigi']);
    $donem = sanitize_input($veri['donem']);
    $faaliyet_yeri = sanitize_input($veri['faaliyet_yeri']);
    $faaliyet_turu = sanitize_input($veri['faaliyet_turu']);
    $icerik_detayi = !empty($veri['icerik_detayi']) ? sanitize_input($veri['icerik_detayi']) : null;
    
    // opsiyonel alanlar
    $kapsam_id = !empty($veri['kapsam_id']) ? (int)$veri['kapsam_id'] : null;
    $fayda_id = !empty($veri['fayda_id']) ? (int)$veri['fayda_id'] : null;
    $dil_id = !empty($veri['dil_id']) ? (int)$veri['dil_id'] : null;
    $birim_id = !empty($veri['birim_id']) ? (int)$veri['birim_id'] : null;
    $ska_id = !empty($veri['ska_id']) ? (int)$veri['ska_id'] : null;
    
    $sorgu = $baglanti->prepare("UPDATE faaliyetler SET yil=?, donem=?, faaliyet_icerigi=?, icerik_detayi=?,
                            baslangic_tarihi=?, bitis_tarihi=?, kapsam_id=?, fayda_id=?, dil_id=?,
                            birim_id=?, ska_id=?, katilimci_sayisi=?, faaliyet_yeri=?, faaliyet_turu=?
                            WHERE faaliyet_id=?");
    
    $sorgu->bind_param("isssssiiiiiissi",
        $yil, $donem, $faaliyet_icerigi, $icerik_detayi,
        $veri['baslangic_tarihi'], $veri['bitis_tarihi'],
        $kapsam_id, $fayda_id, $dil_id, $birim_id, $ska_id, $katilimci_sayisi,
        $faaliyet_yeri, $faaliyet_turu, $id
    );
    
    if ($sorgu->execute()) {
        kaydet_ek_kategoriler($baglanti, $id, $veri);
        json_response(['success' => true, 'message' => 'Faaliyet başarıyla güncellendi.']);
    } else {
        json_response(['success' => false, 'message' => 'Faaliyet güncellenirken hata oluştu.'], 500);
    }
}

// Ek kategori secimlerini pivot tabloya kaydet
// Form alanları: ek_kat_TIP_ID => deger_id
function kaydet_ek_kategoriler($baglanti, $faaliyet_id, $veri) {
    // Once mevcut secimler temizle
    $temizle = @$baglanti->prepare("DELETE FROM faaliyet_ek_kategoriler WHERE faaliyet_id = ?");
    if (!$temizle) return; // Tablo yoksa sessizce devam et (migration calistirilmadiysa)
    $temizle->bind_param("i", $faaliyet_id);
    $temizle->execute();

    // Yeni secimler ekle
    $ekle = $baglanti->prepare("INSERT IGNORE INTO faaliyet_ek_kategoriler (faaliyet_id, deger_id) VALUES (?, ?)");
    if (!$ekle) return;
    foreach ($veri as $anahtar => $deger) {
        if (substr($anahtar, 0, 7) === 'ek_kat_' && !empty($deger)) {
            $deger_id = (int)$deger;
            if ($deger_id > 0) {
                $ekle->bind_param("ii", $faaliyet_id, $deger_id);
                $ekle->execute();
            }
        }
    }
}

// faaliyet sil — yetki kontrollu
function deleteFaaliyet($baglanti, $id) {
    $id = (int)$id;
    
    if ($id <= 0) {
        json_response(['success' => false, 'message' => 'Geçersiz faaliyet ID.'], 400);
    }
    
    // yetkilendirme: admin her seyi, normal kullanici sadece kendininkini
    if (!is_admin()) {
        $yetki_sorgu = $baglanti->prepare("SELECT kullanici_id FROM faaliyetler WHERE faaliyet_id = ?");
        $yetki_sorgu->bind_param("i", $id);
        $yetki_sorgu->execute();
        $faaliyet = $yetki_sorgu->get_result()->fetch_assoc();
        
        if (!$faaliyet || $faaliyet['kullanici_id'] != $_SESSION['kullanici_id']) {
            json_response(['success' => false, 'message' => 'Bu faaliyeti silme yetkiniz yok.'], 403);
        }
    }
    
    $sorgu = $baglanti->prepare("DELETE FROM faaliyetler WHERE faaliyet_id = ?");
    $sorgu->bind_param("i", $id);
    
    if ($sorgu->execute()) {
        json_response(['success' => true, 'message' => 'Faaliyet başarıyla silindi.']);
    } else {
        json_response(['success' => false, 'message' => 'Faaliyet silinirken hata oluştu.'], 500);
    }
}

// Takvim İçin Veri Hazırlama Fonksiyonu
function getCalendarEvents($baglanti) {
    $sql = "SELECT faaliyet_id as id, faaliyet_icerigi as title, baslangic_tarihi as start, bitis_tarihi as end FROM faaliyetler";
    $sonuc = $baglanti->query($sql);
    $etkinlikler = $sonuc ? $sonuc->fetch_all(MYSQLI_ASSOC) : [];
    
    $takvim_verisi = [];
    $bugun = date('Y-m-d');
    
    foreach ($etkinlikler as $row) {
        if (!empty($row['end']) && $row['end'] < $bugun) {
            $row['color'] = '#e74c3c'; // Geçmiş etkinlikler (Kırmızı)
        } else {
            $row['color'] = '#2ecc71'; // Yaklaşan/Devam eden (Yeşil)
        }
        if (!empty($row['end'])) {
            $row['end'] = date('Y-m-d', strtotime($row['end'] . ' +1 day'));
        }
        $takvim_verisi[] = $row;
    }
    
    json_response($takvim_verisi);
}