# DEUZEM Faaliyet Yönetim Sistemi

Dokuz Eylül Üniversitesi Sürekli Eğitim Merkezi (DEUZEM) için geliştirilmiş kurumsal faaliyet takip ve raporlama sistemi. PHP tabanlı, framework kullanmayan, WAMP/XAMPP üzerinde çalışan bir web uygulamasıdır.

---

## İçindekiler

- [Özellikler](#özellikler)
- [Sistem Gereksinimleri](#sistem-gereksinimleri)
- [Kurulum](#kurulum)
- [Kullanım Kılavuzu](#kullanım-kılavuzu)
- [Admin Paneli](#admin-paneli)
- [Raporlama](#raporlama)
- [Konfigürasyon](#konfigürasyon)
- [Proje Yapısı](#proje-yapısı)
- [Güvenlik](#güvenlik)
- [Teknolojiler](#teknolojiler)

---

## Özellikler

### Faaliyet Yönetimi
- Faaliyet ekleme, düzenleme ve silme (tek ekrandan, sayfa yenilemeden)
- Her faaliyete şu bilgiler girilebilir:
  - Yıl, dönem, faaliyet içeriği ve detayı
  - Başlangıç / bitiş tarihi
  - Faaliyet yeri ve türü
  - Katılımcı sayısı
  - Kapsam, toplumsal fayda, faaliyet dili, paydaş birim
  - BM Sürdürülebilir Kalkınma Amacı (SKA 1–17)

### Dashboard
- Anlık istatistik kartları: Toplam, Yaklaşan, Devam Eden, Bu Ay Tamamlanan
- Yan panelde yaklaşan / devam eden / son tamamlanan faaliyet listeleri (sayfalı)

### Tablo ve Filtreleme
- Tüm faaliyetler sayfalı tablo olarak listelenir (sayfa başı 8 kayıt)
- Başlangıç tarihi, bitiş tarihi ve metin araması ile anlık filtreleme
- Hangi sütunların görüneceği kullanıcı tarafından seçilebilir (14 sütun seçeneği)
- Tablodaki herhangi bir satıra tıklayınca faaliyet formu dolar (düzenleme modu)

### Raporlama
- Aktif filtre ve sütun seçimine göre **PDF** ve **Excel (CSV)** raporu oluşturma
- Raporu oluşturan kişinin adını rapora dahil etme seçeneği

### Kullanıcı Sistemi
- İki rol: `Kullanıcı` ve `Admin`
- Normal kullanıcılar yalnızca kendi oluşturdukları faaliyetleri düzenleyebilir/silebilir
- Admin tüm faaliyetleri yönetebilir

---

## Sistem Gereksinimleri

- **WAMP** (önerilir) veya **XAMPP**
- PHP 7.4 veya üzeri
- MySQL 5.7+ / MariaDB 10.3+
- Apache 2.4+

---

## Kurulum

### 1. Projeyi sunucu dizinine kopyalayın

**WAMP kullanıyorsanız:**
```
C:\wamp64\www\deuzem_php\
```

**XAMPP kullanıyorsanız:**
```
C:\xampp\htdocs\deuzem_php\
```

### 2. Apache ve MySQL'i başlatın

WAMP Manager veya XAMPP Control Panel üzerinden Apache ve MySQL servislerini başlatın.

### 3. Veritabanını oluşturun

Tarayıcıdan phpMyAdmin'i açın:
```
http://localhost/phpmyadmin
```

- Sol menüden **Yeni** tıklayın
- Veritabanı adı: `deuzem_etkinlik`
- Karakter seti: `utf8mb4_general_ci`
- **Oluştur** butonuna tıklayın

### 4. SQL dosyasını import edin

- `deuzem_etkinlik` veritabanını sol menüden seçin
- Üstteki **İçe Aktar** sekmesine tıklayın
- `database/veritabani.sql` dosyasını seçin
- **Git** butonuna tıklayın

Bu dosya yalnızca tabloları ve dropdown listelerini (birim, dil, kapsam, toplumsal fayda, SKA) oluşturur. **`kullanicilar` tablosu boş gelir** — sistemde hazır/varsayılan bir kullanıcı yoktur, güvenlik nedeniyle bilinçli olarak eklenmemiştir.

### 5. İlk yönetici (admin) hesabını oluşturun

Sisteme giriş yapılabilmesi için önce veritabanına elle bir yönetici kaydı eklemeniz gerekir. Şifreler düz metin olarak değil, **hash'lenmiş** olarak saklanır; bu yüzden şifreyi doğrudan tabloya yazamazsınız, önce PHP ile hash'ini üretmeniz gerekir.

**a) Şifre hash'i üretin**

Komut satırını (CMD/PowerShell) açın ve XAMPP'ın PHP'sini çalıştırın:

```
C:\xampp\php\php.exe -r "echo password_hash('BurayaSifrenizi', PASSWORD_DEFAULT);"
```

(WAMP kullanıyorsanız `C:\wamp64\bin\php\php-x.x.x\php.exe` yolunu kullanın.)

Bu komut ekrana şuna benzer bir çıktı verir, tamamını kopyalayın:
```
$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUV
```

**b) Kullanıcıyı veritabanına ekleyin**

phpMyAdmin'de `deuzem_etkinlik` veritabanı → **SQL** sekmesine gidin ve aşağıdaki sorguyu kendi bilgilerinizle doldurup çalıştırın:

```sql
INSERT INTO kullanicilar (ad, soyad, eposta, sifre, yetki)
VALUES ('Ad', 'Soyad', 'eposta@ornek.com', '<a adımında ürettiğiniz hash>', 1);
```

- `eposta` alanı giriş için kullanılacak e-posta adresidir.
- `sifre` alanına **yalnızca** (a) adımında üretilen hash'i yapıştırın, düz metin şifreyi değil.
- `yetki = 1` bu kullanıcıyı **admin** yapar (tam yetki). Normal kullanıcı için `0` kullanılır.

### 6. Sisteme giriş yapın

```
http://localhost/deuzem_php
```

5. adımda oluşturduğunuz e-posta ve şifre ile giriş yapın. Sonraki kullanıcıları artık veritabanına elle girmenize gerek yok — **Kullanıcılar** panelinden normal şekilde ekleyebilirsiniz (bkz. [Admin Paneli](#admin-paneli)).

---

## Kullanım Kılavuzu

### Giriş Ekranı

`http://localhost/deuzem_php` adresine gidin. E-posta ve şifrenizi girerek sisteme giriş yapın. 5 başarısız girişimden sonra IP adresiniz 15 dakika kilitlenir.

---

### Ana Ekran (Dashboard)

Giriş yaptıktan sonra **Faaliyet Yönetimi** ekranı açılır. Bu ekran üç bölümden oluşur:

#### Üst Bölüm — İstatistik Kartları

| Kart | Açıklama |
|------|----------|
| Toplam Faaliyet | Sistemdeki tüm kayıt sayısı |
| Yaklaşan | Başlangıç tarihi henüz gelmemiş faaliyetler |
| Devam Eden | Bugünün tarihi başlangıç-bitiş arasında olanlar |
| Bu Ay Tamamlanan | Bu ay biten faaliyetler |

---

#### Sol Bölüm — Faaliyet Formu

**Yeni faaliyet eklemek için:**
1. Formdaki zorunlu alanları doldurun (Yıl, Dönem, Faaliyet İçeriği, Faaliyet Yeri, Faaliyet Türü, Tarihler)
2. İsteğe bağlı alanlı seçin (Kapsam, Toplumsal Fayda, Faaliyet Dili, Paydaş Birim, SKA, Katılımcı Sayısı)
3. **Kaydet** butonuna tıklayın

**Mevcut faaliyeti düzenlemek için:**
1. Faaliyet tablosundaki herhangi bir satıra tıklayın
2. Form otomatik olarak dolar, başlık **Faaliyet Düzenle** olur
3. Değişiklikleri yapıp **Güncelle** butonuna tıklayın

**Faaliyet silmek için:**
- Düzenleme modundayken **Sil** butonu görünür, tıklayınca onay istenir

**Formu sıfırlamak için:**
- **Yeni** butonuna tıklayın

---

#### Sağ Bölüm — Widget Paneli

Üç widget bulunur: **Yaklaşan Etkinlikler**, **Devam Eden Faaliyetler**, **Son Tamamlanan**. Her birinde sayfalama vardır. Herhangi bir öğeye tıklayınca sol formdaki düzenleme modu açılır.

---

#### Alt Bölüm — Faaliyet Tablosu

**Filtreleme:**
- **Başlangıç / Bitiş:** Tarih aralığı ile filtrele
- **Ara:** Faaliyet içeriğinde metin arama (500ms gecikme ile)
- **Temizle:** Tüm filtreleri kaldır

**Sütun Seçimi:**
- **Sütunları Seç** butonuna tıklayarak hangi sütunların tabloda ve raporda görüneceğini seçin
- 14 farklı sütun seçeneği mevcuttur

---

## Admin Paneli

Admin rolündeki kullanıcılar üst menüde ek seçenekler görür.

### Kullanıcı Yönetimi

`Kullanıcılar` menüsünden erişilir. İlk admin hesabı [Kurulum → 5. adımda](#5-i̇lk-yönetici-admin-hesabını-oluşturun) veritabanına elle eklenir; sonraki tüm kullanıcılar bu panelden normal şekilde eklenir.

| İşlem | Açıklama |
|-------|----------|
| Kullanıcı Ekle | Ad, soyad, e-posta, şifre ve rol seçilerek yeni kullanıcı oluşturulur |
| Düzenle | Kullanıcı bilgilerini güncelle (şifre boş bırakılırsa değişmez) |
| Pasif/Aktif | Kullanıcıyı devre dışı bırak veya tekrar aktifleştir |
| Sil | Kullanıcıyı sistemden kaldır |

**Roller:**
- `Kullanıcı (0)` — Yalnızca kendi faaliyetlerini yönetebilir
- `Admin (1)` — Tüm faaliyetler ve kullanıcılar üzerinde tam yetki
- `Pasif (-1)` — Sisteme giriş yapamaz

---

### Tanımlamalar

`Tanımlamalar` menüsünden erişilir. Faaliyet formundaki dropdown listelerini yönetmek için kullanılır.

| Sekme | İçerik | Alanlar |
|-------|--------|---------|
| Birimler | Paydaş birim listesi | Birim Adı, Birim Kodu |
| Diller | Faaliyet dili listesi | Dil Adı, Dil Kodu |
| Kapsamlar | Kapsam listesi | Kapsam Adı |
| Toplumsal Faydalar | Fayda listesi | Fayda Adı |

> **Not:** Herhangi bir faaliyette kullanılmış olan tanımlamalar silinemez.

---

## Raporlama

Faaliyet tablosunun altındaki rapor bölümünden erişilir.

### Excel Raporu
- Aktif filtrelere (tarih aralığı, arama metni) göre veri çeker
- Seçili sütunları içerir
- `.csv` formatında indirilir, Excel'de açılabilir

### PDF Raporu
- Aynı filtre ve sütun seçimini kullanır
- Yeni sekmede PDF olarak açılır
- **Raporu oluşturan kişiyi belirt** seçeneği işaretlenirse kullanıcı adı rapora eklenir

---

## Konfigürasyon

### Veritabanı (`config/db.php`)

```php
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'deuzem_etkinlik');
```

WAMP ve XAMPP'ta varsayılan ayarlar (`root` / şifresiz) olduğu gibi çalışır.

### Uygulama Ayarları (`config/config.php`)

| Sabit | Varsayılan | Açıklama |
|-------|-----------|----------|
| `BASE_URL` | `/deuzem_php` | Uygulamanın URL yolu |
| `DEFAULT_PAGE_SIZE` | `10` | Tablo sayfalama boyutu |
| `SESSION_LIFETIME` | `86400` | Oturum süresi (saniye, 24 saat) |
| `MAX_LOGIN_ATTEMPTS` | `5` | Maksimum başarısız giriş denemesi |
| `LOCKOUT_TIME` | `900` | Kilitleme süresi (saniye, 15 dakika) |

---

## Proje Yapısı

```
deuzem_php/
├── index.php                  # Giriş sayfası
├── config/
│   ├── config.php             # Uygulama sabitleri
│   └── db.php                 # Veritabanı bağlantısı
├── includes/
│   ├── auth.php               # Oturum, yetki, CSRF
│   ├── functions.php          # Yardımcı fonksiyonlar
│   ├── header.php             # HTML head
│   ├── navbar.php             # Üst menü
│   └── footer.php             # HTML footer + JS
├── pages/
│   └── dashboard.php          # Ana sayfa (faaliyet formu + tablo)
├── admin/
│   ├── kullanici-yonetimi.php # Kullanıcı yönetimi (admin)
│   └── tanimlamalar.php       # Tanımlamalar yönetimi (admin)
├── actions/                   # JSON API uç noktaları
│   ├── faaliyetler.php        # Faaliyet CRUD + istatistikler
│   ├── users.php              # Kullanıcı CRUD
│   ├── tanimlamalar.php       # Tanımlama CRUD
│   ├── lookup.php             # Dropdown verileri
│   ├── export_pdf.php         # PDF raporu
│   ├── export_excel.php       # Excel/CSV raporu
│   └── logout.php             # Çıkış
├── assets/
│   ├── css/                   # Stil dosyaları
│   ├── js/main.js             # safeFetch, toast, debounce
│   └── images/                # Logo ve görseller
└── database/
    └── veritabani.sql         # Veritabanı şeması ve başlangıç (lookup) verileri
```

### Veritabanı Tabloları

| Tablo | Açıklama |
|-------|----------|
| `kullanicilar` | Kullanıcılar; `yetki`: `0`=kullanıcı, `1`=admin, `-1`=pasif. Import sonrası boştur, bkz. [Kurulum → 5](#5-i̇lk-yönetici-admin-hesabını-oluşturun) |
| `faaliyetler` | Ana faaliyet kaydı; tüm lookup tablolarına FK bağlantısı |
| `birimler` | Paydaş birim listesi |
| `diller` | Faaliyet dili listesi |
| `kapsamlar` | Kapsam listesi |
| `toplumsal_faydalar` | Toplumsal fayda listesi |
| `ska` | BM SKA hedefleri (1–17) |

---

## Güvenlik

- Tüm DB sorguları `mysqli_prepare()` ile **prepared statement** kullanır — SQL injection yoktur
- Her POST isteği **CSRF token** doğrulamasından geçer
- Tüm kullanıcı girdileri `htmlspecialchars()` ile temizlenir — XSS yoktur
- Şifreler `password_hash()` ile hash'lenerek saklanır; sistemde hazır/varsayılan bir hesap yoktur
- 5 başarısız giriş → 15 dakika IP kilidi
- Oturum süresi 24 saat (ayarlanabilir)
- `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` güvenlik header'ları aktif
- Hata mesajları kullanıcıya gösterilmez, sunucu loguna yazılır

---

## Teknolojiler

| Katman | Teknoloji |
|--------|----------|
| Backend | PHP 7.4+ (framework yok, prosedürel) |
| Veritabanı | MySQL / MariaDB |
| Frontend | HTML5, CSS3, Vanilla JavaScript (ES6+) |
| Sunucu | Apache (WAMP / XAMPP) |
| PDF | Sunucu taraflı PHP ile üretim |
| Excel | CSV formatında dışa aktarım |
#   D E U Z E M - S t a j - P r o j e s i  
 #   D E U Z E M - S t a j - P r o j e s i  
 