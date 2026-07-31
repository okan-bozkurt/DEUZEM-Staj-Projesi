<?php
// dropdown verileri (kapsamlar, diller, birimler vb.)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Giriş yapmanız gerekiyor.'], 401);
}

$baglanti = get_db_connection();
$tip = $_GET['type'] ?? '';

switch ($tip) {
    case 'kapsamlar':
        $veri = $baglanti->query("SELECT kapsam_id, kapsam_adi FROM kapsamlar ORDER BY kapsam_adi")->fetch_all(MYSQLI_ASSOC);
        break;
    case 'diller':
        $veri = $baglanti->query("SELECT dil_id, dil_adi, dil_kodu FROM diller ORDER BY dil_adi")->fetch_all(MYSQLI_ASSOC);
        break;
    case 'birimler':
        $veri = $baglanti->query("SELECT birim_id, birim_adi, birim_kodu FROM birimler ORDER BY birim_adi")->fetch_all(MYSQLI_ASSOC);
        break;
    case 'faydalar':
        $veri = $baglanti->query("SELECT fayda_id, fayda_adi FROM toplumsal_faydalar ORDER BY fayda_adi")->fetch_all(MYSQLI_ASSOC);
        break;
    case 'ska':
        $veri = $baglanti->query("SELECT ska_id, ska_aciklama FROM ska ORDER BY ska_aciklama")->fetch_all(MYSQLI_ASSOC);
        break;
    case 'all':
        // hepsini bir seferde getir
        $veri = [
            'kapsamlar' => $baglanti->query("SELECT kapsam_id, kapsam_adi FROM kapsamlar ORDER BY kapsam_adi")->fetch_all(MYSQLI_ASSOC),
            'diller' => $baglanti->query("SELECT dil_id, dil_adi FROM diller ORDER BY dil_adi")->fetch_all(MYSQLI_ASSOC),
            'birimler' => $baglanti->query("SELECT birim_id, birim_adi FROM birimler ORDER BY birim_adi")->fetch_all(MYSQLI_ASSOC),
            'faydalar' => $baglanti->query("SELECT fayda_id, fayda_adi FROM toplumsal_faydalar ORDER BY fayda_adi")->fetch_all(MYSQLI_ASSOC),
            'skaList' => $baglanti->query("SELECT ska_id, ska_aciklama FROM ska ORDER BY ska_aciklama")->fetch_all(MYSQLI_ASSOC)
        ];
        break;
    default:
        json_response(['success' => false, 'message' => 'Geçersiz tip.'], 400);
}

json_response(['success' => true, 'data' => $veri]);
?>
