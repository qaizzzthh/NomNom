<?php
// src/views/layouts/main.php
// Dipanggil dengan: include, dengan variabel $title, $role sudah di-set
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?? 'NomNom.id' ?> — NomNom.id Food Delivery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@400;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/NomNom.id.svg">
<?= $extraCSS ?? '' ?>
</head>
<body class="role-<?= $role ?? 'public' ?>">
<?php include __DIR__ . '/navbar.php'; ?>
<div class="app-wrapper">
    <?php if (isset($sidebar) && $sidebar): ?>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content with-sidebar">
    <?php else: ?>
    <main class="main-content">
    <?php endif; ?>

    <?php
    $flash_success = flash('success');
    $flash_error = flash('error');
    $flash_info = flash('info');
    if ($flash_success): ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= $flash_success ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
    <div class="alert alert-error"><i class="fa fa-exclamation-circle"></i> <?= $flash_error ?></div>
    <?php endif; ?>
    <?php if ($flash_info): ?>
    <div class="alert alert-info"><i class="fa fa-info-circle"></i> <?= $flash_info ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
    </main>
</div>
<?php include __DIR__ . '/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?= $extraJS ?? '' ?>
</body>
</html>
