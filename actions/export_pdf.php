<?php
// pdf rapor sayfasi
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    die("Yetkisiz erişim.");
}

$baglanti = get_db_connection();
$kullanici = get_logged_user();

// filtreler
$kosullar = [];
$parametreler = [];
$tipler = '';

$filtreBilgisi = [];

if (!empty($_GET['baslangic_tarihi']) && !empty($_GET['bitis_tarihi'])) {
    $kosullar[] = 'f.baslangic_tarihi >= ? AND f.bitis_tarihi <= ?';
    $parametreler[] = $_GET['baslangic_tarihi'];
    $parametreler[] = $_GET['bitis_tarihi'];
    $tipler .= 'ss';
    $filtreBilgisi[] = "Tarih Aralığı: " . date('d.m.Y', strtotime($_GET['baslangic_tarihi'])) . " - " . date('d.m.Y', strtotime($_GET['bitis_tarihi']));
} elseif (!empty($_GET['baslangic_tarihi'])) {
    $kosullar[] = 'f.baslangic_tarihi >= ?';
    $parametreler[] = $_GET['baslangic_tarihi'];
    $tipler .= 's';
    $filtreBilgisi[] = "Başlangıç: " . date('d.m.Y', strtotime($_GET['baslangic_tarihi'])) . " ve sonrası";
} elseif (!empty($_GET['bitis_tarihi'])) {
    $kosullar[] = 'f.bitis_tarihi <= ?';
    $parametreler[] = $_GET['bitis_tarihi'];
    $tipler .= 's';
    $filtreBilgisi[] = "Bitiş: " . date('d.m.Y', strtotime($_GET['bitis_tarihi'])) . " ve öncesi";
}

if (!empty($_GET['search'])) {
    $kosullar[] = 'f.faaliyet_icerigi LIKE ?';
    $parametreler[] = '%' . $_GET['search'] . '%';
    $tipler .= 's';
    $filtreBilgisi[] = "Arama: " . htmlspecialchars($_GET['search']);
}

$filtreSQL = $kosullar ? 'WHERE ' . implode(' AND ', $kosullar) : '';

// tum sutunlar
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

// secili sutunlar
$seciliSutunlar = [];
if (!empty($_GET['columns'])) {
    $istenenler = explode(',', $_GET['columns']);
    foreach ($istenenler as $anahtar) {
        if (array_key_exists($anahtar, $tumSutunlar)) {
            $seciliSutunlar[] = $anahtar;
        }
    }
}

if (empty($seciliSutunlar)) {
    $seciliSutunlar = ['yil', 'donem', 'faaliyet_icerigi', 'baslangic_tarihi', 'bitis_tarihi', 'faaliyet_yeri', 'katilimci_sayisi'];
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
$faaliyetler = $sonuc->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Faaliyet Raporu - <?php echo date('d.m.Y'); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 10px;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            color: #1a1a1a;
        }

        .meta {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #444;
        }

        tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 11px;
            color: #666;
            page-break-inside: avoid;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                font-size: 10pt;
            }

            a {
                text-decoration: none;
                color: #333;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header">
        <h1>Faaliyet Raporu</h1>
        <div class="meta">
            Oluşturulma Tarihi: <?php echo date('d.m.Y H:i:s'); ?> |
            Toplam Kayıt: <?php echo count($faaliyetler); ?>
            <?php if ($filtreBilgisi): ?>
                <br>
                <?php echo implode(' | ', $filtreBilgisi); ?>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <?php foreach ($seciliSutunlar as $anahtar): ?>
                    <th><?php echo htmlspecialchars($tumSutunlar[$anahtar]); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($faaliyetler)): ?>
                <tr>
                    <td colspan="<?php echo count($seciliSutunlar); ?>" style="text-align: center; padding: 20px;">Kayıt bulunamadı.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($faaliyetler as $f): ?>
                    <tr>
                        <?php foreach ($seciliSutunlar as $anahtar): ?>
                            <td>
                                <?php
                                $deger = $f[$anahtar] ?? '-';
                                
                                if ($anahtar === 'faaliyet_icerigi') {
                                    echo '<strong>' . htmlspecialchars($deger) . '</strong>';
                                } elseif ($anahtar === 'icerik_detayi') {
                                    if ($deger && $deger !== '-') {
                                        echo '<small style="color: #666;">' . htmlspecialchars($deger) . '</small>';
                                    } else {
                                        echo '-';
                                    }
                                } elseif ($anahtar === 'baslangic_tarihi') {
                                    echo $deger !== '-' ? date('d.m.Y', strtotime($deger)) : '-';
                                } elseif ($anahtar === 'bitis_tarihi') {
                                    echo $deger !== '-' ? date('d.m.Y', strtotime($deger)) : '-';
                                } elseif ($anahtar === 'katilimci_sayisi') {
                                    echo '<span style="display:block;text-align:center;">' . ($deger ?: 0) . '</span>';
                                } else {
                                    echo htmlspecialchars($deger);
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (isset($_GET['writer']) && $_GET['writer'] === '1'): ?>
        <div class="footer">
            <p>Raporu Oluşturan: <?php echo $kullanici['ad'] . ' ' . $kullanici['soyad']; ?></p>
            <p>İmza: _______________________</p>
        </div>
    <?php endif; ?>

</body>

</html>