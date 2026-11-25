<?php
require_once __DIR__ . '/asset.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c234b">
    <meta name="referrer" content="same-origin">
    <title>Salud UNEFA · Gestión médica</title>
    <link rel="icon" type="image/png" href="<?= asset_url('assets/favicon-unefa.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset_url('assets/favicon-unefa.png') ?>">
    <link rel="preload" href="fonts/geist-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset_url('css/tailwind.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('vendor/phosphor/style.css') ?>">
    <script src="<?= asset_url('vendor/sweetalert2.all.min.js') ?>"></script>
    <script src="<?= asset_url('vendor/chart.umd.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset_url('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/vistas.css') ?>">
</head>
