<?php
// ust menu
$mevcut_kullanici = get_logged_user();
$mevcut_sayfa = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar" role="navigation" aria-label="Ana menü">
    <div class="container navbar-content">
        <!-- logo -->
        <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="navbar-brand">
            <img src="<?php echo BASE_URL; ?>/assets/images/deuzem_logo.png" alt="DEUZEM Logo" class="navbar-logo">
            <h2>Deuzem Etkinlik Yönetim Sistemi</h2>
        </a>

        <!-- hamburger menu butonu (mobil) -->
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Menüyü aç/kapat" aria-expanded="false">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>

        <!-- kullanici menusu -->
        <div class="navbar-user" id="navbarMenu">
            <span class="user-name">
                <?php echo htmlspecialchars($mevcut_kullanici['ad'] . ' ' . $mevcut_kullanici['soyad']); ?>
            </span>
            
            <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="btn <?php echo ($mevcut_sayfa === 'dashboard.php') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                Anasayfa
            </a>
            
            <a href="<?php echo BASE_URL; ?>/pages/kayit-ekle.php" class="btn <?php echo ($mevcut_sayfa === 'kayit-ekle.php' || $mevcut_sayfa === 'faaliyet-ekle.php') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                Kayıt Ekle
            </a>
            
            <?php if (is_admin()): ?>
            <a href="<?php echo BASE_URL; ?>/admin/kullanici-yonetimi.php" class="btn <?php echo ($mevcut_sayfa === 'kullanici-yonetimi.php') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                Kullanıcılar
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/tanimlamalar.php" class="btn <?php echo ($mevcut_sayfa === 'tanimlamalar.php') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                Tanımlamalar
            </a>
            <?php endif; ?>
            
            <!-- karanlik mod toggle -->
            <button id="darkModeToggle" class="btn btn-outline btn-sm dark-mode-toggle" aria-label="Karanlık moda geç" title="Karanlık Moda Geç">
                <!-- Ay ikonu: aydınlık modda görünür -->
                <svg id="iconMoon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <!-- Güneş ikonu: karanlık modda görünür -->
                <svg id="iconSun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/>
                    <line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
            </button>

            <a href="<?php echo BASE_URL; ?>/actions/logout.php" class="btn btn-outline btn-sm" 
               onclick="return confirm('Çıkış yapmak istediğinizden emin misiniz?');">
                Çıkış Yap
            </a>
        </div>
    </div>
</nav>
