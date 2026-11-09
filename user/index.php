<?php
require_once __DIR__ . '/../db/app.php';
$page_title = 'Home';
include __DIR__ . '/../design/header.php';
?>

<div class="hero" style="text-align: center; border-radius: var(--radius-lg); padding: 40px 20px; margin-bottom: 30px;">
    <h1>Welcome to PetCare</h1>
    <p style="font-size: 1.1rem; color: var(--bark-700); max-width: 600px; margin: 0 auto 20px;">Your assistant for identifying pet skin conditions, exploring vet-approved symptoms, and estimating treatment costs.</p>
</div>

<div class="card-grid">
    <a href="./symptom-search.php" class="card">
        <div class="icon-badge">🔍</div>
        <h3>Symptom Search</h3>
        <p>Search our vet-approved knowledge base for symptoms and treatments.</p>
        <span class="btn btn-secondary btn-sm">Search DB &rarr;</span>
    </a>
    <a href="./image-classifier.php" class="card">
        <div class="icon-badge">📷</div>
        <h3>Skin Check AI</h3>
        <p>Upload a photo of a skin issue for an instant AI classification and heatmap.</p>
        <span class="btn btn-secondary btn-sm">Scan Image &rarr;</span>
    </a>
    <a href="./cost-estimator.php" class="card">
        <div class="icon-badge">💰</div>
        <h3>Cost Estimator</h3>
        <p>Get estimated veterinary costs and market comparisons for common diseases.</p>
        <span class="btn btn-secondary btn-sm">Calculate &rarr;</span>
    </a>
</div>

<?php include __DIR__ . '/../design/footer.php'; ?>