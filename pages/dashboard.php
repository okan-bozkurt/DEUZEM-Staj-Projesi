<?php
// ana sayfa - takvim, widgetlar ve faaliyet tablosu
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$baglanti = get_db_connection();
$kullanici = get_logged_user();

// CSRF token JS icin
$csrf_token = generate_csrf_token();

$page_title = 'Faaliyet Yönetimi';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <div class="dashboard-page">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1>Faaliyet Yönetimi</h1>
                <p class="text-muted">Kurumsal faaliyetlerinizi bu ekrandan takip edebilir ve yönetebilirsiniz</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/pages/kayit-ekle.php" class="btn btn-primary btn-lg">
                + Yeni Kayıt Ekle
            </a>
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

        <!-- sol: takvim | sağ: yaklaşan, devam eden ve tamamlanan widgetlar -->
        <div class="dashboard-grid">
            <!-- sol taraf: etkinlik takvimi -->
            <div class="dashboard-left">
                <div class="card widget" style="padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                        <h3 style="margin: 0; font-size: 1.2rem; font-weight: 600;">Etkinlik Takvimi</h3>
                        <a href="<?php echo BASE_URL; ?>/pages/kayit-ekle.php" class="btn btn-sm btn-outline">
                            + Etkinlik Ekle
                        </a>
                    </div>
                    <div id="calendar" style="min-height: 520px;"></div>
                </div>
            </div>

            <!-- sağ taraf: widgetlar -->
            <div class="dashboard-right">
                <div class="card widget">
                    <h4>Yaklaşan Faaliyetler</h4>
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
                        <label for="filterStart">Başlangıç</label>
                        <input type="date" id="filterStart" class="form-input">
                    </div>
                    <div class="filter-group">
                        <label for="filterEnd">Bitiş</label>
                        <input type="date" id="filterEnd" class="form-input">
                    </div>
                    <div class="filter-group search-group">
                        <label for="filterSearch">Ara</label>
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

