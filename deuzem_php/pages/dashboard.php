<?php
// ana sayfa - faaliyet formu, widgetlar ve tablo
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$baglanti = get_db_connection();
$kullanici = get_logged_user();

// dropdown verilerini al
$kapsamlar = $baglanti->query("SELECT kapsam_id, kapsam_adi FROM kapsamlar ORDER BY kapsam_adi")->fetch_all(MYSQLI_ASSOC);
$diller = $baglanti->query("SELECT dil_id, dil_adi FROM diller ORDER BY dil_adi")->fetch_all(MYSQLI_ASSOC);
$birimler = $baglanti->query("SELECT birim_id, birim_adi FROM birimler ORDER BY birim_adi")->fetch_all(MYSQLI_ASSOC);
$faydalar = $baglanti->query("SELECT fayda_id, fayda_adi FROM toplumsal_faydalar ORDER BY fayda_adi")->fetch_all(MYSQLI_ASSOC);
$skaList = $baglanti->query("SELECT ska_id, ska_aciklama FROM ska ORDER BY ska_aciklama")->fetch_all(MYSQLI_ASSOC);

// CSRF token JS icin
$csrf_token = generate_csrf_token();

$page_title = 'Faaliyet Yönetimi';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <div class="dashboard-page">
        <div class="page-header">
            <h1>Faaliyet Yönetimi</h1>
            <p class="text-muted">Kurumsal faaliyetlerinizi bu ekrandan yönetebilirsiniz</p>
        </div>

        <!-- istatistik kartlari -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="statToplam">-</div>
                <div class="stat-label">Toplam Faaliyet</div>
            </div>
            <div class="stat-card stat-upcoming">
                <div class="stat-number" id="statYaklasan">-</div>
                <div class="stat-label">Yaklaşan</div>
            </div>
            <div class="stat-card stat-ongoing">
                <div class="stat-number" id="statDevamEden">-</div>
                <div class="stat-label">Devam Eden</div>
            </div>
            <div class="stat-card stat-recent">
                <div class="stat-number" id="statTamamlanan">-</div>
                <div class="stat-label">Bu Ay Tamamlanan</div>
            </div>
        </div>

        <!-- form + widgetlar -->
        <div class="dashboard-grid">
            <!-- faaliyet formu -->
            <div class="dashboard-left">
                <div class="card activity-form-container">
                    <div class="form-header">
                        <h3 id="formTitle">Yeni Faaliyet</h3>
                    </div>

                    <div id="formMessage" class="alert" style="display: none;"></div>

                    <form id="activityForm" class="activity-form">
                        <input type="hidden" id="faaliyet_id" name="faaliyet_id">

                        <!-- genel bilgiler -->
                        <div class="form-section">
                            <h4 class="section-title">Genel Bilgiler</h4>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="yil" class="form-label required">Yıl</label>
                                    <input type="number" id="yil" name="yil" class="form-input"
                                        value="<?php echo date('Y'); ?>" min="2000" max="2100" required>
                                </div>
                                <div class="form-group">
                                    <label for="donem" class="form-label required">Dönem</label>
                                    <input type="text" id="donem" name="donem" class="form-input"
                                        placeholder="Örn: 2025-2026" maxlength="20" required>
                                </div>
                                <div class="form-group span-2">
                                    <label for="faaliyet_icerigi" class="form-label required">Faaliyet İçeriği</label>
                                    <input type="text" id="faaliyet_icerigi" name="faaliyet_icerigi" class="form-input"
                                        placeholder="Kısa açıklama" maxlength="255" required>
                                </div>
                                <div class="form-group span-2">
                                    <label for="icerik_detayi" class="form-label">İçerik Detayı</label>
                                    <textarea id="icerik_detayi" name="icerik_detayi" class="form-textarea" rows="3"
                                        placeholder="Detaylı açıklama..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="faaliyet_yeri" class="form-label required">Faaliyet Yeri</label>
                                    <input type="text" id="faaliyet_yeri" name="faaliyet_yeri" class="form-input"
                                        placeholder="Şehir, kampüs vb." maxlength="255" required>
                                </div>
                                <div class="form-group">
                                    <label for="faaliyet_turu" class="form-label required">Faaliyet Türü</label>
                                    <input type="text" id="faaliyet_turu" name="faaliyet_turu" class="form-input"
                                        placeholder="Seminer, workshop vb." maxlength="255" required>
                                </div>
                            </div>
                        </div>

                        <!-- tarih bilgileri -->
                        <div class="form-section">
                            <h4 class="section-title">Tarih Bilgileri</h4>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="baslangic_tarihi" class="form-label required">Başlangıç Tarihi</label>
                                    <input type="date" id="baslangic_tarihi" name="baslangic_tarihi" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label for="bitis_tarihi" class="form-label required">Bitiş Tarihi</label>
                                    <input type="date" id="bitis_tarihi" name="bitis_tarihi" class="form-input" required>
                                </div>
                            </div>
                        </div>

                        <!-- detay bilgileri -->
                        <div class="form-section">
                            <h4 class="section-title">Detay Bilgileri</h4>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="kapsam_id" class="form-label">Kapsam</label>
                                    <select id="kapsam_id" name="kapsam_id" class="form-select">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach ($kapsamlar as $k): ?>
                                            <option value="<?php echo $k['kapsam_id']; ?>">
                                                <?php echo htmlspecialchars($k['kapsam_adi']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="fayda_id" class="form-label">Toplumsal Fayda</label>
                                    <select id="fayda_id" name="fayda_id" class="form-select">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach ($faydalar as $f): ?>
                                            <option value="<?php echo $f['fayda_id']; ?>">
                                                <?php echo htmlspecialchars($f['fayda_adi']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="dil_id" class="form-label">Faaliyet Dili</label>
                                    <select id="dil_id" name="dil_id" class="form-select">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach ($diller as $d): ?>
                                            <option value="<?php echo $d['dil_id']; ?>">
                                                <?php echo htmlspecialchars($d['dil_adi']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="birim_id" class="form-label">Paydaş Birim</label>
                                    <select id="birim_id" name="birim_id" class="form-select">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach ($birimler as $b): ?>
                                            <option value="<?php echo $b['birim_id']; ?>">
                                                <?php echo htmlspecialchars($b['birim_adi']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="ska_id" class="form-label">Sosyal Kalkınma Amaçları (SKA)</label>
                                    <select id="ska_id" name="ska_id" class="form-select">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach ($skaList as $s): ?>
                                            <option value="<?php echo $s['ska_id']; ?>">
                                                <?php echo htmlspecialchars($s['ska_aciklama']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="katilimci_sayisi" class="form-label">Katılımcı Sayısı</label>
                                    <input type="number" id="katilimci_sayisi" name="katilimci_sayisi"
                                        class="form-input" value="0" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- butonlar -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-outline" onclick="formuTemizle()">Yeni</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">Kaydet</button>
                            <button type="button" class="btn btn-danger" id="deleteBtn" style="display: none;"
                                onclick="faaliyetSil()">Sil</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- widgetlar -->
            <div class="dashboard-right">
                <div class="card widget">
                    <h4>Yaklaşan Etkinlikler</h4>
                    <div id="upcomingList" class="widget-list"></div>
                    <div id="upcomingPagination" class="widget-pagination"></div>
                </div>

                <div class="card widget">
                    <h4>Devam Eden Faaliyetler</h4>
                    <div id="ongoingList" class="widget-list"></div>
                    <div id="ongoingPagination" class="widget-pagination"></div>
                </div>

                <div class="card widget">
                    <h4>Son Tamamlanan</h4>
                    <div id="recentList" class="widget-list"></div>
                    <div id="recentPagination" class="widget-pagination"></div>
                </div>
            </div>
        </div>

        <!-- faaliyet tablosu -->
        <div class="card activity-table-container">
            <div class="table-header">
                <h3>Faaliyetler</h3>
                <div class="table-actions">
                    <span id="totalCount" class="table-info"></span>
                    <button class="btn btn-sm btn-outline" onclick="toggleColumnSelect()">Sütunları Seç</button>
                </div>
            </div>

            <!-- filtreler -->
            <div class="filter-section">
                <div class="filter-inputs">
                    <div class="filter-group">
                        <label>Başlangıç</label>
                        <input type="date" id="filterStart" class="form-input">
                    </div>
                    <div class="filter-group">
                        <label>Bitiş</label>
                        <input type="date" id="filterEnd" class="form-input">
                    </div>
                    <div class="filter-group search-group">
                        <label>Ara</label>
                        <input type="text" id="filterSearch" class="form-input"
                            placeholder="Faaliyet içeriğinde ara...">
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-sm btn-outline" onclick="filtreleriTemizle()">Temizle</button>
                    </div>
                </div>
            </div>

            <!-- sutun secimi -->
            <div id="columnSelectArea" class="column-select-area" style="display: none;">
                <h5>Gösterilecek Sütunlar</h5>
                <div class="column-select-grid">
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="yil" checked> Yıl</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="donem"> Dönem</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="faaliyet_icerigi" checked> Faaliyet İçeriği</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="icerik_detayi" checked> İçerik Detayı</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="baslangic_tarihi" checked> Başlangıç Tarihi</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="bitis_tarihi" checked> Bitiş Tarihi</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="faaliyet_yeri" checked> Faaliyet Yeri</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="faaliyet_turu"> Faaliyet Türü</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="kapsam_adi"> Kapsam</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="fayda_adi"> Toplumsal Fayda</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="dil_adi"> Faaliyet Dili</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="birim_adi"> Paydaş Birim</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="ska_aciklama"> SKA</label>
                    <label class="checkbox-label"><input type="checkbox" class="col-check" value="katilimci_sayisi" checked> Katılımcı Sayısı</label>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead id="activityTableHead"></thead>
                    <tbody id="activityTableBody"></tbody>
                </table>
            </div>

            <div id="tablePagination" class="pagination"></div>

            <!-- rapor olusturma -->
            <div class="report-section">
                <label>
                    <input type="checkbox" id="reportWriter"> Raporu oluşturan kişiyi belirt
                </label>
                <button class="btn btn-sm btn-success" onclick="raporOlustur('excel')">
                    Excel Raporu
                </button>
                <button class="btn btn-sm btn-danger" onclick="raporOlustur('pdf')">
                    PDF Raporu
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
    let mevcutSayfa = 1;
    let yaklasanSayfa = 1;
    let devamEdenSayfa = 1;
    let sonTamamlananSayfa = 1;

    // sayfa yuklenince
    document.addEventListener('DOMContentLoaded', function () {
        sutunSeciminiBaslat();
        istatistikleriYukle();
        faaliyetleriYukle();
        yaklasanlariYukle();
        devamEdenleriYukle();
        sonTamamlananlariYukle();

        document.getElementById('activityForm').addEventListener('submit', formGonder);
        document.getElementById('filterStart').addEventListener('change', () => { mevcutSayfa = 1; faaliyetleriYukle(); });
        document.getElementById('filterEnd').addEventListener('change', () => { mevcutSayfa = 1; faaliyetleriYukle(); });
        document.getElementById('filterSearch').addEventListener('input', debounce(() => { mevcutSayfa = 1; faaliyetleriYukle(); }, 500));
        
        // tarih validasyonu — bitis < baslangic kontrolu
        document.getElementById('bitis_tarihi').addEventListener('change', function() {
            const baslangic = document.getElementById('baslangic_tarihi').value;
            if (baslangic && this.value && this.value < baslangic) {
                toastGoster('Bitiş tarihi başlangıç tarihinden önce olamaz.', 'warning');
                this.value = baslangic;
            }
        });
    });

    // sutun tanimlari
    const SUTUN_TANIMLARI = [
        { alan: 'yil', baslik: 'Yıl', varsayilanGorunum: true },
        { alan: 'donem', baslik: 'Dönem', varsayilanGorunum: false },
        { alan: 'faaliyet_icerigi', baslik: 'Faaliyet İçeriği', varsayilanGorunum: true },
        { alan: 'icerik_detayi', baslik: 'İçerik Detayı', varsayilanGorunum: true },
        { alan: 'baslangic_tarihi', baslik: 'Başlangıç Tarihi', varsayilanGorunum: true },
        { alan: 'bitis_tarihi', baslik: 'Bitiş Tarihi', varsayilanGorunum: true },
        { alan: 'faaliyet_yeri', baslik: 'Faaliyet Yeri', varsayilanGorunum: true },
        { alan: 'faaliyet_turu', baslik: 'Faaliyet Türü', varsayilanGorunum: false },
        { alan: 'kapsam_adi', baslik: 'Kapsam', varsayilanGorunum: false },
        { alan: 'fayda_adi', baslik: 'Toplumsal Fayda', varsayilanGorunum: false },
        { alan: 'dil_adi', baslik: 'Faaliyet Dili', varsayilanGorunum: false },
        { alan: 'birim_adi', baslik: 'Paydaş Birim', varsayilanGorunum: false },
        { alan: 'ska_aciklama', baslik: 'SKA', varsayilanGorunum: false },
        { alan: 'katilimci_sayisi', baslik: 'Katılımcı Sayısı', varsayilanGorunum: true }
    ];

    // istatistikleri yukle
    async function istatistikleriYukle() {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=stats`);
        if (sonuc.success) {
            document.getElementById('statToplam').textContent = sonuc.data.toplam;
            document.getElementById('statYaklasan').textContent = sonuc.data.yaklasan;
            document.getElementById('statDevamEden').textContent = sonuc.data.devam_eden;
            document.getElementById('statTamamlanan').textContent = sonuc.data.bu_ay_tamamlanan;
        }
    }

    // secili sutunlari al
    function seciliSutunlariAl() {
        const checkboxlar = document.querySelectorAll('.col-check:checked');
        return Array.from(checkboxlar).map(cb => cb.value);
    }

    // tablo basligini olustur
    function tabloBasliginiOlustur() {
        const seciliSutunlar = seciliSutunlariAl();
        const thead = document.getElementById('activityTableHead');

        if (seciliSutunlar.length === 0) {
            thead.innerHTML = '<tr><th>Lütfen en az bir sütun seçin</th></tr>';
            return;
        }

        const basliklar = SUTUN_TANIMLARI
            .filter(s => seciliSutunlar.includes(s.alan))
            .map(s => `<th>${htmlTemizle(s.baslik)}</th>`)
            .join('');

        thead.innerHTML = `<tr>${basliklar}</tr>`;
    }

    // sutun secimi checkboxlarina dinleyici ekle
    function sutunSeciminiBaslat() {
        const checkboxlar = document.querySelectorAll('.col-check');
        checkboxlar.forEach(cb => {
            cb.addEventListener('change', () => {
                tabloBasliginiOlustur();
                faaliyetleriYukle();
            });
        });
        tabloBasliginiOlustur();
    }

    // faaliyetleri yukle
    async function faaliyetleriYukle() {
        const parametreler = new URLSearchParams({
            page: mevcutSayfa,
            limit: 8,
            baslangic_tarihi: document.getElementById('filterStart').value,
            bitis_tarihi: document.getElementById('filterEnd').value,
            search: document.getElementById('filterSearch').value
        });

        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?${parametreler}`);

        if (sonuc.success) {
            tabloOlustur(sonuc.data);
            sayfalama(sonuc.pagination, 'tablePagination', (s) => { mevcutSayfa = s; faaliyetleriYukle(); });
            document.getElementById('totalCount').textContent = `Toplam: ${sonuc.pagination.total} faaliyet`;
        }
    }

    // tabloyu doldur — XSS korumalı
    function tabloOlustur(faaliyetler) {
        const tbody = document.getElementById('activityTableBody');
        const seciliSutunlar = seciliSutunlariAl();

        if (seciliSutunlar.length === 0) {
            tbody.innerHTML = '<tr><td class="text-center">Lütfen en az bir sütun seçin</td></tr>';
            return;
        }

        if (faaliyetler.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${seciliSutunlar.length}" class="text-center">Faaliyet bulunamadı.</td></tr>`;
            return;
        }

        tbody.innerHTML = faaliyetler.map(f => {
            const hucreler = SUTUN_TANIMLARI
                .filter(s => seciliSutunlar.includes(s.alan))
                .map(s => {
                    let deger = f[s.alan] || '-';

                    // tarihleri formatla
                    if (s.alan === 'baslangic_tarihi' || s.alan === 'bitis_tarihi') {
                        deger = tarihFormatla(deger);
                    }

                    // katilimci sayisi ortala
                    if (s.alan === 'katilimci_sayisi') {
                        return `<td class="text-center">${htmlTemizle(deger || 0)}</td>`;
                    }

                    return `<td>${htmlTemizle(deger)}</td>`;
                })
                .join('');

            return `<tr onclick="faaliyetDuzenle(${f.faaliyet_id})" style="cursor:pointer;" title="Düzenlemek için tıklayın">${hucreler}</tr>`;
        }).join('');
    }

    // yaklasan etkinlikler — XSS korumalı
    async function yaklasanlariYukle() {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=upcoming&limit=4&page=${yaklasanSayfa}`);

        if (sonuc.success) {
            document.getElementById('upcomingList').innerHTML = sonuc.data.length === 0
                ? '<p class="text-muted">Yaklaşan etkinlik yok.</p>'
                : sonuc.data.map(f => `<div class="widget-item" onclick="faaliyetDuzenle(${f.faaliyet_id})" tabindex="0" role="button">
                    <div class="widget-item-title">${htmlTemizle(f.faaliyet_icerigi)}</div>
                    <div class="widget-item-meta">${htmlTemizle(f.faaliyet_yeri || '-')} | ${tarihFormatla(f.baslangic_tarihi)} - ${tarihFormatla(f.bitis_tarihi)}</div>
                </div>`).join('');
            sayfalama(sonuc.pagination, 'upcomingPagination', (s) => { yaklasanSayfa = s; yaklasanlariYukle(); });
        }
    }

    // devam eden faaliyetler — XSS korumalı
    async function devamEdenleriYukle() {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=ongoing&limit=4&page=${devamEdenSayfa}`);

        if (sonuc.success) {
            document.getElementById('ongoingList').innerHTML = sonuc.data.length === 0
                ? '<p class="text-muted">Devam eden faaliyet yok.</p>'
                : sonuc.data.map(f => `<div class="widget-item" onclick="faaliyetDuzenle(${f.faaliyet_id})" tabindex="0" role="button">
                    <div class="widget-item-title">${htmlTemizle(f.faaliyet_icerigi)}</div>
                    <div class="widget-item-meta">${htmlTemizle(f.faaliyet_yeri || '-')} | ${tarihFormatla(f.baslangic_tarihi)} - ${tarihFormatla(f.bitis_tarihi)}</div>
                </div>`).join('');
            sayfalama(sonuc.pagination, 'ongoingPagination', (s) => { devamEdenSayfa = s; devamEdenleriYukle(); });
        }
    }

    // son tamamlanan faaliyetler — XSS korumalı
    async function sonTamamlananlariYukle() {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=recent&limit=4&page=${sonTamamlananSayfa}`);

        if (sonuc.success) {
            document.getElementById('recentList').innerHTML = sonuc.data.length === 0
                ? '<p class="text-muted">Son faaliyet yok.</p>'
                : sonuc.data.map(f => `<div class="widget-item" onclick="faaliyetDuzenle(${f.faaliyet_id})" tabindex="0" role="button">
                    <div class="widget-item-title">${htmlTemizle(f.faaliyet_icerigi)}</div>
                    <div class="widget-item-meta">${htmlTemizle(f.faaliyet_yeri || '-')} | ${tarihFormatla(f.baslangic_tarihi)} - ${tarihFormatla(f.bitis_tarihi)}</div>
                </div>`).join('');
            sayfalama(sonuc.pagination, 'recentPagination', (s) => { sonTamamlananSayfa = s; sonTamamlananlariYukle(); });
        }
    }

    // sayfa numaralari
    function sayfalama(sayfalamaBilgisi, containerId, tikla) {
        const container = document.getElementById(containerId);
        if (sayfalamaBilgisi.totalPages <= 1) { container.innerHTML = ''; return; }

        const mevcut = sayfalamaBilgisi.page;
        const toplam = sayfalamaBilgisi.totalPages;
        const maxGorunen = 5;

        let sayfalar = [];

        if (toplam <= maxGorunen) {
            for (let i = 1; i <= toplam; i++) sayfalar.push(i);
        } else {
            sayfalar.push(1);
            let baslangic = Math.max(2, mevcut - 1);
            let bitis = Math.min(toplam - 1, mevcut + 1);
            if (baslangic > 2) sayfalar.push('...');
            for (let i = baslangic; i <= bitis; i++) sayfalar.push(i);
            if (bitis < toplam - 1) sayfalar.push('...');
            sayfalar.push(toplam);
        }

        let html = '';
        sayfalar.forEach(s => {
            if (s === '...') {
                html += '<span class="pagination-ellipsis">...</span>';
            } else {
                html += `<button class="btn btn-sm ${s === mevcut ? 'btn-primary' : 'btn-outline'}" onclick="(${tikla})(${s})">${s}</button>`;
            }
        });

        container.innerHTML = html;
    }

    // form gonder — CSRF korumalı
    async function formGonder(e) {
        e.preventDefault();
        const form = e.target;
        const formVerisi = new FormData(form);
        const veri = Object.fromEntries(formVerisi);
        
        // istemci tarafi tarih kontrolu
        if (veri.baslangic_tarihi && veri.bitis_tarihi && veri.bitis_tarihi < veri.baslangic_tarihi) {
            toastGoster('Bitiş tarihi başlangıç tarihinden önce olamaz.', 'error');
            return;
        }

        const islem = veri.faaliyet_id ? 'update' : 'create';
        const sonuc = await postJSON(`${BASE_URL}/actions/faaliyetler.php?action=${islem}`, veri, CSRF_TOKEN);

        if (sonuc.success) {
            toastGoster(sonuc.message, 'success');
            formuTemizle();
            faaliyetleriYukle();
            yaklasanlariYukle();
            devamEdenleriYukle();
            sonTamamlananlariYukle();
            istatistikleriYukle();
        } else {
            toastGoster(sonuc.message || 'Bir hata oluştu.', 'error');
        }
    }

    // faaliyet duzenle (tiklayinca formu doldur)
    async function faaliyetDuzenle(id) {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=get&id=${id}`);

        if (sonuc.success) {
            const f = sonuc.data;
            document.getElementById('faaliyet_id').value = f.faaliyet_id;
            document.getElementById('yil').value = f.yil;
            document.getElementById('donem').value = f.donem || '';
            document.getElementById('faaliyet_icerigi').value = f.faaliyet_icerigi;
            document.getElementById('icerik_detayi').value = f.icerik_detayi || '';
            document.getElementById('baslangic_tarihi').value = (f.baslangic_tarihi || '').split('T')[0];
            document.getElementById('bitis_tarihi').value = (f.bitis_tarihi || '').split('T')[0];
            document.getElementById('faaliyet_yeri').value = f.faaliyet_yeri || '';
            document.getElementById('faaliyet_turu').value = f.faaliyet_turu || '';
            document.getElementById('kapsam_id').value = f.kapsam_id || '';
            document.getElementById('fayda_id').value = f.fayda_id || '';
            document.getElementById('dil_id').value = f.dil_id || '';
            document.getElementById('birim_id').value = f.birim_id || '';
            document.getElementById('ska_id').value = f.ska_id || '';
            document.getElementById('katilimci_sayisi').value = f.katilimci_sayisi || 0;

            document.getElementById('formTitle').textContent = 'Faaliyet Düzenle';
            document.getElementById('submitBtn').textContent = 'Güncelle';
            document.getElementById('deleteBtn').style.display = 'inline-block';

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // faaliyet sil (formdan)
    async function faaliyetSil() {
        const id = document.getElementById('faaliyet_id').value;
        if (!id || !confirm('Bu faaliyeti silmek istediğinizden emin misiniz?')) return;
        await silIslemleri(id);
    }

    // ortak silme islemi
    async function silIslemleri(id) {
        const sonuc = await postJSON(`${BASE_URL}/actions/faaliyetler.php?action=delete`, { id }, CSRF_TOKEN);

        if (sonuc.success) {
            toastGoster(sonuc.message, 'success');
            formuTemizle();
            faaliyetleriYukle();
            yaklasanlariYukle();
            devamEdenleriYukle();
            sonTamamlananlariYukle();
            istatistikleriYukle();
        } else {
            toastGoster(sonuc.message || 'Silme işlemi başarısız.', 'error');
        }
    }

    // formu temizle
    function formuTemizle() {
        document.getElementById('activityForm').reset();
        document.getElementById('faaliyet_id').value = '';
        document.getElementById('yil').value = new Date().getFullYear();
        document.getElementById('formTitle').textContent = 'Yeni Faaliyet';
        document.getElementById('submitBtn').textContent = 'Kaydet';
        document.getElementById('deleteBtn').style.display = 'none';
    }

    // filtreleri temizle
    function filtreleriTemizle() {
        document.getElementById('filterStart').value = '';
        document.getElementById('filterEnd').value = '';
        document.getElementById('filterSearch').value = '';
        mevcutSayfa = 1;
        faaliyetleriYukle();
    }

    // sutun secimi ac/kapat
    function toggleColumnSelect() {
        const el = document.getElementById('columnSelectArea');
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }

    // rapor olustur
    function raporOlustur(tip) {
        const baslangic = document.getElementById('filterStart').value;
        const bitis = document.getElementById('filterEnd').value;
        const arama = document.getElementById('filterSearch').value;
        const raporlayan = document.getElementById('reportWriter').checked ? '1' : '0';

        const sutunlar = Array.from(document.querySelectorAll('.col-check:checked'))
            .map(cb => cb.value)
            .join(',');

        let url = `${BASE_URL}/actions/export_${tip}.php?`;
        url += `baslangic_tarihi=${baslangic}&bitis_tarihi=${bitis}&search=${encodeURIComponent(arama)}&writer=${raporlayan}&columns=${sutunlar}`;

        if (tip === 'excel') {
            window.location.href = url;
        } else {
            window.open(url, '_blank');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>