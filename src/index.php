<?php include 'components/head.php'; ?>
<body class="app-body antialiased">
    <a class="skip-link" href="#mainContent">Ir al contenido</a>
    <?php include 'components/sidebar.php'; ?>
    <main id="mainContent" class="app-main" tabindex="-1">
        <div class="page-shell">
            <?php include 'components/header.php'; ?>
            <?php include 'views/dashboard.php'; ?>
            <?php include 'views/pacientes.php'; ?>
            <?php include 'views/citas.php'; ?>
            <?php include 'views/reportes.php'; ?>
            <footer class="page-footer"><span>Salud UNEFA</span><span>Gestión de atención universitaria</span></footer>
        </div>
    </main>
    <?php include 'modals/patient_drawer.php'; ?>
    <?php include 'modals/consulta_modal.php'; ?>
    <?php include 'modals/cita_modal.php'; ?>
    <?php include 'components/footer.php'; ?>
