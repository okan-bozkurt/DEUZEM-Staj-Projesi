<?php
// yardimci fonksiyonlar

// girdi temizleme
function sanitize_input($veri) {
    $veri = trim($veri);
    $veri = stripslashes($veri);
    $veri = htmlspecialchars($veri, ENT_QUOTES, 'UTF-8');
    return $veri;
}

// eposta gecerli mi
function validate_email($eposta) {
    return filter_var($eposta, FILTER_VALIDATE_EMAIL) !== false;
}


// tarih gosterim formati (orn: 15.01.2025)
function format_date_display($tarih) {
    if (empty($tarih)) return '-';
    $timestamp = strtotime($tarih);
    return date('d.m.Y', $timestamp);
}

// tarih input formati (orn: 2025-01-15)
function format_date_for_input($tarih) {
    if (empty($tarih)) return '';
    $timestamp = strtotime($tarih);
    return date('Y-m-d', $timestamp);
}


// tarih araligi gecerli mi
function is_valid_date_range($baslangic, $bitis) {
    return strtotime($bitis) >= strtotime($baslangic);
}

// pozitif sayi mi
function is_positive_number($sayi) {
    return is_numeric($sayi) && $sayi >= 0;
}


// json cevap dondur
function json_response($veri, $durum = 200) {
    http_response_code($durum);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($veri, JSON_UNESCAPED_UNICODE);
    exit;
}


// csrf token olustur
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


// csrf token dogrula
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


// csrf token hidden input olarak dondur (formlarda kullanmak icin)
function csrf_token_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}


// csrf token dogrula — basarisizsa json hata dondur
function verify_csrf_or_die() {
    // hem header hem post'tan kontrol et
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($token)) {
        json_response(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.'], 403);
    }
}


// sayfalama hesapla
function calculate_pagination($toplam, $sayfa = 1, $limit = 10) {
    $sayfa = max(1, (int)$sayfa);
    $limit = max(1, (int)$limit);
    $toplam_sayfa = ceil($toplam / $limit);
    $baslangic = ($sayfa - 1) * $limit;
    
    return [
        'total' => $toplam,
        'page' => $sayfa,
        'limit' => $limit,
        'totalPages' => $toplam_sayfa,
        'offset' => $baslangic,
        'hasNext' => $sayfa < $toplam_sayfa,
        'hasPrev' => $sayfa > 1
    ];
}


// zorunlu alanlari kontrol et
function check_required_fields($veri, $zorunlu_alanlar) {
    $eksikler = [];
    foreach ($zorunlu_alanlar as $alan) {
        if (!isset($veri[$alan]) || trim($veri[$alan]) === '') {
            $eksikler[] = $alan;
        }
    }
    return [
        'valid' => empty($eksikler),
        'missing' => $eksikler
    ];
}


// metin uzunlugu kontrolu
function validate_length($metin, $min, $max) {
    $uzunluk = mb_strlen($metin, 'UTF-8');
    return $uzunluk >= $min && $uzunluk <= $max;
}


// sifre karmasiklik kontrolu (en az 4 karakter)
function validate_password($sifre) {
    if (mb_strlen($sifre, 'UTF-8') < 4) {
        return ['valid' => false, 'message' => 'Şifre en az 4 karakter olmalıdır.'];
    }
    return ['valid' => true, 'message' => ''];
}
?>
