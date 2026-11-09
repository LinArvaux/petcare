<?php
require_once __DIR__ . '/../db/app.php';
$page_title = 'Symptom Search';
$conn = get_db_connection();

$search_query = trim($_GET['q'] ?? '');
$results = [];

if ($search_query !== '') {
    $stmt = $conn->prepare("
        SELECT condition_name, summary, symptoms, recommended_action, severity 
        FROM conditions 
        WHERE is_published = 1 
        AND (condition_name LIKE ? OR symptoms LIKE ? OR summary LIKE ?)
        ORDER BY condition_name ASC
    ");
    $like_term = '%' . $search_query . '%';
    $stmt->bind_param("sss", $like_term, $like_term, $like_term);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}

include __DIR__ . '/../design/header.php';
?>

<div class="form-panel" style="max-width: 800px;">
    <h2>Knowledge Base & Symptom Search</h2>
    <p style="color: var(--bark-700); margin-bottom: 20px;">Search our vet-approved database for symptoms, skin issues, and general pet health advice.</p>
    
    <form method="GET" action="./symptom-search.php" style="display: flex; gap: 10px; margin-bottom: 30px;">
        <input type="text" name="q" value="<?= h($search_query) ?>" placeholder="e.g., itching, red spots, ticks..." style="flex: 1;" required>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <div class="search-results">
        <?php if ($search_query !== '' && empty($results)): ?>
            <div class="alert alert-error">No matching conditions found for "<?= h($search_query) ?>".</div>
        <?php elseif (!empty($results)): ?>
            <h3 style="color: var(--moss-900); margin-bottom: 15px;">Results for "<?= h($search_query) ?>"</h3>
            <?php foreach ($results as $item): ?>
                <div class="card" style="margin-bottom: 15px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3 style="margin:0; color:var(--moss-900);"><?= h($item['condition_name']) ?></h3>
                        <span class="badge badge-<?= h($item['severity']) ?>"><?= h($item['severity']) ?></span>
                    </div>
                    <p style="margin-top:10px;"><strong>Overview:</strong> <?= nl2br(h($item['summary'])) ?></p>
                    <p><strong>Common Symptoms:</strong> <?= nl2br(h($item['symptoms'])) ?></p>
                    <div class="alert alert-success" style="margin-top: 10px;">
                        <strong>Recommended Action:</strong> <?= nl2br(h($item['recommended_action'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../design/footer.php'; ?>