    <!-- Sesión actual (usuario, permisos y token CSRF) para js/app.js. -->
    <script>window.SALUD_SESSION = <?= json_encode($SALUD_SESSION ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <script src="<?= asset_url('js/dashboard.js') ?>"></script>
    <script src="<?= asset_url('js/pacientes.js') ?>"></script>
    <script src="<?= asset_url('js/citas.js') ?>"></script>
    <script src="<?= asset_url('js/reportes.js') ?>"></script>
    <script src="<?= asset_url('js/admin.js') ?>"></script>
</body>
</html>
