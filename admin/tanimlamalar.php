<?php
// tanim tablolari yonetim sayfasi (birimler, diller, kapsamlar, faydalar)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$kullanici = get_logged_user();
$csrf_token = generate_csrf_token();

$page_title = 'Tanımlamalar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <div class="dashboard-page">
        <div class="page-header">
            <h1>Tanımlamalar</h1>
            <p class="text-muted">Birim, dil, kapsam ve toplumsal fayda listelerini yönetin</p>
        </div>

        <!-- sekme butonlari -->
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px;">
            <button class="btn btn-primary" onclick="sekmeAc('birimler')">Birimler</button>
            <button class="btn btn-outline" onclick="sekmeAc('diller')">Diller</button>
            <button class="btn btn-outline" onclick="sekmeAc('kapsamlar')">Kapsamlar</button>
            <button class="btn btn-outline" onclick="sekmeAc('toplumsal_faydalar')">Toplumsal Faydalar</button>
        </div>

        <!-- her tanim turu icin panel -->
        <?php
        $paneller = [
            'birimler'           => ['baslik' => 'Birimler',           'ad_label' => 'Birim Adı',  'kod_label' => 'Birim Kodu', 'kod' => true],
            'diller'             => ['baslik' => 'Diller',             'ad_label' => 'Dil Adı',    'kod_label' => 'Dil Kodu',   'kod' => true],
            'kapsamlar'          => ['baslik' => 'Kapsamlar',          'ad_label' => 'Kapsam Adı', 'kod_label' => null,         'kod' => false],
            'toplumsal_faydalar' => ['baslik' => 'Toplumsal Faydalar', 'ad_label' => 'Fayda Adı',  'kod_label' => null,         'kod' => false],
        ];
        foreach ($paneller as $tur => $p):
        ?>
        <div id="panel_<?php echo $tur; ?>" class="tanim-panel" style="display:none;">
            <div class="card" style="margin-bottom:16px;">
                <h3><?php echo $p['baslik']; ?> Ekle</h3>
                <form id="form_<?php echo $tur; ?>" onsubmit="kayitEkle(event, '<?php echo $tur; ?>')" style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
                    <div class="form-group" style="flex:1; min-width:180px; margin:0;">
                        <label class="form-label required"><?php echo $p['ad_label']; ?></label>
                        <input type="text" name="ad" class="form-input" required placeholder="<?php echo $p['ad_label']; ?>">
                    </div>
                    <?php if ($p['kod']): ?>
                    <div class="form-group" style="flex:0 0 120px; margin:0;">
                        <label class="form-label required"><?php echo $p['kod_label']; ?></label>
                        <input type="text" name="kod" class="form-input" required placeholder="Ör: TR">
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">+ Ekle</button>
                </form>
            </div>

            <div class="card">
                <h3><?php echo $p['baslik']; ?> Listesi</h3>
                <div class="table-container" style="margin-top:12px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo $p['ad_label']; ?></th>
                                <?php if ($p['kod']): ?>
                                <th><?php echo $p['kod_label']; ?></th>
                                <?php endif; ?>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="tablo_<?php echo $tur; ?>">
                            <tr><td colspan="<?php echo $p['kod'] ? 4 : 3; ?>" style="text-align:center;">Yükleniyor...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

const panelBilgiler = {
    birimler:           { adAlan: 'birim_adi',  kodAlan: 'birim_kodu',   idAlan: 'birim_id',  kod: true },
    diller:             { adAlan: 'dil_adi',    kodAlan: 'dil_kodu',     idAlan: 'dil_id',    kod: true },
    kapsamlar:          { adAlan: 'kapsam_adi', kodAlan: null,           idAlan: 'kapsam_id', kod: false },
    toplumsal_faydalar: { adAlan: 'fayda_adi',  kodAlan: null,           idAlan: 'fayda_id',  kod: false },
};

let aktifSekme = null;

function sekmeAc(tur) {
    // tum panelleri gizle
    document.querySelectorAll('.tanim-panel').forEach(p => p.style.display = 'none');
    // tum butonlari outline yap
    document.querySelectorAll('.tanim-panel').forEach(() => {});
    const butonlar = document.querySelectorAll('[onclick^="sekmeAc"]');
    butonlar.forEach(b => { b.className = 'btn btn-outline'; });
    // secili sekmeyi ac
    document.getElementById('panel_' + tur).style.display = 'block';
    event.currentTarget.className = 'btn btn-primary';
    aktifSekme = tur;
    kayitlariYukle(tur);
}

async function kayitlariYukle(tur) {
    const bilgi = panelBilgiler[tur];
    const tbody = document.getElementById('tablo_' + tur);
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Yükleniyor...</td></tr>';

    const sonuc = await fetchJSON(`${BASE_URL}/actions/tanimlamalar.php?tur=${tur}`);
    if (!sonuc.success) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:red;">Yüklenemedi.</td></tr>';
        return;
    }

    if (sonuc.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${bilgi.kod ? 4 : 3}" style="text-align:center; color:#999;">Henüz kayıt yok.</td></tr>`;
        return;
    }

    tbody.innerHTML = sonuc.data.map((satir, i) => {
        const kodSutun = bilgi.kod ? `<td>${htmlTemizle(satir[bilgi.kodAlan])}</td>` : '';
        const id = satir[bilgi.idAlan];
        return `<tr>
            <td>${i + 1}</td>
            <td>${htmlTemizle(satir[bilgi.adAlan])}</td>
            ${kodSutun}
            <td><button class="btn btn-sm btn-danger" onclick="kayitSil('${tur}', ${id}, '${htmlTemizle(satir[bilgi.adAlan])}')">Sil</button></td>
        </tr>`;
    }).join('');
}

async function kayitEkle(e, tur) {
    e.preventDefault();
    const form = e.target;
    const bilgi = panelBilgiler[tur];
    const adInput = form.querySelector('[name="ad"]');
    const kodInput = form.querySelector('[name="kod"]');

    const veri = {};
    veri[bilgi.adAlan] = adInput.value.trim();
    if (bilgi.kod && kodInput) {
        veri[bilgi.kodAlan] = kodInput.value.trim();
    }

    const sonuc = await postJSON(`${BASE_URL}/actions/tanimlamalar.php?tur=${tur}`, veri, CSRF_TOKEN);
    if (sonuc.success) {
        toastGoster(sonuc.message, 'success');
        form.reset();
        kayitlariYukle(tur);
    } else {
        toastGoster(sonuc.message || 'Hata oluştu.', 'error');
    }
}

async function kayitSil(tur, id, isim) {
    if (!confirm(`"${isim}" kaydını silmek istediğinizden emin misiniz?\n\nBu kayda bağlı faaliyet varsa silinemez.`)) return;

    const sonuc = await postJSON(`${BASE_URL}/actions/tanimlamalar.php?tur=${tur}&action=delete`, { id }, CSRF_TOKEN);
    if (sonuc.success) {
        toastGoster(sonuc.message, 'success');
        kayitlariYukle(tur);
    } else {
        toastGoster(sonuc.message || 'Silinemedi.', 'error');
    }
}

// sayfa acildiginda ilk sekmeyi goster
document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[onclick^="sekmeAc"]').click();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
