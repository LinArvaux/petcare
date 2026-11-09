<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? h($page_title) . ' | ' : '' ?>PetCare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../design/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="./index.php" class="brand"><span class="paw">🐾</span> PetCare</a>

<nav class="main-nav">
    <a href="./index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Home</a>
    <a href="./symptom-search.php" class="<?= $current === 'symptom-search.php' ? 'active' : '' ?>">Symptom Search</a>
    <a href="./image-classifier.php" class="<?= $current === 'image-classifier.php' ? 'active' : '' ?>">Skin Check</a>
    <a href="./cost-estimator.php" class="<?= $current === 'cost-estimator.php' ? 'active' : '' ?>">Cost Estimate</a>
    
   
    <a href="./nearby-vets.php" class="<?= $current === 'nearby-vets.php' ? 'active' : '' ?>">Find a Vet</a>
</nav>
    </div>
</header>
<main class="container" style="padding-top: 26px; padding-bottom: 20px;">
<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?= h($msg) ?></div>
<?php endif; if ($msg = flash_get('error')): ?>
    <div class="alert alert-error"><?= h($msg) ?></div>
<?php endif; ?>