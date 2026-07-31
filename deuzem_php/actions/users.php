<?php
// kullanici islemleri (sadece admin)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_admin();

$baglanti = get_db_connection();
$metod = $_SERVER['REQUEST_METHOD'];
$islem = $_GET['action'] ?? 'list';

switch ($metod) {
    case 'GET':
        if ($islem === 'get' && isset($_GET['id'])) {
            getUserById($baglanti, (int)$_GET['id']);
        } else {
            getUsers($baglanti);
        }
        break;
    case 'POST':
        // CSRF dogrulama
        verify_csrf_or_die();
        
        $veri = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if ($islem === 'delete' && isset($veri['id'])) {
            deleteUser($baglanti, $veri['id']);
        } elseif ($islem === 'activate' && isset($veri['id'])) {
            activateUser($baglanti, $veri['id']);
        } elseif ($islem === 'update' && isset($veri['kullanici_id'])) {
            updateUser($baglanti, $veri);
        } else {
            createUser($baglanti, $veri);
        }
        break;
    default:
        json_response(['success' => false, 'message' => 'Geçersiz istek.'], 400);
}

// kullanicilari listele
function getUsers($baglanti) {
    $sonuc = $baglanti->query("SELECT kullanici_id, ad, soyad, eposta, yetki FROM kullanicilar ORDER BY kullanici_id DESC");
    json_response(['success' => true, 'data' => $sonuc->fetch_all(MYSQLI_ASSOC)]);
}

// tek kullanici getir
function getUserById($baglanti, $id) {
    $sorgu = $baglanti->prepare("SELECT kullanici_id, ad, soyad, eposta, yetki FROM kullanicilar WHERE kullanici_id = ?");
    $sorgu->bind_param("i", $id);
    $sorgu->execute();
    $sonuc = $sorgu->get_result();
    
    if ($sonuc->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Kullanıcı bulunamadı.'], 404);
    }
    
    json_response(['success' => true, 'data' => $sonuc->fetch_assoc()]);
}

// yeni kullanici ekle — sifre hash'li
function createUser($baglanti, $veri) {
    $zorunlu_alanlar = ['ad', 'soyad', 'eposta', 'sifre'];
    $kontrol = check_required_fields($veri, $zorunlu_alanlar);
    
    if (!$kontrol['valid']) {
        json_response(['success' => false, 'message' => 'Zorunlu alanlar eksik: ' . implode(', ', $kontrol['missing'])], 400);
    }
    
    if (!validate_email($veri['eposta'])) {
        json_response(['success' => false, 'message' => 'Geçersiz e-posta formatı.'], 400);
    }
    
    // sifre karmasiklik kontrolu
    $sifre_kontrol = validate_password($veri['sifre']);
    if (!$sifre_kontrol['valid']) {
        json_response(['success' => false, 'message' => $sifre_kontrol['message']], 400);
    }
    
    // ayni epostadan var mi kontrol et
    $sorgu = $baglanti->prepare("SELECT kullanici_id FROM kullanicilar WHERE eposta = ?");
    $sorgu->bind_param("s", $veri['eposta']);
    $sorgu->execute();
    if ($sorgu->get_result()->num_rows > 0) {
        json_response(['success' => false, 'message' => 'Bu e-posta adresi zaten kullanılıyor.'], 409);
    }
    
    $yetki = isset($veri['yetki']) ? (int)$veri['yetki'] : 0;
    
    // sifre hash'le
    $hashed_sifre = password_hash($veri['sifre'], PASSWORD_DEFAULT);
    
    // girdileri temizle
    $ad = sanitize_input($veri['ad']);
    $soyad = sanitize_input($veri['soyad']);
    
    $sorgu = $baglanti->prepare("INSERT INTO kullanicilar (ad, soyad, eposta, sifre, yetki) VALUES (?, ?, ?, ?, ?)");
    $sorgu->bind_param("ssssi", $ad, $soyad, $veri['eposta'], $hashed_sifre, $yetki);
    
    if ($sorgu->execute()) {
        json_response(['success' => true, 'message' => 'Kullanıcı başarıyla oluşturuldu.', 'data' => ['kullanici_id' => $baglanti->insert_id]], 201);
    } else {
        json_response(['success' => false, 'message' => 'Kullanıcı oluşturulurken hata oluştu.'], 500);
    }
}

