<?php
// ust menu
$mevcut_kullanici = get_logged_user();
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
            
            <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="btn btn-primary btn-sm">
                Anasayfa
            </a>
            
            <?php if (is_admin()): ?>
            <a href="<?php echo BASE_URL; ?>/admin/kullanici-yonetimi.php" class="btn btn-primary btn-sm">
                Kullanıcılar
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/tanimlamalar.php" class="btn btn-primary btn-sm">
                Tanımlamalar
            </a>
            <?php endif; ?>
            
            <a href="<?php echo BASE_URL; ?>/actions/logout.php" class="btn btn-outline btn-sm" 
               onclick="return confirm('Çıkış yapmak istediğinizden emin misiniz?');">
                Çıkış Yap
            </a>
        </div>
    </div>
</nav>
