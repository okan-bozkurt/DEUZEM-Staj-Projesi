<?php
// giris ve yetki kontrolleri

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// giris yapmamissa logine yonlendir
function require_login() {
    // session suresi kontrolu
    if (isset($_SESSION['son_aktivite'])) {
        if (time() - $_SESSION['son_aktivite'] > SESSION_LIFETIME) {
            do_logout();
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
    $_SESSION['son_aktivite'] = time();

    if (!isset($_SESSION['kullanici_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// admin degilse dashboarda yonlendir
function require_admin() {
    require_login();
    
    if (!isset($_SESSION['yetki']) || $_SESSION['yetki'] != 1) {
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;
    }
}

// oturumdaki kullanici bilgilerini getir
function get_logged_user() {
    if (!isset($_SESSION['kullanici_id'])) {
        return null;
    }
    
    return [
        'kullanici_id' => $_SESSION['kullanici_id'],
        'ad' => $_SESSION['ad'] ?? '',
        'soyad' => $_SESSION['soyad'] ?? '',
        'eposta' => $_SESSION['eposta'] ?? '',
        'yetki' => $_SESSION['yetki'] ?? 0
    ];
}

// admin mi kontrol
function is_admin() {
    return isset($_SESSION['yetki']) && $_SESSION['yetki'] == 1;
}

// giris yapmis mi kontrol
function is_logged_in() {
    return isset($_SESSION['kullanici_id']);
}

// brute force korumasi - deneme sayisini kontrol et
function check_rate_limit() {
    $anahtar = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION[$anahtar])) {
        $_SESSION[$anahtar] = ['sayi' => 0, 'zaman' => time()];
    }
    
    $denemeler = &$_SESSION[$anahtar];
    
    // kilitleme suresi dolduysa sifirla
    if (time() - $denemeler['zaman'] > LOGIN_LOCKOUT_TIME) {
        $denemeler = ['sayi' => 0, 'zaman' => time()];
    }
    
    // cok fazla deneme
    if ($denemeler['sayi'] >= MAX_LOGIN_ATTEMPTS) {
        $kalan = LOGIN_LOCKOUT_TIME - (time() - $denemeler['zaman']);
        $dakika = ceil($kalan / 60);
        return [
            'allowed' => false,
            'message' => "Çok fazla hatalı deneme. Lütfen {$dakika} dakika sonra tekrar deneyin."
        ];
    }
    
    return ['allowed' => true];
}

// basarisiz giris denemesini kaydet
function record_failed_login() {
    $anahtar = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION[$anahtar])) {
        $_SESSION[$anahtar] = ['sayi' => 0, 'zaman' => time()];
    }
    
    $_SESSION[$anahtar]['sayi']++;
    $_SESSION[$anahtar]['zaman'] = time();
}

// basarili giris sonrasi deneme sayacini sifirla
function reset_login_attempts() {
    $anahtar = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
    unset($_SESSION[$anahtar]);
}

// giris islemi
function do_login($eposta, $sifre) {
    if (empty($eposta) || empty($sifre)) {
        return ['success' => false, 'message' => 'E-posta ve şifre gereklidir.'];
    }
    
    if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Geçersiz e-posta formatı.'];
    }
    
    // brute force kontrolu
    $limit = check_rate_limit();
    if (!$limit['allowed']) {
        return ['success' => false, 'message' => $limit['message']];
    }
    
    $baglanti = get_db_connection();
    
    // kullaniciyi bul
    $sorgu = $baglanti->prepare("SELECT kullanici_id, ad, soyad, eposta, sifre, yetki FROM kullanicilar WHERE eposta = ?");
    $sorgu->bind_param("s", $eposta);
    $sorgu->execute();
    $sonuc = $sorgu->get_result();
    
    if ($sonuc->num_rows === 0) {
        record_failed_login();
        return ['success' => false, 'message' => 'E-posta veya şifre hatalı.'];
    }
    
    $kullanici = $sonuc->fetch_assoc();
    
    // sifre kontrol — hash'li sifre destegi
    if (!password_verify($sifre, $kullanici['sifre'])) {
        record_failed_login();
        return ['success' => false, 'message' => 'E-posta veya şifre hatalı.'];
    }
    
    // pasif kullanici kontrolu
    if ((int)$kullanici['yetki'] === -1) {
        return ['success' => false, 'message' => 'Hesabınız pasif hale getirilmiştir. Lütfen yöneticiyle iletişime geçin.'];
    }
    
    // session fixation koruması — yeni session id olustur
    session_regenerate_id(true);
    
    // session olustur
    $_SESSION['kullanici_id'] = $kullanici['kullanici_id'];
    $_SESSION['ad'] = $kullanici['ad'];
    $_SESSION['soyad'] = $kullanici['soyad'];
    $_SESSION['eposta'] = $kullanici['eposta'];
    $_SESSION['yetki'] = $kullanici['yetki'];
    $_SESSION['son_aktivite'] = time();
    
    // basarili giris — deneme sayacini sifirla
    reset_login_attempts();
    
    return [
        'success' => true,
        'message' => 'Giriş başarılı.',
        'kullanici' => [
            'kullanici_id' => $kullanici['kullanici_id'],
            'ad' => $kullanici['ad'],
            'soyad' => $kullanici['soyad'],
            'eposta' => $kullanici['eposta'],
            'yetki' => $kullanici['yetki']
        ]
    ];
}

// cikis yap
function do_logout() {
    session_unset();
    session_destroy();
}
?>
