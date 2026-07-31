<?php
// cikis islemi
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

do_logout();
header('Location: ' . BASE_URL . '/index.php');
exit;
?>
