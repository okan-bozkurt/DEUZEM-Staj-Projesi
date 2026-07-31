<?php
// kullanici yonetimi (admin paneli)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

$baglanti = get_db_connection();
$kullanici = get_logged_user();
$csrf_token = generate_csrf_token();

$page_title = 'Kullanıcı Yönetimi';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container">
    <div class="dashboard-page">
        <div class="page-header">
            <h1>Kullanıcı Yönetimi</h1>
            <p class="text-muted">Sistem kullanıcılarını yönetin</p>
        </div>

        <div style="margin-bottom: 20px;">
            <button class="btn btn-primary" onclick="formuGoster()">+ Yeni Kullanıcı Ekle</button>
        </div>

        <!-- kullanici formu -->
        <div id="userFormContainer" class="card user-form-container" style="display: none; margin-bottom: 20px;">
            <div class="form-header">
                <h3 id="formTitle">Yeni Kullanıcı Ekle</h3>
            </div>
            
            <form id="userForm" class="user-form">
                <input type="hidden" id="kullanici_id" name="kullanici_id">
                
                <!-- kisisel bilgiler -->
                <div class="form-section">
                    <h4 class="section-title">Kişisel Bilgiler</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ad" class="form-label required">Ad</label>
                            <input type="text" id="ad" name="ad" class="form-input" required placeholder="Kullanıcının adı">
                        </div>
                        <div class="form-group">
                            <label for="soyad" class="form-label required">Soyad</label>
                            <input type="text" id="soyad" name="soyad" class="form-input" required placeholder="Kullanıcının soyadı">
                        </div>
                    </div>
                </div>

                <!-- giris bilgileri -->
                <div class="form-section">
                    <h4 class="section-title">Giriş Bilgileri</h4>
                    <div class="form-grid">
                        <div class="form-group span-2">
                            <label for="eposta" class="form-label required">E-posta</label>
                            <input type="email" id="eposta" name="eposta" class="form-input" required placeholder="ornek@email.com" autocomplete="off">
                            <div class="form-hint">Giriş için kullanılacak e-posta adresi</div>
                        </div>
                        <div class="form-group">
                            <label for="sifre" class="form-label" id="sifreLabel">Şifre</label>
                            <input type="password" id="sifre" name="sifre" class="form-input" placeholder="En az 4 karakter" autocomplete="new-password">
                            <div class="form-hint" id="sifreHint"></div>
                        </div>
                        <div class="form-group">
                            <label for="sifre_tekrar" class="form-label" id="sifreTekrarLabel">Şifre Tekrar</label>
                            <input type="password" id="sifre_tekrar" name="sifre_tekrar" class="form-input" placeholder="Şifreyi tekrar girin" autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <!-- yetki ayarlari -->
                <div class="form-section">
                    <h4 class="section-title">Yetki Ayarları</h4>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="yetki" name="yetki">
                            <span class="checkbox-text">
                                <strong>Admin Yetkisi</strong>
                                <span class="form-hint" id="yetkiHint">
                                    Kullanıcı yönetim paneline erişim ve tüm sistem yetkilerini verir
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- butonlar -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">+ Ekle</button>
                    <button type="button" class="btn btn-outline" onclick="formuGizle()">✕ İptal</button>
                </div>
            </form>
        </div>

        <!-- kullanici tablosu -->
        <div class="card">
            <h3>Kullanıcı Listesi</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Soyad</th>
                            <th>E-posta</th>
                            <th>Yetki</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const CSRF_TOKEN = '<?php echo $csrf_token; ?>';
const MEVCUT_KULLANICI_ID = <?php echo $kullanici['kullanici_id']; ?>;
let duzenleniyor = false;

document.addEventListener('DOMContentLoaded', function() {
    kullanicilariYukle();
    document.getElementById('userForm').addEventListener('submit', formGonder);
});

