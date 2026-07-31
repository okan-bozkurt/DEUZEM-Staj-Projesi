<?php
// veritabani baglantisi

define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');  
define('DB_NAME', 'deuzem_etkinlik');
define('DB_CHARSET', 'utf8mb4');  


function get_db_connection() {
    static $baglanti = null;
    
    if ($baglanti === null) {
        $baglanti = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($baglanti->connect_error) {
            error_log("Veritabanı bağlantı hatası: " . $baglanti->connect_error);
            die("Sistem geçici olarak kullanılamıyor. Lütfen daha sonra tekrar deneyin.");
        }
        
        $baglanti->set_charset(DB_CHARSET);
    }
    
    return $baglanti;
}
?>