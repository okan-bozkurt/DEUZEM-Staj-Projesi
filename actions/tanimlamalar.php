<?php
// tanim tablolari yonetimi (birimler, diller, kapsamlar, faydalar, ska) — sadece admin
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_admin();

$baglanti = get_db_connection();
$metod    = $_SERVER['REQUEST_METHOD'];
$tur      = $_GET['tur'] ?? '';

// izin verilen tablolar ve alan bilgileri
$tablolar = [
    'birimler'           => ['tablo' => 'birimler',          'id' => 'birim_id',    'ad' => 'birim_adi',    'kod' => 'birim_kodu', 'kod_zorunlu' => true],
    'diller'             => ['tablo' => 'diller',             'id' => 'dil_id',      'ad' => 'dil_adi',      'kod' => 'dil_kodu',   'kod_zorunlu' => true],
    'kapsamlar'          => ['tablo' => 'kapsamlar',          'id' => 'kapsam_id',   'ad' => 'kapsam_adi',   'kod' => null,         'kod_zorunlu' => false],
    'toplumsal_faydalar' => ['tablo' => 'toplumsal_faydalar', 'id' => 'fayda_id',    'ad' => 'fayda_adi',    'kod' => null,         'kod_zorunlu' => false],
    'ska'                => ['tablo' => 'ska',                'id' => 'ska_id',      'ad' => 'ska_aciklama', 'kod' => null,         'kod_zorunlu' => false],
];

if (!array_key_exists($tur, $tablolar)) {
    json_response(['success' => false, 'message' => 'Geçersiz tablo türü.'], 400);
}

$bilgi = $tablolar[$tur];
ensure_schema_ready($baglanti);

switch ($metod) {
    case 'GET':
        listele($baglanti, $bilgi);
        break;
    case 'POST':
        verify_csrf_or_die();
        $veri  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $islem = $_GET['action'] ?? 'create';
        switch ($islem) {
            case 'delete':
                if (isset($veri['id'])) pasife_al($baglanti, $bilgi, (int)$veri['id']);
                break;
            case 'restore':
                if (isset($veri['id'])) aktife_al($baglanti, $bilgi, (int)$veri['id']);
                break;
            default:
                ekle($baglanti, $bilgi, $veri);
        }
        break;
    default:
        json_response(['success' => false, 'message' => 'Geçersiz istek.'], 400);
}

// Listele — tum=1 ise aktif+pasif, degilse sadece aktif
function listele($baglanti, $bilgi) {
    $tum   = isset($_GET['tum']) && $_GET['tum'] === '1';
    $where = $tum ? '' : ' WHERE `aktif_mi` = 1';
    $sonuc = $baglanti->query("SELECT * FROM `{$bilgi['tablo']}`{$where} ORDER BY `aktif_mi` DESC, `{$bilgi['ad']}` ASC");
    if (!$sonuc) {
        $sonuc = $baglanti->query("SELECT * FROM `{$bilgi['tablo']}` ORDER BY `aktif_mi` DESC, `{$bilgi['ad']}` ASC");
    }
    json_response(['success' => true, 'data' => $sonuc ? $sonuc->fetch_all(MYSQLI_ASSOC) : []]);
}

// Yeni kayit ekle
function ekle($baglanti, $bilgi, $veri) {
    $ad = trim($veri[$bilgi['ad']] ?? '');
    if (empty($ad)) {
        json_response(['success' => false, 'message' => 'Ad alanı zorunludur.'], 400);
    }
    if (!validate_length($ad, 1, 255)) {
        json_response(['success' => false, 'message' => 'Ad en fazla 255 karakter olabilir.'], 400);
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
        json_response(['success' => true, 'message' => 'Kayıt eklendi.', 'data' => ['id' => $baglanti->insert_id]], 201);
    }
    json_response(['success' => false, 'message' => 'Kayıt eklenirken hata oluştu.'], 500);
}

// Soft-delete: aktif_mi = 0
function pasife_al($baglanti, $bilgi, $id) {
    $sorgu = $baglanti->prepare("UPDATE `{$bilgi['tablo']}` SET `aktif_mi` = 0 WHERE `{$bilgi['id']}` = ?");
    $sorgu->bind_param("i", $id);
    $sorgu->execute();
    if ($sorgu->affected_rows > 0) {
        json_response(['success' => true, 'message' => 'Kayıt silindi.']);
    }
    json_response(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
}

// Geri yükle: aktif_mi = 1
function aktife_al($baglanti, $bilgi, $id) {
    $sorgu = $baglanti->prepare("UPDATE `{$bilgi['tablo']}` SET `aktif_mi` = 1 WHERE `{$bilgi['id']}` = ?");
    $sorgu->bind_param("i", $id);
    $sorgu->execute();
    if ($sorgu->affected_rows > 0) {
        json_response(['success' => true, 'message' => 'Kayıt geri yüklendi.']);
    }
    json_response(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
}
?>
