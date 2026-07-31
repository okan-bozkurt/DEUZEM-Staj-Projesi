// genel yardimci fonksiyonlar

// bekleme fonksiyonu (orn: arama kutusunda cok hizli yazmada gereksiz istek atmayi onler)
function debounce(fonksiyon, bekleme) {
    let zamanlayici;
    return function (...args) {
        clearTimeout(zamanlayici);
        zamanlayici = setTimeout(() => fonksiyon.apply(this, args), bekleme);
    };
}

// html ozel karakterleri temizle (guvenlik icin — XSS koruması)
function htmlTemizle(metin) {
    if (!metin && metin !== 0) return '';
    const div = document.createElement('div');
    div.textContent = String(metin);
    return div.innerHTML;
}

// tarih formatla (gun.ay.yil seklinde)
function tarihFormatla(tarihStr) {
    if (!tarihStr) return '-';
    const t = new Date(tarihStr);
    return t.toLocaleDateString('tr-TR');
}

// toast bildirim sistemi (alert() yerine)
function toastGoster(mesaj, tip = 'success', sure = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${tip}`;

    const ikonlar = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    toast.innerHTML = `
        <span class="toast-icon">${ikonlar[tip] || 'ℹ'}</span>
        <span class="toast-message">${htmlTemizle(mesaj)}</span>
        <button class="toast-close" onclick="this.parentElement.remove()" aria-label="Kapat">×</button>
    `;

    container.appendChild(toast);

    // animasyon icin kisa gecikme
    requestAnimationFrame(() => {
        toast.classList.add('toast-show');
    });

    // otomatik kapat
    setTimeout(() => {
        toast.classList.remove('toast-show');
        toast.classList.add('toast-hide');
        setTimeout(() => toast.remove(), 300);
    }, sure);
}

// guvenli fetch wrapper (hata yakalama ile)
async function fetchJSON(url, secenekler = {}) {
    try {
        const yanit = await fetch(url, secenekler);

        // JSON olmayan yanit kontrolu
        const contentType = yanit.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Sunucudan beklenmeyen yanıt alındı.');
        }

        const sonuc = await yanit.json();
        return sonuc;
    } catch (hata) {
        console.error('Fetch hatası:', hata);
        toastGoster('Bir bağlantı hatası oluştu. Lütfen tekrar deneyin.', 'error');
        return { success: false, message: hata.message };
    }
}

// CSRF token'li POST istegi gonder
async function postJSON(url, veri, csrfToken) {
    return fetchJSON(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(veri)
    });
}

// hamburger menu toggle
document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const navbarMenu = document.getElementById('navbarMenu');

    if (hamburgerBtn && navbarMenu) {
        hamburgerBtn.addEventListener('click', function () {
            const acikMi = navbarMenu.classList.toggle('navbar-user-open');
            hamburgerBtn.classList.toggle('hamburger-active');
            hamburgerBtn.setAttribute('aria-expanded', acikMi);
        });

        // sayfa disina tiklaninca kapat
        document.addEventListener('click', function (e) {
            if (!hamburgerBtn.contains(e.target) && !navbarMenu.contains(e.target)) {
                navbarMenu.classList.remove('navbar-user-open');
                hamburgerBtn.classList.remove('hamburger-active');
                hamburgerBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
