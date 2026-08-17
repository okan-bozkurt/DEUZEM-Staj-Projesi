<?php
// Dinamik ek kategori yonetimi — sadece admin
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_admin();

$baglanti = get_db_connection();
ensure_schema_ready($baglanti);
$metod    = $_SERVER['REQUEST_METHOD'];
$islem    = $_GET['action'] ?? '';

switch ($metod) {
    case 'GET':
        switch ($islem) {
            case 'list_tipler':  listTipler($baglanti);  break;
            case 'list_degerler': listDegerler($baglanti); break;
            default: json_response(['success' => false, 'message' => 'Geçersiz islem.'], 400);
        }
        break;

    case 'POST':
        verify_csrf_or_die();
        $veri = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        switch ($islem) {
            case 'create_tip':    createTip($baglanti, $veri);                  break;
            case 'delete_tip':    tipGuncelle($baglanti, (int)($veri['id'] ?? 0), 0); break;
            case 'restore_tip':   tipGuncelle($baglanti, (int)($veri['id'] ?? 0), 1); break;
            case 'create_deger':  createDeger($baglanti, $veri);                break;
            case 'delete_deger':  degerGuncelle($baglanti, (int)($veri['id'] ?? 0), 0); break;
            case 'restore_deger': degerGuncelle($baglanti, (int)($veri['id'] ?? 0), 1); break;
            default: json_response(['success' => false, 'message' => 'Geçersiz islem.'], 400);
        }
        break;

    default:
        json_response(['success' => false, 'message' => 'Geçersiz istek.'], 400);
}

// ---------------------------------------------------------------
// Kategori tiplerini listele
// ---------------------------------------------------------------
function listTipler($baglanti) {
    $tum   = isset($_GET['tum']) && $_GET['tum'] === '1';
    $where = $tum ? '' : ' WHERE aktif_mi = 1';
    $sonuc = $baglanti->query("SELECT * FROM ek_kategori_tipleri{$where} ORDER BY aktif_mi DESC, olusturma_tarihi ASC");
    json_response(['success' => true, 'data' => $sonuc->fetch_all(MYSQLI_ASSOC)]);
}

// ---------------------------------------------------------------
// Bir tipin değerlerini listele (?tip_id=X)
// ---------------------------------------------------------------
function listDegerler($baglanti) {
    $tip_id = (int)($_GET['tip_id'] ?? 0);
    if ($tip_id <= 0) {
        json_response(['success' => false, 'message' => 'Geçerli bir tip_id gerekli.'], 400);
    }
    $tum   = isset($_GET['tum']) && $_GET['tum'] === '1';
    $where = $tum ? 'WHERE tip_id = ?' : 'WHERE tip_id = ? AND aktif_mi = 1';
    $sorgu = $baglanti->prepare("SELECT * FROM ek_kategori_degerleri {$where} ORDER BY aktif_mi DESC, deger_adi ASC");
    $sorgu->bind_param("i", $tip_id);
    $sorgu->execute();
    json_response(['success' => true, 'data' => $sorgu->get_result()->fetch_all(MYSQLI_ASSOC)]);
}

// ---------------------------------------------------------------
// Yeni kategori tipi oluştur
// ---------------------------------------------------------------
function createTip($baglanti, $veri) {
    $ad = trim($veri['tip_adi'] ?? '');
    if (empty($ad)) {
        json_response(['success' => false, 'message' => 'Kategori adı zorunludur.'], 400);
    }
    if (!validate_length($ad, 1, 100)) {
        json_response(['success' => false, 'message' => 'Kategori adı en fazla 100 karakter olabilir.'], 400);
    }
    $sorgu = $baglanti->prepare("INSERT INTO ek_kategori_tipleri (tip_adi) VALUES (?)");
    $sorgu->bind_param("s", $ad);
    if ($sorgu->execute()) {
        json_response(['success' => true, 'message' => 'Kategori oluşturuldu.', 'data' => ['tip_id' => $baglanti->insert_id]], 201);
    }
    json_response(['success' => false, 'message' => 'Oluşturulurken hata oluştu.'], 500);
}

// Kategori tipini sil (0) veya geri yükle (1)
function tipGuncelle($baglanti, $id, $aktif) {
    if ($id <= 0) json_response(['success' => false, 'message' => 'Geçersiz ID.'], 400);
    $sorgu = $baglanti->prepare("UPDATE ek_kategori_tipleri SET aktif_mi = ? WHERE tip_id = ?");
    $sorgu->bind_param("ii", $aktif, $id);
    $sorgu->execute();
    $mesaj = $aktif ? 'Kategori geri yüklendi.' : 'Kategori silindi.';
    if ($sorgu->affected_rows > 0) {
        json_response(['success' => true, 'message' => $mesaj]);
    }
    json_response(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
}

// ---------------------------------------------------------------
// Bir tipe yeni değer ekle
// ---------------------------------------------------------------
function createDeger($baglanti, $veri) {
    $tip_id = (int)($veri['tip_id'] ?? 0);
    $ad     = trim($veri['deger_adi'] ?? '');
    if ($tip_id <= 0 || empty($ad)) {
        json_response(['success' => false, 'message' => 'tip_id ve değer adı zorunludur.'], 400);
    }
    if (!validate_length($ad, 1, 255)) {
        json_response(['success' => false, 'message' => 'Değer adı en fazla 255 karakter olabilir.'], 400);
    }
    $sorgu = $baglanti->prepare("INSERT INTO ek_kategori_degerleri (tip_id, deger_adi) VALUES (?, ?)");
    $sorgu->bind_param("is", $tip_id, $ad);
    if ($sorgu->execute()) {
        json_response(['success' => true, 'message' => 'Değer eklendi.', 'data' => ['deger_id' => $baglanti->insert_id]], 201);
    }
    json_response(['success' => false, 'message' => 'Eklenirken hata oluştu.'], 500);
}

// Değeri sil (0) veya geri yükle (1)
function degerGuncelle($baglanti, $id, $aktif) {
    if ($id <= 0) json_response(['success' => false, 'message' => 'Geçersiz ID.'], 400);
    $sorgu = $baglanti->prepare("UPDATE ek_kategori_degerleri SET aktif_mi = ? WHERE deger_id = ?");
    $sorgu->bind_param("ii", $aktif, $id);
    $sorgu->execute();
    $mesaj = $aktif ? 'Değer geri yüklendi.' : 'Değer silindi.';
    if ($sorgu->affected_rows > 0) {
        json_response(['success' => true, 'message' => $mesaj]);
    }
    json_response(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
}
?>
