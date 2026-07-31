<?php
// yeni kayit / faaliyet ekleme ve duzenleme sayfasi
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

// Duzenleme durumunda kayit bilgisini cek
$duzenlenecek_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$duzenlenen_faaliyet = null;

if ($duzenlenecek_id) {
    $stmt = $baglanti->prepare("SELECT * FROM faaliyetler WHERE faaliyet_id = ?");
    $stmt->bind_param("i", $duzenlenecek_id);
    $stmt->execute();
    $duzenlenen_faaliyet = $stmt->get_result()->fetch_assoc();
}

$varsayilan_tarih = isset($_GET['baslangic_tarihi']) ? htmlspecialchars($_GET['baslangic_tarihi']) : (isset($_GET['tarih']) ? htmlspecialchars($_GET['tarih']) : '');
$varsayilan_yil = $duzenlenen_faaliyet['yil'] ?? ($varsayilan_tarih ? date('Y', strtotime($varsayilan_tarih)) : date('Y'));

$csrf_token = generate_csrf_token();
$page_title = $duzenlenen_faaliyet ? 'Kayıt Düzenle' : 'Kayıt Ekle';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <div class="dashboard-page" style="max-width: 900px; margin: 0 auto;">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 id="pageHeading"><?php echo $duzenlenen_faaliyet ? 'Kayıt Düzenle' : 'Yeni Kayıt Ekle'; ?></h1>
                <p class="text-muted">Sisteme yeni kurumsal faaliyet kaydı ekleyin veya var olan kaydı güncelleyin</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="btn btn-outline">
                ← Kayıt Listesine Dön
            </a>
        </div>

        <div class="card activity-form-container" style="margin-top: 1rem;">
            <div id="formMessage" class="alert" style="display: none;"></div>

            <form id="recordForm" class="activity-form">
                <input type="hidden" id="faaliyet_id" name="faaliyet_id" value="<?php echo $duzenlenen_faaliyet['faaliyet_id'] ?? ''; ?>">

                <!-- genel bilgiler -->
                <div class="form-section">
                    <h4 class="section-title">Genel Bilgiler</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="yil" class="form-label required">Yıl</label>
                            <input type="number" id="yil" name="yil" class="form-input"
                                value="<?php echo htmlspecialchars((string)$varsayilan_yil); ?>" min="2000" max="2100" required>
                        </div>
                        <div class="form-group">
                            <label for="donem" class="form-label required">Dönem</label>
                            <input type="text" id="donem" name="donem" class="form-input"
                                value="<?php echo htmlspecialchars($duzenlenen_faaliyet['donem'] ?? ''); ?>"
                                placeholder="Örn: 2025-2026" maxlength="20" required>
                        </div>
                        <div class="form-group span-2">
                            <label for="faaliyet_icerigi" class="form-label required">Faaliyet İçeriği</label>
                            <input type="text" id="faaliyet_icerigi" name="faaliyet_icerigi" class="form-input"
                                value="<?php echo htmlspecialchars($duzenlenen_faaliyet['faaliyet_icerigi'] ?? ''); ?>"
                                placeholder="Kısa açıklama veya etkinlik başlığı" maxlength="255" required>
                        </div>
                        <div class="form-group span-2">
                            <label for="icerik_detayi" class="form-label">İçerik Detayı</label>
                            <textarea id="icerik_detayi" name="icerik_detayi" class="form-textarea" rows="4"
                                placeholder="Detaylı açıklama..."><?php echo htmlspecialchars($duzenlenen_faaliyet['icerik_detayi'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="faaliyet_yeri" class="form-label required">Faaliyet Yeri</label>
                            <input type="text" id="faaliyet_yeri" name="faaliyet_yeri" class="form-input"
                                value="<?php echo htmlspecialchars($duzenlenen_faaliyet['faaliyet_yeri'] ?? ''); ?>"
                                placeholder="Şehir, kampüs vb." maxlength="255" required>
                        </div>
                        <div class="form-group">
                            <label for="faaliyet_turu" class="form-label required">Faaliyet Türü</label>
                            <input type="text" id="faaliyet_turu" name="faaliyet_turu" class="form-input"
                                value="<?php echo htmlspecialchars($duzenlenen_faaliyet['faaliyet_turu'] ?? ''); ?>"
                                placeholder="Seminer, workshop, konferans vb." maxlength="255" required>
                        </div>
                    </div>
                </div>

                <!-- tarih bilgileri -->
                <div class="form-section">
                    <h4 class="section-title">Tarih Bilgileri</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="baslangic_tarihi" class="form-label required">Başlangıç Tarihi</label>
                            <input type="date" id="baslangic_tarihi" name="baslangic_tarihi" class="form-input"
                                value="<?php echo htmlspecialchars($duzenlenen_faaliyet['baslangic_tarihi'] ?? $varsayilan_tarih); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="bitis_tarihi" class="form-label required">Bitiş Tarihi</label>
                            <input type="date" id="bitis_tarihi" name="bitis_tarihi" class="form-input"
                                value="<?php echo htmlspecialchars($duzenlenen_faaliyet['bitis_tarihi'] ?? $varsayilan_tarih); ?>" required>
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
                                    <option value="<?php echo $k['kapsam_id']; ?>"
                                        <?php echo (isset($duzenlenen_faaliyet['kapsam_id']) && $duzenlenen_faaliyet['kapsam_id'] == $k['kapsam_id']) ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $f['fayda_id']; ?>"
                                        <?php echo (isset($duzenlenen_faaliyet['fayda_id']) && $duzenlenen_faaliyet['fayda_id'] == $f['fayda_id']) ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $d['dil_id']; ?>"
                                        <?php echo (isset($duzenlenen_faaliyet['dil_id']) && $duzenlenen_faaliyet['dil_id'] == $d['dil_id']) ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $b['birim_id']; ?>"
                                        <?php echo (isset($duzenlenen_faaliyet['birim_id']) && $duzenlenen_faaliyet['birim_id'] == $b['birim_id']) ? 'selected' : ''; ?>>
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
                                    <option value="<?php echo $s['ska_id']; ?>"
                                        <?php echo (isset($duzenlenen_faaliyet['ska_id']) && $duzenlenen_faaliyet['ska_id'] == $s['ska_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['ska_aciklama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="katilimci_sayisi" class="form-label">Katılımcı Sayısı</label>
                            <input type="number" id="katilimci_sayisi" name="katilimci_sayisi"
                                class="form-input" value="<?php echo htmlspecialchars($duzenlenen_faaliyet['katilimci_sayisi'] ?? '0'); ?>" min="0">
                        </div>
                    </div>
                </div>

                <!-- butonlar -->
                <div class="form-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <?php echo $duzenlenen_faaliyet ? 'Kayıt Güncelle' : 'Kaydet ve Oluştur'; ?>
                    </button>
                    <button type="button" class="btn btn-outline" onclick="formuTemizle()">Temizle</button>
                    
                    <?php if ($duzenlenen_faaliyet): ?>
                    <button type="button" class="btn btn-danger" id="deleteBtn" onclick="faaliyetSil()">Kayıt Sil</button>
                    <?php endif; ?>

                    <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="btn btn-outline" style="margin-left: auto;">
                        İptal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('recordForm').addEventListener('submit', formGonder);
        
        // tarih validasyonu — bitis < baslangic kontrolu
        document.getElementById('bitis_tarihi').addEventListener('change', function() {
            const baslangic = document.getElementById('baslangic_tarihi').value;
            if (baslangic && this.value && this.value < baslangic) {
                toastGoster('Bitiş tarihi başlangıç tarihinden önce olamaz.', 'warning');
                this.value = baslangic;
            }
        });
    });

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
        const submitBtn = document.getElementById('submitBtn');
        const orjinalMetin = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Kaydediliyor...';

        try {
            const sonuc = await postJSON(`${BASE_URL}/actions/faaliyetler.php?action=${islem}`, veri, CSRF_TOKEN);

            if (sonuc.success) {
                toastGoster(sonuc.message || 'Kayıt başarıyla saklandı.', 'success');
                if (islem === 'create') {
                    // Kayit eklendikten sonra temizle veya anasayfaya yonlendir
                    setTimeout(() => {
                        if (confirm('Kayıt oluşturuldu! Ana sayfadaki listeye dönmek ister misiniz?')) {
                            window.location.href = `${BASE_URL}/pages/dashboard.php`;
                        } else {
                            formuTemizle();
                        }
                    }, 500);
                } else {
                    setTimeout(() => {
                        window.location.href = `${BASE_URL}/pages/dashboard.php`;
                    }, 1000);
                }
            } else {
                toastGoster(sonuc.message || 'Bir hata oluştu.', 'error');
            }
        } catch (err) {
            toastGoster('İşlem sırasında bir hata oluştu.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = orjinalMetin;
        }
    }

    function formuTemizle() {
        document.getElementById('recordForm').reset();
        document.getElementById('faaliyet_id').value = '';
        document.getElementById('yil').value = new Date().getFullYear();
        document.getElementById('pageHeading').textContent = 'Yeni Kayıt Ekle';
        document.getElementById('submitBtn').textContent = 'Kaydet ve Oluştur';
        const deleteBtn = document.getElementById('deleteBtn');
        if (deleteBtn) deleteBtn.style.display = 'none';
    }

    async function faaliyetSil() {
        const id = document.getElementById('faaliyet_id').value;
        if (!id || !confirm('Bu kaydı silmek istediğinizden emin misiniz?')) return;
        
        const sonuc = await postJSON(`${BASE_URL}/actions/faaliyetler.php?action=delete`, { id }, CSRF_TOKEN);
        if (sonuc.success) {
            toastGoster(sonuc.message, 'success');
            setTimeout(() => {
                window.location.href = `${BASE_URL}/pages/dashboard.php`;
            }, 800);
        } else {
            toastGoster(sonuc.message || 'Silme işlemi başarısız.', 'error');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
