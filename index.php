<?php
// giris sayfasi
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// giris yapmissa dashboarda git
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

// giris islemi
$hata = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF dogrulama
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $hata = 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $eposta = $_POST['eposta'] ?? '';
        $sifre = $_POST['sifre'] ?? '';

        $sonuc = do_login($eposta, $sifre);

        if ($sonuc['success']) {
            header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit;
        } else {
            $hata = $sonuc['message'];
        }
    }
}

$page_title = 'Giriş';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DEUZEM Faaliyet Yönetim Sistemi - Kurumsal Giriş">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/deuzem_logo.png">
</head>

<body class="login-body">
    <div class="glass-container">
        <div class="glass-card">
            <div class="login-header">
                <h2>Faaliyet Yönetim Sistemi</h2>
                <p>Kurumsal hesabınızla giriş yapın</p>
            </div>

            <form method="POST" class="login-form" autocomplete="on">
                <?php echo csrf_token_field(); ?>
                
                <?php if ($hata): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($hata); ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="eposta" class="form-label required">E-posta</label>
                    <input type="email" id="eposta" name="eposta" class="form-input glass-input"
                        placeholder="ornek@email.com"
                        value="<?php echo htmlspecialchars($_POST['eposta'] ?? ''); ?>" 
                        autocomplete="email" required>
                </div>

                <div class="form-group">
                    <label for="sifre" class="form-label required">Şifre</label>
                    <input type="password" id="sifre" name="sifre" class="form-input glass-input" 
                        placeholder="••••" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full login-btn glass-btn">
                    Giriş Yap
                </button>
            </form>
        </div>
    </div>
</body>

</html>