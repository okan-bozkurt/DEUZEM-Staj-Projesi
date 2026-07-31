<?php
// excel (csv) raporu
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    die("Yetkisiz erişim.");
}

$baglanti = get_db_connection();

// filtreler
$kosullar = [];
$parametreler = [];
$tipler = '';

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

if (!empty($_GET['search'])) {
    $kosullar[] = 'f.faaliyet_icerigi LIKE ?';
    $parametreler[] = '%' . $_GET['search'] . '%';
    $tipler .= 's';
}

$filtreSQL = $kosullar ? 'WHERE ' . implode(' AND ', $kosullar) : '';

// tum sutunlar ve basliklari
$tumSutunlar = [
    'yil' => 'Yıl',
    'donem' => 'Dönem',
    'faaliyet_icerigi' => 'Faaliyet İçeriği',
    'icerik_detayi' => 'Detay',
    'baslangic_tarihi' => 'Başlangıç Tarihi',
    'bitis_tarihi' => 'Bitiş Tarihi',
    'faaliyet_yeri' => 'Yer',
    'faaliyet_turu' => 'Tür',
    'kapsam_adi' => 'Kapsam',
    'fayda_adi' => 'Toplumsal Fayda',
    'dil_adi' => 'Faaliyet Dili',
    'birim_adi' => 'Paydaş Birim',
    'ska_aciklama' => 'SKA',
    'katilimci_sayisi' => 'Katılımcı Sayısı'
];

// secili sutunlari belirle
$seciliSutunlar = [];
if (!empty($_GET['columns'])) {
    $istenenler = explode(',', $_GET['columns']);
    foreach ($istenenler as $anahtar) {
        if (array_key_exists($anahtar, $tumSutunlar)) {
            $seciliSutunlar[] = $anahtar;
        }
    }
}

// hicbiri secilmediyse varsayilanlari kullan
if (empty($seciliSutunlar)) {
    $seciliSutunlar = ['yil', 'donem', 'faaliyet_icerigi', 'baslangic_tarihi', 'bitis_tarihi', 'faaliyet_yeri', 'faaliyet_turu'];
}

// veritabanindan cek
$sql = "SELECT f.*, k.kapsam_adi, tf.fayda_adi, d.dil_adi, b.birim_adi, s.ska_aciklama
        FROM faaliyetler f
        LEFT JOIN kapsamlar k ON f.kapsam_id = k.kapsam_id
        LEFT JOIN toplumsal_faydalar tf ON f.fayda_id = tf.fayda_id
        LEFT JOIN diller d ON f.dil_id = d.dil_id
        LEFT JOIN birimler b ON f.birim_id = b.birim_id
        LEFT JOIN ska s ON f.ska_id = s.ska_id
        $filtreSQL
        ORDER BY f.olusturma_tarihi DESC";

$sorgu = $baglanti->prepare($sql);

if ($parametreler) {
    $sorgu->bind_param($tipler, ...$parametreler);
}

$sorgu->execute();
$sonuc = $sorgu->get_result();
$veriler = $sonuc->fetch_all(MYSQLI_ASSOC);

// csv hazirla
$dosya_adi = "faaliyet_raporu_" . date('Y-m-d_H-i') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $dosya_adi . '"');

$dosya = fopen('php://output', 'w');

// excel turkce karakter icin BOM
fputs($dosya, "\xEF\xBB\xBF");

// baslik satiri
$basliklar = [];
foreach ($seciliSutunlar as $anahtar) {
    $basliklar[] = $tumSutunlar[$anahtar];
}
fputcsv($dosya, $basliklar, ";");

// veriler
foreach ($veriler as $satir) {
    $csvSatir = [];
    foreach ($seciliSutunlar as $anahtar) {
        $deger = $satir[$anahtar] ?? '';
        $csvSatir[] = $deger;
    }
    fputcsv($dosya, $csvSatir, ";");
}

// raporlayan kisi (opsiyonel)
if (isset($_GET['writer']) && $_GET['writer'] === '1') {
    $kullanici = get_logged_user();
    $raporlayan = $kullanici ? ($kullanici['ad'] . ' ' . $kullanici['soyad']) : 'Bilinmeyen Kullanıcı';

    fputcsv($dosya, [], ";");
    fputcsv($dosya, ['Raporu Oluşturan:', $raporlayan], ";");
    fputcsv($dosya, ['Oluşturulma Tarihi:', date('d.m.Y H:i:s')], ";");
}

fclose($dosya);
exit;
?>