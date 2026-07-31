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

// istatistikler (dashboard icin)
function getStats($baglanti) {
    $toplam = $baglanti->query("SELECT COUNT(*) as toplam FROM faaliyetler")->fetch_assoc()['toplam'];
    $yaklasan = $baglanti->query("SELECT COUNT(*) as toplam FROM faaliyetler WHERE baslangic_tarihi > CURDATE()")->fetch_assoc()['toplam'];
    $devam_eden = $baglanti->query("SELECT COUNT(*) as toplam FROM faaliyetler WHERE baslangic_tarihi <= CURDATE() AND bitis_tarihi >= CURDATE()")->fetch_assoc()['toplam'];
    $bu_ay = $baglanti->query("SELECT COUNT(*) as toplam FROM faaliyetler WHERE bitis_tarihi >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND bitis_tarihi <= CURDATE()")->fetch_assoc()['toplam'];
    
    json_response([
        'success' => true,
        'data' => [
            'toplam' => (int)$toplam,
            'yaklasan' => (int)$yaklasan,
            'devam_eden' => (int)$devam_eden,
            'bu_ay_tamamlanan' => (int)$bu_ay
        ]
    ]);
}

// faaliyetleri getir (sayfalama + filtre)
function getFaaliyetler($baglanti) {
    $sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
    $baslangic = ($sayfa - 1) * $limit;
    
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
        $parametreler[] = '%' . $_GET['search'] . '%';
        $tipler .= 's';
    }
    
    $filtreSQL = $kosullar ? 'WHERE ' . implode(' AND ', $kosullar) : '';
    
    // ana sorgu
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
    
    $sorgu = $baglanti->prepare($sql);
    $parametreler[] = $limit;
    $parametreler[] = $baslangic;
    $tipler .= 'ii';
    
    if ($parametreler) {
        $sorgu->bind_param($tipler, ...$parametreler);
    }
    
    $sorgu->execute();
    $sonuc = $sorgu->get_result();
    $veriler = $sonuc->fetch_all(MYSQLI_ASSOC);
    
    // toplam kayit sayisi
    $sayimSQL = "SELECT COUNT(*) as toplam FROM faaliyetler f $filtreSQL";
    $sayim_parametreler = array_slice($parametreler, 0, -2);
    $sayim_tipler = substr($tipler, 0, -2);
    
    if ($sayim_parametreler) {
        $sayim_sorgusu = $baglanti->prepare($sayimSQL);
        $sayim_sorgusu->bind_param($sayim_tipler, ...$sayim_parametreler);
        $sayim_sorgusu->execute();
        $toplam = $sayim_sorgusu->get_result()->fetch_assoc()['toplam'];
    } else {
        $toplam = $baglanti->query($sayimSQL)->fetch_assoc()['toplam'];
    }
    
    json_response([
        'success' => true,
        'data' => $veriler,
        'pagination' => [
            'total' => (int)$toplam,
            'page' => $sayfa,
            'limit' => $limit,
            'totalPages' => ceil($toplam / $limit)
        ]
    ]);
}

