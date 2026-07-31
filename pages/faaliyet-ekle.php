<?php
// faaliyet-ekle.php -> kayit-ekle.php yonlendirmesi
$queryString = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: kayit-ekle.php' . $queryString);
exit;
