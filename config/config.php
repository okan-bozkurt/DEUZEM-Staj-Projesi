<?php
// genel ayarlar

// zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// hata gosterimi kapali (kullaniciya sizmasin), loglama acik
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// guvenlik header'lari
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// session ayarlari
$https_aktif = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', $https_aktif ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');

// session suresi (24 saat)
define('SESSION_LIFETIME', 86400);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// session baslangiç
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// sabitler
define('APP_NAME', 'DEUZEM Faaliyet Yönetim Sistemi');
define('APP_VERSION', '1.0.0');

// url ayari
define('BASE_URL', '/deuzem_php');

// dosya yollari
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ACTIONS_PATH', ROOT_PATH . '/actions');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// sayfalama
define('DEFAULT_PAGE_SIZE', 10);
define('DASHBOARD_WIDGET_LIMIT', 4);

// brute force korumasi
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 dakika
?>