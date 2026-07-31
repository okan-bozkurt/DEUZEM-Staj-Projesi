<?php
// tanim tablolari yonetimi (birimler, diller, kapsamlar, faydalar) — sadece admin
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_admin();

$baglanti = get_db_connection();
$metod = $_SERVER['REQUEST_METHOD'];
$tur = $_GET['tur'] ?? '';

// izin verilen tablolar ve alan bilgileri
$tablolar = [
    'birimler'           => ['tablo' => 'birimler',          'id' => 'birim_id',   'ad' => 'birim_adi',   'kod' => 'birim_kodu',   'kod_zorunlu' => true],
    'diller'             => ['tablo' => 'diller',             'id' => 'dil_id',     'ad' => 'dil_adi',     'kod' => 'dil_kodu',     'kod_zorunlu' => true],
    'kapsamlar'          => ['tablo' => 'kapsamlar',          'id' => 'kapsam_id',  'ad' => 'kapsam_adi',  'kod' => null,           'kod_zorunlu' => false],
    'toplumsal_faydalar' => ['tablo' => 'toplumsal_faydalar', 'id' => 'fayda_id',   'ad' => 'fayda_adi',   'kod' => null,           'kod_zorunlu' => false],
];

if (!array_key_exists($tur, $tablolar)) {
    json_response(['success' => false, 'message' => 'Geçersiz tablo türü.'], 400);
}

$bilgi = $tablolar[$tur];

switch ($metod) {
    case 'GET':
        listele($baglanti, $bilgi);
        break;
    case 'POST':
        verify_csrf_or_die();
        $veri = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $islem = $_GET['action'] ?? 'create';
        if ($islem === 'delete' && isset($veri['id'])) {
            sil($baglanti, $bilgi, (int)$veri['id']);
        } else {
            ekle($baglanti, $bilgi, $veri);
        }
        break;
    default:
        json_response(['success' => false, 'message' => 'Geçersiz istek.'], 400);
}

function listele($baglanti, $bilgi) {
    $sonuc = $baglanti->query("SELECT * FROM `{$bilgi['tablo']}` ORDER BY `{$bilgi['ad']}`");
    json_response(['success' => true, 'data' => $sonuc->fetch_all(MYSQLI_ASSOC)]);
}

function ekle($baglanti, $bilgi, $veri) {
    $ad = trim($veri[$bilgi['ad']] ?? '');
    if (empty($ad)) {
        json_response(['success' => false, 'message' => 'Ad alanı zorunludur.'], 400);
    }
    if (!validate_length($ad, 1, 100)) {
        json_response(['success' => false, 'message' => 'Ad en fazla 100 karakter olabilir.'], 400);
    }

    if ($bilgi['kod_zorunlu']) {
        $kod = trim($veri[$bilgi['kod']] ?? '');
        if (empty($kod)) {
            json_response(['success' => false, 'message' => 'Kod alanı zorunludur.'], 400);
        }
        if (!validate_length($kod, 1, 50)) {
            json_response(['success' => false, 'message' => 'Kod en fazla 50 karakter olabilir.'], 400);
        }
        $sorgu = $baglanti->prepare("INSERT INTO `{$bilgi['tablo']}` (`{$bilgi['ad']}`, `{$bilgi['kod']}`) VALUES (?, ?)");
        $sorgu->bind_param("ss", $ad, $kod);
    } else {
        $sorgu = $baglanti->prepare("INSERT INTO `{$bilgi['tablo']}` (`{$bilgi['ad']}`) VALUES (?)");
        $sorgu->bind_param("s", $ad);
    }

    if ($sorgu->execute()) {
        json_response(['success' => true, 'message' => 'Kayıt başarıyla eklendi.', 'data' => ['id' => $baglanti->insert_id]], 201);
    } else {
        json_response(['success' => false, 'message' => 'Kayıt eklenirken hata oluştu.'], 500);
    }
}

function sil($baglanti, $bilgi, $id) {
    // bu kayda bagli faaliyet var mi kontrol et
    $fk_alanlar = [
        'birimler'           => 'birim_id',
        'diller'             => 'dil_id',
        'kapsamlar'          => 'kapsam_id',
        'toplumsal_faydalar' => 'fayda_id',
    ];
    $fk = $fk_alanlar[$bilgi['tablo']] ?? null;
    if ($fk) {
        $kontrol = $baglanti->prepare("SELECT COUNT(*) as sayi FROM faaliyetler WHERE `$fk` = ?");
        $kontrol->bind_param("i", $id);
        $kontrol->execute();
        $sayi = $kontrol->get_result()->fetch_assoc()['sayi'];
        if ($sayi > 0) {
            json_response(['success' => false, 'message' => "Bu kayıt {$sayi} faaliyette kullanıldığı için silinemez."], 409);
        }
    }

    $sorgu = $baglanti->prepare("DELETE FROM `{$bilgi['tablo']}` WHERE `{$bilgi['id']}` = ?");
    $sorgu->bind_param("i", $id);

    if ($sorgu->execute()) {
        if ($sorgu->affected_rows > 0) {
            json_response(['success' => true, 'message' => 'Kayıt silindi.']);
        } else {
            json_response(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
        }
    } else {
        json_response(['success' => false, 'message' => 'Kayıt silinirken hata oluştu.'], 500);
    }
}
?>
