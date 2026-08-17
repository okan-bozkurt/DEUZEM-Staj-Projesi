<?php
// tanim tablolari yonetim sayfasi — sadece admin
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$baglanti    = get_db_connection();
ensure_schema_ready($baglanti);

$kullanici   = get_logged_user();
$csrf_token  = generate_csrf_token();
$page_title  = 'Tanımlamalar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <div class="dashboard-page">
        <div class="page-header">
            <h1>Tanımlamalar</h1>
            <p class="text-muted">Kategori listelerini yönetin, yeni kategori ve değerler ekleyin</p>
        </div>

        <!-- bilgi mesaji -->
        <div class="alert" style="background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.3); border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.85rem; color: var(--text-secondary);">
            ℹ️ Silinen kayıtlar yeni kayıt eklerken gösterilmez; mevcut kayıtlarda ve raporlarda görünmeye devam eder.
        </div>

        <!-- Yeni kategori oluşturma formu (gizli) -->
        <div id="yeniKatForm" style="display:none; margin-bottom:20px;">
            <div class="card" style="border: 2px dashed var(--primary-color, #2563eb);">
                <h3 style="margin-top:0;">Yeni Kategori Oluştur</h3>
                <form onsubmit="yeniKategoriOlustur(event)" style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; margin-top:12px;">
                    <div class="form-group" style="flex:1; min-width:240px; margin:0;">
                        <label class="form-label required">Kategori Adı</label>
                        <input type="text" id="yeniKatAdi" class="form-input" required placeholder="Örn: Hedef Kitle, Proje Türü...">
                    </div>
                    <button type="submit" class="btn btn-primary">Oluştur ve Sekmelere Ekle</button>
                    <button type="button" class="btn btn-outline" onclick="yeniKategoriFormGizle()">İptal</button>
                </form>
            </div>
        </div>

        <!-- ================================================
             TEK HİZADA KATEGORİ SEKMELERİ (SABİT + DİNAMİK)
        ================================================ -->
        <div id="tum-kategori-sekmeleri" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:24px;">
            <!-- Sabit sekmeler -->
            <button class="btn btn-primary tab-btn" onclick="sabitSekmeAc('birimler', this)">Birimler</button>
            <button class="btn btn-outline tab-btn" onclick="sabitSekmeAc('diller', this)">Diller</button>
            <button class="btn btn-outline tab-btn" onclick="sabitSekmeAc('kapsamlar', this)">Kapsamlar</button>
            <button class="btn btn-outline tab-btn" onclick="sabitSekmeAc('toplumsal_faydalar', this)">Toplumsal Faydalar</button>
            <button class="btn btn-outline tab-btn" onclick="sabitSekmeAc('ska', this)">SKA</button>

            <!-- Dinamik eklenen sekmeler buraya yerleşecek -->
            <span id="dinamik-tab-butonlari" style="display:inline-flex; gap:8px; flex-wrap:wrap;"></span>

            <!-- + Yeni Kategori Butonu (Sekmelerin en sonunda) -->
            <button class="btn btn-outline" onclick="yeniKategoriFormGoster()" id="yeniKatBtn" style="font-size:0.85rem; border-style:dashed; color:var(--primary-color, #2563eb);">
                + Yeni Kategori Ekle
            </button>
        </div>

        <!-- Sabit kategori panelleri -->
        <?php
        $paneller = [
            'birimler'           => ['baslik' => 'Birimler',           'ad_label' => 'Birim Adı',    'kod_label' => 'Birim Kodu', 'kod' => true],
            'diller'             => ['baslik' => 'Diller',             'ad_label' => 'Dil Adı',      'kod_label' => 'Dil Kodu',   'kod' => true],
            'kapsamlar'          => ['baslik' => 'Kapsamlar',          'ad_label' => 'Kapsam Adı',   'kod_label' => null,         'kod' => false],
            'toplumsal_faydalar' => ['baslik' => 'Toplumsal Faydalar', 'ad_label' => 'Fayda Adı',    'kod_label' => null,         'kod' => false],
            'ska'                => ['baslik' => 'SKA',                'ad_label' => 'SKA Açıklaması','kod_label' => null,         'kod' => false],
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
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h3 style="margin:0;"><?php echo $p['baslik']; ?> Listesi</h3>
                    <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer; color:var(--text-secondary);">
                        <input type="checkbox" id="goster_pasif_<?php echo $tur; ?>" onchange="kayitlariYukle('<?php echo $tur; ?>')">
                        Silinen kayıtları da göster
                    </label>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead id="head_<?php echo $tur; ?>">
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

        <!-- Dinamik ek kategori panelleri buraya yüklenecek -->
        <div id="ek-paneller"></div>

    </div>
</div>

<script>
const BASE_URL   = '<?php echo BASE_URL; ?>';
const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

// =====================================================
// Sabit Kategori Panelleri
// =====================================================
const panelBilgiler = {
    birimler:           { adAlan: 'birim_adi',   kodAlan: 'birim_kodu', idAlan: 'birim_id',  kod: true,  adLabel: 'Birim Adı',   kodLabel: 'Birim Kodu' },
    diller:             { adAlan: 'dil_adi',      kodAlan: 'dil_kodu',   idAlan: 'dil_id',    kod: true,  adLabel: 'Dil Adı',     kodLabel: 'Dil Kodu' },
    kapsamlar:          { adAlan: 'kapsam_adi',   kodAlan: null,         idAlan: 'kapsam_id', kod: false, adLabel: 'Kapsam Adı',  kodLabel: null },
    toplumsal_faydalar: { adAlan: 'fayda_adi',    kodAlan: null,         idAlan: 'fayda_id',  kod: false, adLabel: 'Fayda Adı',   kodLabel: null },
    ska:                { adAlan: 'ska_aciklama', kodAlan: null,         idAlan: 'ska_id',    kod: false, adLabel: 'SKA Açıklaması', kodLabel: null },
};

function tumSekmeButonlariniSifirla() {
    document.querySelectorAll('.tanim-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.className = 'btn btn-outline tab-btn');
}

function sabitSekmeAc(tur, btn) {
    tumSekmeButonlariniSifirla();
    document.getElementById('panel_' + tur).style.display = 'block';
    btn.className = 'btn btn-primary tab-btn';
    kayitlariYukle(tur);
}

async function kayitlariYukle(tur) {
    const bilgi       = panelBilgiler[tur];
    const head        = document.getElementById('head_' + tur);
    const tbody       = document.getElementById('tablo_' + tur);
    const pasifGoster = document.getElementById('goster_pasif_' + tur)?.checked;

    if (head) {
        head.innerHTML = `<tr>
            <th>#</th>
            <th>${htmlTemizle(bilgi.adLabel)}</th>
            ${bilgi.kod ? `<th>${htmlTemizle(bilgi.kodLabel)}</th>` : ''}
            ${pasifGoster ? '<th>Durum</th>' : ''}
            <th>İşlem</th>
        </tr>`;
    }

    const kolonSayisi = (bilgi.kod ? 3 : 2) + (pasifGoster ? 1 : 0) + 1;

    tbody.innerHTML = `<tr><td colspan="${kolonSayisi}" style="text-align:center;">Yükleniyor...</td></tr>`;
    const url    = `${BASE_URL}/actions/tanimlamalar.php?tur=${tur}${pasifGoster ? '&tum=1' : ''}`;
    const sonuc  = await fetchJSON(url);

    if (!sonuc.success) {
        tbody.innerHTML = `<tr><td colspan="${kolonSayisi}" style="text-align:center;color:red;">Yüklenemedi.</td></tr>`;
        return;
    }
    if (sonuc.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${kolonSayisi}" style="text-align:center;color:#999;">Henüz kayıt yok.</td></tr>`;
        return;
    }

    tbody.innerHTML = sonuc.data.map((satir, i) => {
        const id    = satir[bilgi.idAlan];
        const aktif = parseInt(satir.aktif_mi) !== 0;

        // Silinenleri göster seçildiyse: aktif -> Yeşil Aktif badge, pasif -> Kırmızı Silindi badge
        const durumSutun = pasifGoster
            ? `<td>${aktif
                ? '<span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:600;background:rgba(34,197,94,0.15);color:#16a34a;">✓ Aktif</span>'
                : '<span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:600;background:rgba(239,68,68,0.12);color:#dc2626;">⊘ Silindi</span>'}</td>`
            : '';

        const buton = aktif
            ? `<button class="btn btn-sm btn-danger" onclick="kayitPasife('${tur}', ${id}, '${htmlTemizle(satir[bilgi.adAlan])}')">Sil</button>`
            : `<button class="btn btn-sm btn-outline" onclick="kayitAktife('${tur}', ${id}, '${htmlTemizle(satir[bilgi.adAlan])}')" style="border-color:#16a34a;color:#16a34a;">Geri Yükle</button>`;

        return `<tr style="${aktif ? '' : 'opacity:0.6;background:rgba(239,68,68,0.04);'}">
            <td>${i + 1}</td>
            <td>${htmlTemizle(satir[bilgi.adAlan])}</td>
            ${bilgi.kod ? `<td>${htmlTemizle(satir[bilgi.kodAlan])}</td>` : ''}
            ${durumSutun}
            <td>${buton}</td>
        </tr>`;
    }).join('');
}

async function kayitEkle(e, tur) {
    e.preventDefault();
    const form     = e.target;
    const bilgi    = panelBilgiler[tur];
    const adInput  = form.querySelector('[name="ad"]');
    const kodInput = form.querySelector('[name="kod"]');
    const veri = {};
    veri[bilgi.adAlan] = adInput.value.trim();
    if (bilgi.kod && kodInput) veri[bilgi.kodAlan] = kodInput.value.trim();

    const sonuc = await postJSON(`${BASE_URL}/actions/tanimlamalar.php?tur=${tur}`, veri, CSRF_TOKEN);
    if (sonuc.success) { toastGoster(sonuc.message, 'success'); form.reset(); kayitlariYukle(tur); }
    else               { toastGoster(sonuc.message || 'Hata oluştu.', 'error'); }
}

async function kayitPasife(tur, id, isim) {
    if (!confirm(`"${isim}" kaydını silmek istiyor musunuz?\n\nYeni kayıt formundan kaldırılır; mevcut raporlarda görünmeye devam eder.`)) return;
    const sonuc = await postJSON(`${BASE_URL}/actions/tanimlamalar.php?tur=${tur}&action=delete`, { id }, CSRF_TOKEN);
    if (sonuc.success) { toastGoster(sonuc.message, 'success'); kayitlariYukle(tur); }
    else               { toastGoster(sonuc.message || 'İşlem başarısız.', 'error'); }
}

async function kayitAktife(tur, id, isim) {
    if (!confirm(`"${isim}" kaydını geri yüklemek istiyor musunuz?`)) return;
    const sonuc = await postJSON(`${BASE_URL}/actions/tanimlamalar.php?tur=${tur}&action=restore`, { id }, CSRF_TOKEN);
    if (sonuc.success) { toastGoster(sonuc.message, 'success'); kayitlariYukle(tur); }
    else               { toastGoster(sonuc.message || 'İşlem başarısız.', 'error'); }
}

// =====================================================
// Dinamik Ek Kategoriler (Aynı Tab Bar İçinde)
// =====================================================

async function ekKategorileriYukle() {
    const dinamikBar = document.getElementById('dinamik-tab-butonlari');
    const paneller   = document.getElementById('ek-paneller');

    const sonuc = await fetchJSON(`${BASE_URL}/actions/ek_kategoriler.php?action=list_tipler&tum=1`);
    if (!sonuc.success || sonuc.data.length === 0) {
        dinamikBar.innerHTML = '';
        paneller.innerHTML   = '';
        return;
    }

    dinamikBar.innerHTML = '';
    paneller.innerHTML   = '';

    sonuc.data.forEach(tip => {
        const tipAktif = parseInt(tip.aktif_mi) !== 0;

        // Sekme butonu (Sabit sekmelerin hemen yanına eklenir)
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline tab-btn';
        if (!tipAktif) btn.style.opacity = '0.5';
        btn.textContent = tip.tip_adi + (tipAktif ? '' : ' (Silindi)');
        btn.onclick = () => ekSekmeAc(tip.tip_id, tip.tip_adi, tipAktif, btn);
        dinamikBar.appendChild(btn);

        // Panel
        const panel = document.createElement('div');
        panel.id    = `ek_panel_${tip.tip_id}`;
        panel.className = 'tanim-panel';
        panel.style.display = 'none';
        panel.innerHTML = ekPanelHTML(tip, tipAktif);
        paneller.appendChild(panel);
    });
}

function ekPanelHTML(tip, tipAktif) {
    const ekleForm = tipAktif ? `
        <div class="card" style="margin-bottom:16px;">
            <h3>"${htmlTemizle(tip.tip_adi)}" Değeri Ekle</h3>
            <form onsubmit="ekDegerEkle(event, ${tip.tip_id})" style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; margin-top:12px;">
                <div class="form-group" style="flex:1; min-width:200px; margin:0;">
                    <label class="form-label required">Değer Adı</label>
                    <input type="text" name="deger_adi" class="form-input" required placeholder="Örn: Öğrenci">
                </div>
                <button type="submit" class="btn btn-primary">+ Ekle</button>
            </form>
        </div>` : '';

    const tipAksiyon = tipAktif
        ? `<button class="btn btn-sm btn-danger" onclick="ekTipSil(${tip.tip_id}, '${htmlTemizle(tip.tip_adi)}')">Bu Kategoriyi Sil</button>`
        : `<button class="btn btn-sm btn-outline" onclick="ekTipGeriYukle(${tip.tip_id}, '${htmlTemizle(tip.tip_adi)}')" style="border-color:#16a34a;color:#16a34a;">Kategoriyi Geri Yükle</button>`;

    return `${ekleForm}
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
            <h3 style="margin:0;">"${htmlTemizle(tip.tip_adi)}" Listesi</h3>
            <div style="display:flex; gap:8px; align-items:center;">
                <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer; color:var(--text-secondary);">
                    <input type="checkbox" id="ek_pasif_${tip.tip_id}" onchange="ekDegerleriYukle(${tip.tip_id})">
                    Silinen değerleri de göster
                </label>
                ${tipAksiyon}
            </div>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead id="ek_head_${tip.tip_id}"><tr><th>#</th><th>Değer Adı</th><th>İşlem</th></tr></thead>
                <tbody id="ek_tablo_${tip.tip_id}">
                    <tr><td colspan="3" style="text-align:center;">Yükleniyor...</td></tr>
                </tbody>
            </table>
        </div>
    </div>`;
}

function ekSekmeAc(tipId, tipAdi, tipAktif, btn) {
    tumSekmeButonlariniSifirla();
    document.getElementById(`ek_panel_${tipId}`).style.display = 'block';
    btn.className = 'btn btn-primary tab-btn';
    ekDegerleriYukle(tipId);
}

async function ekDegerleriYukle(tipId) {
    const head  = document.getElementById(`ek_head_${tipId}`);
    const tbody = document.getElementById(`ek_tablo_${tipId}`);
    const tum   = document.getElementById(`ek_pasif_${tipId}`)?.checked;

    if (head) {
        head.innerHTML = `<tr><th>#</th><th>Değer Adı</th>${tum ? '<th>Durum</th>' : ''}<th>İşlem</th></tr>`;
    }

    const kolonSayisi = 2 + (tum ? 1 : 0);
    tbody.innerHTML = `<tr><td colspan="${kolonSayisi}" style="text-align:center;">Yükleniyor...</td></tr>`;

    const sonuc = await fetchJSON(`${BASE_URL}/actions/ek_kategoriler.php?action=list_degerler&tip_id=${tipId}${tum ? '&tum=1' : ''}`);
    if (!sonuc.success) {
        tbody.innerHTML = `<tr><td colspan="${kolonSayisi}" style="text-align:center;color:red;">Yüklenemedi.</td></tr>`;
        return;
    }
    if (sonuc.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${kolonSayisi}" style="text-align:center;color:#999;">Henüz değer yok.</td></tr>`;
        return;
    }
    tbody.innerHTML = sonuc.data.map((d, i) => {
        const aktif = parseInt(d.aktif_mi) !== 0;

        // Silinenleri göster seçildiyse: aktif -> Yeşil Aktif badge, pasif -> Kırmızı Silindi badge
        const durumSutun = tum
            ? `<td>${aktif
                ? '<span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:600;background:rgba(34,197,94,0.15);color:#16a34a;">✓ Aktif</span>'
                : '<span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:0.78rem;font-weight:600;background:rgba(239,68,68,0.12);color:#dc2626;">⊘ Silindi</span>'}</td>`
            : '';

        const buton = aktif
            ? `<button class="btn btn-sm btn-danger" onclick="ekDegerSil(${tipId}, ${d.deger_id}, '${htmlTemizle(d.deger_adi)}')">Sil</button>`
            : `<button class="btn btn-sm btn-outline" onclick="ekDegerGeriYukle(${tipId}, ${d.deger_id}, '${htmlTemizle(d.deger_adi)}')" style="border-color:#16a34a;color:#16a34a;">Geri Yükle</button>`;

        return `<tr style="${aktif ? '' : 'opacity:0.6;background:rgba(239,68,68,0.04);'}">
            <td>${i+1}</td>
            <td>${htmlTemizle(d.deger_adi)}</td>
            ${durumSutun}
            <td>${buton}</td>
        </tr>`;
    }).join('');
}

// ── Yeni kategori oluştur ──
function yeniKategoriFormGoster() {
    document.getElementById('yeniKatForm').style.display = 'block';
    document.getElementById('yeniKatBtn').style.display  = 'none';
    document.getElementById('yeniKatAdi').focus();
}
function yeniKategoriFormGizle() {
    document.getElementById('yeniKatForm').style.display = 'none';
    document.getElementById('yeniKatBtn').style.display  = 'inline-flex';
    document.getElementById('yeniKatAdi').value = '';
}

async function yeniKategoriOlustur(e) {
    e.preventDefault();
    const ad = document.getElementById('yeniKatAdi').value.trim();
    if (!ad) return;
    const sonuc = await postJSON(`${BASE_URL}/actions/ek_kategoriler.php?action=create_tip`, { tip_adi: ad }, CSRF_TOKEN);
    if (sonuc.success) {
        toastGoster(sonuc.message, 'success');
        yeniKategoriFormGizle();
        await ekKategorileriYukle();
        // Yeni eklenen kategorinin sekmesine otomatik geç
        const dinamikBtns = document.querySelectorAll('#dinamik-tab-butonlari .tab-btn');
        if (dinamikBtns.length > 0) {
            dinamikBtns[dinamikBtns.length - 1].click();
        }
    } else {
        toastGoster(sonuc.message || 'Hata oluştu.', 'error');
    }
}

// ── Ek değer ekle ──
async function ekDegerEkle(e, tipId) {
    e.preventDefault();
    const form = e.target;
    const ad   = form.querySelector('[name="deger_adi"]').value.trim();
    if (!ad) return;
    const sonuc = await postJSON(`${BASE_URL}/actions/ek_kategoriler.php?action=create_deger`, { tip_id: tipId, deger_adi: ad }, CSRF_TOKEN);
    if (sonuc.success) { toastGoster(sonuc.message, 'success'); form.reset(); ekDegerleriYukle(tipId); }
    else               { toastGoster(sonuc.message || 'Hata oluştu.', 'error'); }
}

// ── Kategori tipi sil / geri yükle ──
async function ekTipSil(id, isim) {
    if (!confirm(`"${isim}" kategorisini silmek istiyor musunuz?\n\nKategorideki tüm değerler de listeden kaldırılır.`)) return;
    const sonuc = await postJSON(`${BASE_URL}/actions/ek_kategoriler.php?action=delete_tip`, { id }, CSRF_TOKEN);
    if (sonuc.success) {
        toastGoster(sonuc.message, 'success');
        await ekKategorileriYukle();
        document.querySelector('#tum-kategori-sekmeleri .tab-btn').click();
    } else {
        toastGoster(sonuc.message || 'Hata oluştu.', 'error');
    }
}
async function ekTipGeriYukle(id, isim) {
    if (!confirm(`"${isim}" kategorisini geri yüklemek istiyor musunuz?`)) return;
    const sonuc = await postJSON(`${BASE_URL}/actions/ek_kategoriler.php?action=restore_tip`, { id }, CSRF_TOKEN);
    if (sonuc.success) {
        toastGoster(sonuc.message, 'success');
        await ekKategorileriYukle();
    } else {
        toastGoster(sonuc.message || 'Hata oluştu.', 'error');
    }
}

// ── Değer sil / geri yükle ──
async function ekDegerSil(tipId, id, isim) {
    if (!confirm(`"${isim}" değerini silmek istiyor musunuz?`)) return;
    const sonuc = await postJSON(`${BASE_URL}/actions/ek_kategoriler.php?action=delete_deger`, { id }, CSRF_TOKEN);
    if (sonuc.success) { toastGoster(sonuc.message, 'success'); ekDegerleriYukle(tipId); }
    else               { toastGoster(sonuc.message || 'Hata oluştu.', 'error'); }
}
async function ekDegerGeriYukle(tipId, id, isim) {
    if (!confirm(`"${isim}" değerini geri yüklemek istiyor musunuz?`)) return;
    const sonuc = await postJSON(`${BASE_URL}/actions/ek_kategoriler.php?action=restore_deger`, { id }, CSRF_TOKEN);
    if (sonuc.success) { toastGoster(sonuc.message, 'success'); ekDegerleriYukle(tipId); }
    else               { toastGoster(sonuc.message || 'Hata oluştu.', 'error'); }
}

// ── Sayfa açılışı ──
document.addEventListener('DOMContentLoaded', async () => {
    // İlk sabit sekmeyi aç (Birimler)
    const ilkBtn = document.querySelector('#tum-kategori-sekmeleri .tab-btn');
    if (ilkBtn) ilkBtn.click();
    // Dinamik ek kategorileri yükle ve aynı tab bar'a diz
    await ekKategorileriYukle();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
