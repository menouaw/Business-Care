    <footer class="footer mt-auto py-3 bg-light">
        <div class="container-fluid">
            <div class="d-flex justify-content-between px-md-4">
                <span class="text-muted">&copy; <?php echo date('Y'); ?> Business Care</span>
                <span class="text-muted">Version <?php echo APP_VERSION; ?></span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/admin.js"></script>
    
    <script>
        // initialisation des plugins
        document.addEventListener('DOMContentLoaded', function() {
            // initialise les icones feather
            feather.replace();
            
            // initialise les infobulles
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>
</html> 