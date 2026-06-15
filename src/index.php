<?php include 'components/head.php'; ?>
<body class="text-slate-800 antialiased min-h-screen flex">
    <?php include 'components/sidebar.php'; ?>
    <main class="flex-1 ml-0 md:ml-64 flex flex-col min-h-screen">
        <?php include 'components/header.php'; ?>
        <div class="p-4 sm:p-8 flex-1 flex flex-col gap-6 sm:gap-8 max-w-7xl mx-auto w-full">
            <?php include 'views/dashboard.php'; ?>
            <?php include 'views/pacientes.php'; ?>
            <?php include 'views/citas.php'; ?>
            <?php include 'views/reportes.php'; ?>
        </div>
    </main>
    <?php include 'modals/patient_drawer.php'; ?>
    <?php include 'modals/consulta_modal.php'; ?>
    <?php include 'modals/cita_modal.php'; ?>
    <?php include 'components/footer.php'; ?>