// kullanici guncelle — sifre hash'li
function updateUser($baglanti, $veri) {
    $id = (int)$veri['kullanici_id'];
    $mevcut_kullanici_id = $_SESSION['kullanici_id'];
    $kendisi_mi = ($id === $mevcut_kullanici_id);
    
    // kendi yetkisini degistirmeye calisiyor mu
    if ($kendisi_mi && isset($veri['yetki']) && !empty($veri['yetki_changed'])) {
        json_response(['success' => false, 'message' => 'Kendi yetkinizi değiştiremezsiniz.'], 400);
    }
    
    // eposta baskasinda var mi
    if (!empty($veri['eposta'])) {
        $sorgu = $baglanti->prepare("SELECT kullanici_id FROM kullanicilar WHERE eposta = ? AND kullanici_id != ?");
        $sorgu->bind_param("si", $veri['eposta'], $id);
        $sorgu->execute();
        if ($sorgu->get_result()->num_rows > 0) {
            json_response(['success' => false, 'message' => 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.'], 409);
        }
    }
    
    // mevcut epostayi al (degisiklik kontrolu icin)
    $sorgu = $baglanti->prepare("SELECT eposta FROM kullanicilar WHERE kullanici_id = ?");
    $sorgu->bind_param("i", $id);
    $sorgu->execute();
    $mevcut_eposta = $sorgu->get_result()->fetch_assoc()['eposta'];
    
    $guncellemeler = [];
    $parametreler = [];
    $tipler = '';
    $bilgiler_degisti = false;
    
    if (!empty($veri['ad'])) { 
        $guncellemeler[] = 'ad = ?'; 
        $parametreler[] = sanitize_input($veri['ad']); 
        $tipler .= 's'; 
    }
    if (!empty($veri['soyad'])) { 
        $guncellemeler[] = 'soyad = ?'; 
        $parametreler[] = sanitize_input($veri['soyad']); 
        $tipler .= 's'; 
    }
    if (!empty($veri['eposta'])) { 
        $guncellemeler[] = 'eposta = ?'; 
        $parametreler[] = $veri['eposta']; 
        $tipler .= 's';
        if ($kendisi_mi && $veri['eposta'] !== $mevcut_eposta) {
            $bilgiler_degisti = true;
        }
    }
    if (!empty($veri['sifre'])) { 
        // sifre karmasiklik kontrolu
        $sifre_kontrol = validate_password($veri['sifre']);
        if (!$sifre_kontrol['valid']) {
            json_response(['success' => false, 'message' => $sifre_kontrol['message']], 400);
        }
        
        $guncellemeler[] = 'sifre = ?'; 
        $parametreler[] = password_hash($veri['sifre'], PASSWORD_DEFAULT);
        $tipler .= 's';
        if ($kendisi_mi) {
            $bilgiler_degisti = true;
        }
    }
    // kendi yetkisini degistiremez
    if (isset($veri['yetki']) && !$kendisi_mi) { 
        $guncellemeler[] = 'yetki = ?'; 
        $parametreler[] = (int)$veri['yetki']; 
        $tipler .= 'i'; 
    }
    
    if (empty($guncellemeler)) {
        json_response(['success' => false, 'message' => 'Güncellenecek alan bulunamadı.'], 400);
    }
    
    $parametreler[] = $id;
    $tipler .= 'i';
    
    $sorgu = $baglanti->prepare("UPDATE kullanicilar SET " . implode(', ', $guncellemeler) . " WHERE kullanici_id = ?");
    $sorgu->bind_param($tipler, ...$parametreler);
    
    if ($sorgu->execute()) {
        $yanit = [
            'success' => true, 
            'message' => 'Kullanıcı başarıyla güncellendi.'
        ];
        
        // eposta veya sifre degistiyse tekrar giris yaptir
        if ($bilgiler_degisti) {
            $yanit['logout'] = true;
            $yanit['message'] = 'Bilgileriniz güncellendi. Güvenlik nedeniyle tekrar giriş yapmanız gerekiyor.';
        }
        
        json_response($yanit);
    } else {
        json_response(['success' => false, 'message' => 'Kullanıcı güncellenirken hata oluştu.'], 500);
    }
}

// kullaniciyi pasif yap (silmek yerine yetki=-1 yapiyoruz)
function deleteUser($baglanti, $id) {
    $id = (int)$id;
    
    if ($id === $_SESSION['kullanici_id']) {
        json_response(['success' => false, 'message' => 'Kendi hesabınızı pasif hale getiremezsiniz.'], 400);
    }
    
    $sorgu = $baglanti->prepare("UPDATE kullanicilar SET yetki = -1 WHERE kullanici_id = ?");
    $sorgu->bind_param("i", $id);
    
    if ($sorgu->execute()) {
        json_response(['success' => true, 'message' => 'Kullanıcı pasif hale getirildi. Artık sisteme giriş yapamaz.']);
    } else {
        json_response(['success' => false, 'message' => 'Kullanıcı pasif yapılırken hata oluştu.'], 500);
    }
}

// pasif kullaniciyi tekrar aktif et
function activateUser($baglanti, $id) {
    $id = (int)$id;
    
    $sorgu = $baglanti->prepare("UPDATE kullanicilar SET yetki = 0 WHERE kullanici_id = ? AND yetki = -1");
    $sorgu->bind_param("i", $id);
    
    if ($sorgu->execute()) {
        if ($sorgu->affected_rows > 0) {
            json_response(['success' => true, 'message' => 'Kullanıcı tekrar aktif hale getirildi.']);
        } else {
            json_response(['success' => false, 'message' => 'Kullanıcı bulunamadı veya zaten aktif.'], 400);
        }
    } else {
        json_response(['success' => false, 'message' => 'Kullanıcı aktif edilirken hata oluştu.'], 500);
    }
}
?>