<!-- FullCalendar Kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
    let mevcutSayfa = 1;
    let yaklasanSayfa = 1;
    let devamEdenSayfa = 1;
    let sonTamamlananSayfa = 1;

    // sutun tanimlari
    const SUTUN_TANIMLARI = [
        { alan: 'yil', baslik: 'Yıl' },
        { alan: 'donem', baslik: 'Dönem' },
        { alan: 'faaliyet_icerigi', baslik: 'Faaliyet İçeriği' },
        { alan: 'icerik_detayi', baslik: 'İçerik Detayı' },
        { alan: 'baslangic_tarihi', baslik: 'Başlangıç Tarihi' },
        { alan: 'bitis_tarihi', baslik: 'Bitiş Tarihi' },
        { alan: 'faaliyet_yeri', baslik: 'Faaliyet Yeri' },
        { alan: 'faaliyet_turu', baslik: 'Faaliyet Türü' },
        { alan: 'kapsam_adi', baslik: 'Kapsam' },
        { alan: 'fayda_adi', baslik: 'Toplumsal Fayda' },
        { alan: 'dil_adi', baslik: 'Faaliyet Dili' },
        { alan: 'birim_adi', baslik: 'Paydaş Birim' },
        { alan: 'ska_aciklama', baslik: 'SKA' },
        { alan: 'katilimci_sayisi', baslik: 'Katılımcı Sayısı' }
    ];

    // sayfa yuklenince
    document.addEventListener('DOMContentLoaded', function () {
        sutunSeciminiBaslat();
        istatistikleriYukle();
        faaliyetleriYukle();
        yaklasanlariYukle();
        devamEdenleriYukle();
        sonTamamlananlariYukle();
        takvimiBaslat();

        document.getElementById('filterStart').addEventListener('change', () => { mevcutSayfa = 1; faaliyetleriYukle(); });
        document.getElementById('filterEnd').addEventListener('change', () => { mevcutSayfa = 1; faaliyetleriYukle(); });
        document.getElementById('filterSearch').addEventListener('input', debounce(() => { mevcutSayfa = 1; faaliyetleriYukle(); }, 400));
    });

    // istatistikleri yukle
    async function istatistikleriYukle() {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=stats`);
        if (sonuc && sonuc.success) {
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
        if (!thead) return;

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

    // sutun secimi tercihlerini LocalStorage'da saklama
    function sutunSeciminiBaslat() {
        const kayitliSutunlar = localStorage.getItem('deuzem_selected_columns');
        if (kayitliSutunlar) {
            try {
                const secilenler = JSON.parse(kayitliSutunlar);
                document.querySelectorAll('.col-check').forEach(cb => {
                    cb.checked = secilenler.includes(cb.value);
                });
            } catch (e) {
                console.error('Sütun tercihi yüklenirken hata:', e);
            }
        }

        const checkboxlar = document.querySelectorAll('.col-check');
        checkboxlar.forEach(cb => {
            cb.addEventListener('change', () => {
                sutunTercihiniKaydet();
                tabloBasliginiOlustur();
                faaliyetleriYukle();
            });
        });
        tabloBasliginiOlustur();
    }

    function sutunTercihiniKaydet() {
        const secili = seciliSutunlariAl();
        localStorage.setItem('deuzem_selected_columns', JSON.stringify(secili));
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

        if (sonuc && sonuc.success) {
            tabloOlustur(sonuc.data);
            sayfalama(sonuc.pagination, 'tablePagination', (s) => { mevcutSayfa = s; faaliyetleriYukle(); });
            document.getElementById('totalCount').textContent = `Toplam: ${sonuc.pagination.total} faaliyet`;
        }
    }

    // tabloyu doldur — XSS korumalı
    function tabloOlustur(faaliyetler) {
        const tbody = document.getElementById('activityTableBody');
        if (!tbody) return;
        
        const seciliSutunlar = seciliSutunlariAl();

        if (seciliSutunlar.length === 0) {
            tbody.innerHTML = '<tr><td class="text-center">Lütfen en az bir sütun seçin</td></tr>';
            return;
        }

        if (!faaliyetler || faaliyetler.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${seciliSutunlar.length}" class="text-center">Faaliyet bulunamadı.</td></tr>`;
            return;
        }

        tbody.innerHTML = faaliyetler.map(f => {
            const hucreler = SUTUN_TANIMLARI
                .filter(s => seciliSutunlar.includes(s.alan))
                .map(s => {
                    let deger = f[s.alan] || '-';

                    if (s.alan === 'baslangic_tarihi' || s.alan === 'bitis_tarihi') {
                        deger = tarihFormatla(deger);
                    }

                    if (s.alan === 'katilimci_sayisi') {
                        return `<td class="text-center">${htmlTemizle(deger || 0)}</td>`;
                    }

                    return `<td>${htmlTemizle(deger)}</td>`;
                })
                .join('');

            return `<tr onclick="faaliyetDuzenle(${f.faaliyet_id})" style="cursor:pointer;" title="Düzenlemek için tıklayın">${hucreler}</tr>`;
        }).join('');
    }

    // widget listesi render yardımcısı
    function widgetListesiniGuncelle(containerId, data, bosMesaj) {
        const el = document.getElementById(containerId);
        if (!el) return;
        if (!data || data.length === 0) {
            el.innerHTML = `<p class="text-muted">${bosMesaj}</p>`;
            return;
        }
        el.innerHTML = data.map(f => `
            <div class="widget-item" onclick="faaliyetDuzenle(${f.faaliyet_id})" tabindex="0" role="button">
                <div class="widget-item-title">${htmlTemizle(f.faaliyet_icerigi)}</div>
                <div class="widget-item-meta">${htmlTemizle(f.faaliyet_yeri || '-')} | ${tarihFormatla(f.baslangic_tarihi)} - ${tarihFormatla(f.bitis_tarihi)}</div>
            </div>
        `).join('');
    }

    // yaklasan etkinlikler
    async function yaklasanlariYukle() {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=upcoming&limit=4&page=${yaklasanSayfa}`);
        if (sonuc && sonuc.success) {
            widgetListesiniGuncelle('upcomingList', sonuc.data, 'Yaklaşan etkinlik yok.');
            sayfalama(sonuc.pagination, 'upcomingPagination', (s) => { yaklasanSayfa = s; yaklasanlariYukle(); });
        }
    }

    // devam eden faaliyetler
    async function devamEdenleriYukle() {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=ongoing&limit=4&page=${devamEdenSayfa}`);
        if (sonuc && sonuc.success) {
            widgetListesiniGuncelle('ongoingList', sonuc.data, 'Devam eden faaliyet yok.');
            sayfalama(sonuc.pagination, 'ongoingPagination', (s) => { devamEdenSayfa = s; devamEdenleriYukle(); });
        }
    }

    // son tamamlanan faaliyetler
    async function sonTamamlananlariYukle() {
        const sonuc = await fetchJSON(`${BASE_URL}/actions/faaliyetler.php?action=recent&limit=4&page=${sonTamamlananSayfa}`);
        if (sonuc && sonuc.success) {
            widgetListesiniGuncelle('recentList', sonuc.data, 'Son faaliyet yok.');
            sayfalama(sonuc.pagination, 'recentPagination', (s) => { sonTamamlananSayfa = s; sonTamamlananlariYukle(); });
        }
    }

    // sayfa numaralari
    function sayfalama(sayfalamaBilgisi, containerId, tikla) {
        const container = document.getElementById(containerId);
        if (!container) return;
        if (!sayfalamaBilgisi || sayfalamaBilgisi.totalPages <= 1) { 
            container.innerHTML = ''; 
            return; 
        }

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
                html += `<button type="button" class="btn btn-sm ${s === mevcut ? 'btn-primary' : 'btn-outline'}" onclick="(${tikla})(${s})">${s}</button>`;
            }
        });

        container.innerHTML = html;
    }

    // faaliyet duzenleme sayfasına yönlendir
    function faaliyetDuzenle(id) {
        if (id) {
            window.location.href = `${BASE_URL}/pages/kayit-ekle.php?id=${id}`;
        }
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
        if (el) {
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }
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

    // Takvimi Başlatma Fonksiyonu
    function takvimiBaslat() {
        const calendarEl = document.getElementById('calendar');
        if (calendarEl && typeof FullCalendar !== 'undefined') {
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'tr',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                buttonText: { 
                    today: 'Bugün',
                    month: 'Ay',
                    week: 'Hafta'
                },
                height: 540,
                events: `${BASE_URL}/actions/faaliyetler.php?action=calendar`,
                eventClick: function(info) {
                    if (info.event && info.event.id) {
                        window.location.href = `${BASE_URL}/pages/kayit-ekle.php?id=${info.event.id}`;
                    }
                },
                dateClick: function(info) {
                    window.location.href = `${BASE_URL}/pages/kayit-ekle.php?tarih=${info.dateStr}`;
                }
            });
            calendar.render();
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>