// kullanici listesini yukle — XSS korumalı
async function kullanicilariYukle() {
    const sonuc = await fetchJSON(`${BASE_URL}/actions/users.php`);
    
    if (sonuc.success) {
        const tbody = document.getElementById('userTableBody');
        tbody.innerHTML = sonuc.data.map(k => {
            const pasifMi = k.yetki == -1;
            const adminMi = k.yetki == 1;
            let rozet = '';
            if (pasifMi) {
                rozet = '<span class="badge badge-danger">Pasif</span>';
            } else if (adminMi) {
                rozet = '<span class="badge badge-success">Admin</span>';
            } else {
                rozet = '<span class="badge badge-secondary">Kullanıcı</span>';
            }
            
            let islemler = `<button class="btn btn-sm btn-primary" onclick="kullaniciDuzenle(${k.kullanici_id})" style="margin-right: 5px;">Düzenle</button>`;
            
            if (k.kullanici_id != MEVCUT_KULLANICI_ID) {
                if (pasifMi) {
                    islemler += `<button class="btn btn-sm btn-success" onclick="kullaniciAktifEt(${k.kullanici_id}, '${htmlTemizle(k.ad)} ${htmlTemizle(k.soyad)}')">Aktif Et</button>`;
                } else {
                    islemler += `<button class="btn btn-sm btn-danger" onclick="kullaniciPasifYap(${k.kullanici_id}, '${htmlTemizle(k.ad)} ${htmlTemizle(k.soyad)}')">Pasif Yap</button>`;
                }
            }
            
            return `
            <tr style="${pasifMi ? 'opacity: 0.6; background-color: #fff5f5;' : ''}">
                <td>${htmlTemizle(k.ad)}</td>
                <td>${htmlTemizle(k.soyad)}</td>
                <td>${htmlTemizle(k.eposta)}</td>
                <td>${rozet}</td>
                <td class="row-actions">${islemler}</td>
            </tr>`;
        }).join('');
    }
}