// tek faaliyet getir
function getFaaliyetById($baglanti, $id) {
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

// yaklasan etkinlikler
function getUpcomingActivities($baglanti) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;
    $sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $baslangic = ($sayfa - 1) * $limit;
    
    $sql = "SELECT f.*, k.kapsam_adi, tf.fayda_adi, d.dil_adi, b.birim_adi, s.ska_aciklama
            FROM faaliyetler f
            LEFT JOIN kapsamlar k ON f.kapsam_id = k.kapsam_id
            LEFT JOIN toplumsal_faydalar tf ON f.fayda_id = tf.fayda_id
            LEFT JOIN diller d ON f.dil_id = d.dil_id
            LEFT JOIN birimler b ON f.birim_id = b.birim_id
            LEFT JOIN ska s ON f.ska_id = s.ska_id
            WHERE f.baslangic_tarihi > CURDATE()
            ORDER BY f.baslangic_tarihi ASC
            LIMIT ? OFFSET ?";
    
    $sorgu = $baglanti->prepare($sql);
    $sorgu->bind_param("ii", $limit, $baslangic);
    $sorgu->execute();
    $veriler = $sorgu->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $toplam = $baglanti->query("SELECT COUNT(*) as toplam FROM faaliyetler 
                           WHERE baslangic_tarihi > CURDATE()")->fetch_assoc()['toplam'];
    
    json_response([
        'success' => true,
        'data' => $veriler,
        'pagination' => ['total' => (int)$toplam, 'page' => $sayfa, 'limit' => $limit, 'totalPages' => ceil($toplam / $limit)]
    ]);
}

// devam eden faaliyetler
function getOngoingActivities($baglanti) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;
    $sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $baslangic = ($sayfa - 1) * $limit;
    
    $sql = "SELECT f.*, k.kapsam_adi, tf.fayda_adi, d.dil_adi, b.birim_adi, s.ska_aciklama
            FROM faaliyetler f
            LEFT JOIN kapsamlar k ON f.kapsam_id = k.kapsam_id
            LEFT JOIN toplumsal_faydalar tf ON f.fayda_id = tf.fayda_id
            LEFT JOIN diller d ON f.dil_id = d.dil_id
            LEFT JOIN birimler b ON f.birim_id = b.birim_id
            LEFT JOIN ska s ON f.ska_id = s.ska_id
            WHERE f.baslangic_tarihi <= CURDATE() AND f.bitis_tarihi >= CURDATE()
            ORDER BY f.baslangic_tarihi ASC
            LIMIT ? OFFSET ?";
    
    $sorgu = $baglanti->prepare($sql);
    $sorgu->bind_param("ii", $limit, $baslangic);
    $sorgu->execute();
    $veriler = $sorgu->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $toplam = $baglanti->query("SELECT COUNT(*) as toplam FROM faaliyetler 
                           WHERE baslangic_tarihi <= CURDATE() AND bitis_tarihi >= CURDATE()")->fetch_assoc()['toplam'];
    
    json_response([
        'success' => true,
        'data' => $veriler,
        'pagination' => ['total' => (int)$toplam, 'page' => $sayfa, 'limit' => $limit, 'totalPages' => ceil($toplam / $limit)]
    ]);
}

// son tamamlanan faaliyetler
function getRecentActivities($baglanti) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 4;
    $sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $baslangic = ($sayfa - 1) * $limit;
    
    $sql = "SELECT f.*, k.kapsam_adi, tf.fayda_adi, d.dil_adi, b.birim_adi, s.ska_aciklama
            FROM faaliyetler f
            LEFT JOIN kapsamlar k ON f.kapsam_id = k.kapsam_id
            LEFT JOIN toplumsal_faydalar tf ON f.fayda_id = tf.fayda_id
            LEFT JOIN diller d ON f.dil_id = d.dil_id
            LEFT JOIN birimler b ON f.birim_id = b.birim_id
            LEFT JOIN ska s ON f.ska_id = s.ska_id
            WHERE f.bitis_tarihi >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND f.bitis_tarihi <= CURDATE()
            ORDER BY f.bitis_tarihi DESC
            LIMIT ? OFFSET ?";
    
    $sorgu = $baglanti->prepare($sql);
    $sorgu->bind_param("ii", $limit, $baslangic);
    $sorgu->execute();
    $veriler = $sorgu->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $toplam = $baglanti->query("SELECT COUNT(*) as toplam FROM faaliyetler 
                           WHERE bitis_tarihi >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND bitis_tarihi <= CURDATE()")->fetch_assoc()['toplam'];
    
    json_response([
        'success' => true,
        'data' => $veriler,
        'pagination' => ['total' => (int)$toplam, 'page' => $sayfa, 'limit' => $limit, 'totalPages' => ceil($toplam / $limit)]
    ]);
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
        json_response(['success' => true, 'message' => 'Faaliyet başarıyla oluşturuldu.', 'data' => ['faaliyet_id' => $baglanti->insert_id]], 201);
    } else {
        json_response(['success' => false, 'message' => 'Faaliyet oluşturulurken hata oluştu.'], 500);
    }
}

