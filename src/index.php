<?php require __DIR__ . '/config/page_guard.php'; ?>
<?php include __DIR__ . '/components/head.php'; ?>
<body class="app-body antialiased">
    <a class="skip-link" href="#mainContent">Ir al contenido</a>
    <?php include __DIR__ . '/components/sidebar.php'; ?>
    <main id="mainContent" class="app-main" tabindex="-1">
        <div class="page-shell">
            <?php include __DIR__ . '/components/header.php'; ?>
            <?php include __DIR__ . '/views/dashboard.php'; ?>
            <?php include __DIR__ . '/views/pacientes.php'; ?>
            <?php include __DIR__ . '/views/citas.php'; ?>
            <?php include __DIR__ . '/views/reportes.php'; ?>
            <?php include __DIR__ . '/views/admin.php'; ?>
            <footer class="page-footer"><span>Salud UNEFA</span><span>Gestión de atención universitaria</span></footer>
        </div>
    </main>
    <?php include __DIR__ . '/modals/patient_drawer.php'; ?>
    <?php include __DIR__ . '/modals/consulta_modal.php'; ?>
    <?php include __DIR__ . '/modals/cita_modal.php'; ?>
    <?php include __DIR__ . '/components/footer.php'; ?>
