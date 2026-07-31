<?php
// sayfa sonu
?>

    <!-- kurumsal footer -->
    <footer class="site-footer">
        <div class="container footer-content">
            <div class="footer-left">
                <span>&copy; <?php echo date('Y'); ?> DEUZEM — Dokuz Eylül Üniversitesi Uzaktan Eğitim Uygulama ve Araştırma Merkezi</span>
            </div>
            <div class="footer-right">
                <span>Faaliyet Yönetim Sistemi v<?php echo APP_VERSION; ?></span>
            </div>
        </div>
    </footer>

    <!-- js -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo APP_VERSION; ?>"></script>
    
    <?php if (isset($page_js)): ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $page_js; ?>"></script>
    <?php endif; ?>
</body>
</html>