// faaliyet guncelle — yetki kontrollu
function updateFaaliyet($baglanti, $veri) {
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
    
    // input uzunluk kontrolleri
    if (!empty($veri['faaliyet_icerigi']) && !validate_length($veri['faaliyet_icerigi'], 1, 255)) {
        json_response(['success' => false, 'message' => 'Faaliyet içeriği en fazla 255 karakter olabilir.'], 400);
    }
    if (!empty($veri['donem']) && !validate_length($veri['donem'], 1, 20)) {
        json_response(['success' => false, 'message' => 'Dönem en fazla 20 karakter olabilir.'], 400);
    }
    
    // tarih kontrolu
    if (!empty($veri['baslangic_tarihi']) && !empty($veri['bitis_tarihi'])) {
        if (!is_valid_date_range($veri['baslangic_tarihi'], $veri['bitis_tarihi'])) {
            json_response(['success' => false, 'message' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.'], 400);
        }
    }
    
    // katilimci sayisi kontrolu
    $katilimci_sayisi = !empty($veri['katilimci_sayisi']) ? (int)$veri['katilimci_sayisi'] : 0;
    if ($katilimci_sayisi < 0) {
        json_response(['success' => false, 'message' => 'Katılımcı sayısı negatif olamaz.'], 400);
    }
    
    // girdileri temizle
    $faaliyet_icerigi = sanitize_input($veri['faaliyet_icerigi'] ?? '');
    $donem = sanitize_input($veri['donem'] ?? '');
    $faaliyet_yeri = sanitize_input($veri['faaliyet_yeri'] ?? '');
    $faaliyet_turu = sanitize_input($veri['faaliyet_turu'] ?? '');
    $icerik_detayi = !empty($veri['icerik_detayi']) ? sanitize_input($veri['icerik_detayi']) : null;
    
    // opsiyonel alanlar
    $kapsam_id = !empty($veri['kapsam_id']) ? (int)$veri['kapsam_id'] : null;
    $fayda_id = !empty($veri['fayda_id']) ? (int)$veri['fayda_id'] : null;
    $dil_id = !empty($veri['dil_id']) ? (int)$veri['dil_id'] : null;
    $birim_id = !empty($veri['birim_id']) ? (int)$veri['birim_id'] : null;
    $ska_id = !empty($veri['ska_id']) ? (int)$veri['ska_id'] : null;
    
    $yil = (int)($veri['yil'] ?? date('Y'));
    
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
        json_response(['success' => true, 'message' => 'Faaliyet başarıyla güncellendi.']);
    } else {
        json_response(['success' => false, 'message' => 'Faaliyet güncellenirken hata oluştu.'], 500);
    }
}

// faaliyet sil — yetki kontrollu
function deleteFaaliyet($baglanti, $id) {
    $id = (int)$id;
    
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
} //Takvim İçin Veri Hazırlama Fonksiyonu
function getCalendarEvents($baglanti) {
    $sql = "SELECT faaliyet_id as id, faaliyet_icerigi as title, baslangic_tarihi as start, bitis_tarihi as end FROM faaliyetler";
    $sonuc = $baglanti->query($sql);
    $etkinlikler = $sonuc->fetch_all(MYSQLI_ASSOC);
    
    $takvim_verisi = [];
    $bugun = date('Y-m-d');
    
    foreach($etkinlikler as $row) {
        if ($row['end'] < $bugun) {
            $row['color'] = '#e74c3c'; // Geçmiş etkinlikler (Kırmızı)
        } else {
            $row['color'] = '#2ecc71'; // Yaklaşan etkinlikler (Yeşil)
        }
        if (!empty($row['end'])) {
            $row['end'] = date('Y-m-d', strtotime($row['end'] . ' +1 day'));
        }
        $takvim_verisi[] = $row;
    }
    
    echo json_encode($takvim_verisi);
    exit;
}

?>