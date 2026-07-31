<?php
// sayfa basi

if (!isset($page_title)) {
    $page_title = APP_NAME;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DEUZEM Faaliyet Yönetim Sistemi">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- fontlar -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- css -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo APP_VERSION; ?>">
    
    <!-- favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/deuzem_logo.png">
    
    <?php if (isset($page_css)): ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/<?php echo $page_css; ?>">
    <?php endif; ?>
</head>
<body>
<!-- toast bildirim container -->
<div id="toastContainer" class="toast-container" aria-live="polite"></div>