// formu goster/gizle
function formuGoster(duzenlemeMi = false) {
    duzenleniyor = duzenlemeMi;
    document.getElementById('userFormContainer').style.display = 'block';
    
    if (!duzenlemeMi) {
        formuTemizle();
        document.getElementById('formTitle').textContent = 'Yeni Kullanıcı Ekle';
        document.getElementById('submitBtn').textContent = '+ Ekle';
        document.getElementById('sifreLabel').innerHTML = 'Şifre <span class="required-mark">*</span>';
        document.getElementById('sifreTekrarLabel').innerHTML = 'Şifre Tekrar <span class="required-mark">*</span>';
        document.getElementById('sifreHint').textContent = 'En az 4 karakter';
        document.getElementById('sifre').required = true;
        document.getElementById('sifre_tekrar').required = true;
        document.getElementById('yetki').disabled = false;
        document.getElementById('yetkiHint').textContent = 'Kullanıcı yönetim paneline erişim ve tüm sistem yetkilerini verir';
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function formuGizle() {
    document.getElementById('userFormContainer').style.display = 'none';
    formuTemizle();
}

function formuTemizle() {
    document.getElementById('userForm').reset();
    document.getElementById('kullanici_id').value = '';
}

// kullanici duzenleme — tek kullanıcı getir (tüm listeyi çekmek yerine)
async function kullaniciDuzenle(id) {
    const sonuc = await fetchJSON(`${BASE_URL}/actions/users.php?action=get&id=${id}`);
    
    if (sonuc.success) {
        const k = sonuc.data;
        formuGoster(true);
        document.getElementById('kullanici_id').value = k.kullanici_id;
        document.getElementById('ad').value = k.ad;
        document.getElementById('soyad').value = k.soyad;
        document.getElementById('eposta').value = k.eposta;
        document.getElementById('yetki').checked = k.yetki == 1;
        
        document.getElementById('formTitle').textContent = 'Kullanıcı Düzenle';
        document.getElementById('submitBtn').textContent = '✓ Güncelle';
        document.getElementById('sifreLabel').textContent = 'Şifre';
        document.getElementById('sifreTekrarLabel').textContent = 'Şifre Tekrar';
        document.getElementById('sifreHint').textContent = 'Değiştirmek istemiyorsanız boş bırakın';
        document.getElementById('sifre').required = false;
        document.getElementById('sifre_tekrar').required = false;
        
        // kendi yetkisini degistiremez
        if (k.kullanici_id == MEVCUT_KULLANICI_ID) {
            document.getElementById('yetki').disabled = true;
            document.getElementById('yetkiHint').textContent = 'Kendi yetkinizi değiştiremezsiniz';
        } else {
            document.getElementById('yetki').disabled = false;
            document.getElementById('yetkiHint').textContent = 'Kullanıcı yönetim paneline erişim ve tüm sistem yetkilerini verir';
        }
    }
}

// form gonder — CSRF korumalı + toast bildirimleri
async function formGonder(e) {
    e.preventDefault();
    
    const sifre = document.getElementById('sifre').value;
    const sifreTekrar = document.getElementById('sifre_tekrar').value;
    
    if (sifre && sifre !== sifreTekrar) {
        toastGoster('Şifreler eşleşmiyor!', 'error');
        return;
    }
    
    if (!duzenleniyor && !sifre) {
        toastGoster('Yeni kullanıcı için şifre zorunludur!', 'error');
        return;
    }
    
    if (sifre && sifre.length < 4) {
        toastGoster('Şifre en az 4 karakter olmalıdır.', 'error');
        return;
    }
    
    const veri = {
        kullanici_id: document.getElementById('kullanici_id').value,
        ad: document.getElementById('ad').value,
        soyad: document.getElementById('soyad').value,
        eposta: document.getElementById('eposta').value,
        sifre: sifre,
        yetki: document.getElementById('yetki').checked ? 1 : 0
    };
    
    const islem = veri.kullanici_id ? 'update' : 'create';
    const sonuc = await postJSON(`${BASE_URL}/actions/users.php?action=${islem}`, veri, CSRF_TOKEN);
    
    if (sonuc.success) {
        // eposta veya sifre degistiyse cikis yap
        if (sonuc.logout) {
            toastGoster(sonuc.message, 'warning');
            setTimeout(() => {
                window.location.href = `${BASE_URL}/actions/logout.php`;
            }, 2000);
            return;
        }
        
        toastGoster(sonuc.message, 'success');
        formuGizle();
        kullanicilariYukle();
    } else {
        toastGoster(sonuc.message || 'Bir hata oluştu.', 'error');
    }
}

// kullaniciyi pasif yap — CSRF korumalı
async function kullaniciPasifYap(id, isim) {
    if (!confirm(`${isim} kullanıcısını pasif hale getirmek istediğinizden emin misiniz?\n\nKullanıcı sisteme giriş yapamaz hale gelecek ancak faaliyetleri korunacaktır.`)) return;
    
    const sonuc = await postJSON(`${BASE_URL}/actions/users.php?action=delete`, { id }, CSRF_TOKEN);
    
    if (sonuc.success) {
        toastGoster(sonuc.message, 'success');
        kullanicilariYukle();
    } else {
        toastGoster(sonuc.message || 'İşlem başarısız.', 'error');
    }
}

// kullaniciyi tekrar aktif et — CSRF korumalı
async function kullaniciAktifEt(id, isim) {
    if (!confirm(`${isim} kullanıcısını tekrar aktif hale getirmek istiyor musunuz?`)) return;
    
    const sonuc = await postJSON(`${BASE_URL}/actions/users.php?action=activate`, { id }, CSRF_TOKEN);
    
    if (sonuc.success) {
        toastGoster(sonuc.message, 'success');
        kullanicilariYukle();
    } else {
        toastGoster(sonuc.message || 'İşlem başarısız.', 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
