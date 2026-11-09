<?php
require_once __DIR__ . '/../db/app.php';
require_once __DIR__ . '/../db/admin_auth.php';

$admin = current_admin();
$page_title = 'Dashboard';
$conn = get_db_connection();

$pending_count = $conn->query("SELECT COUNT(*) c FROM staging_queue WHERE status = 'pending'")->fetch_assoc()['c'];
$published_count = $conn->query("SELECT COUNT(*) c FROM conditions WHERE is_published = 1")->fetch_assoc()['c'];
$image_count = $conn->query("SELECT COUNT(*) c FROM image_classifications")->fetch_assoc()['c'];
$flagged_count = $conn->query("SELECT COUNT(*) c FROM image_classifications WHERE is_flagged_false_positive = 1")->fetch_assoc()['c'];
$avg_conf = $conn->query("SELECT AVG(confidence_score) a FROM image_classifications")->fetch_assoc()['a'];
$estimates_count = $conn->query("SELECT COUNT(*) c FROM cost_estimates_log")->fetch_assoc()['c'];

$recent_staging = $conn->query("SELECT id, extracted_condition_name, source_type, confidence_score, created_at FROM staging_queue WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
$recent_images = $conn->query("SELECT id, predicted_label, confidence_score, is_flagged_false_positive, created_at FROM image_classifications ORDER BY created_at DESC LIMIT 5");

$scans_trend_sql = "SELECT DATE(created_at) as scan_date, COUNT(*) as count 
                    FROM image_classifications 
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                    GROUP BY DATE(created_at) 
                    ORDER BY scan_date ASC";
$scans_trend_res = $conn->query($scans_trend_sql);
$scans_dates = [];
$scans_counts = [];
while($r = $scans_trend_res->fetch_assoc()) {
    $scans_dates[] = date('M j', strtotime($r['scan_date']));
    $scans_counts[] = (int)$r['count'];
}

$pred_dist_sql = "SELECT predicted_label, COUNT(*) as count FROM image_classifications GROUP BY predicted_label";
$pred_dist_res = $conn->query($pred_dist_sql);
$pred_labels = [];
$pred_counts = [];
while($r = $pred_dist_res->fetch_assoc()) {
    $label = empty($r['predicted_label']) ? 'Unknown' : ucfirst($r['predicted_label']);
    $pred_labels[] = $label;
    $pred_counts[] = (int)$r['count'];
}

$activity_sql = "
    (SELECT 'Image Scan' as act_type, created_at, CONCAT('Image classified as ', IF(predicted_label='', 'Unknown', predicted_label)) as detail 
     FROM image_classifications ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'Cost Estimate' as act_type, created_at, CONCAT('Estimate for ', species, ' ($', estimated_cost, ')') as detail 
     FROM cost_estimates_log ORDER BY created_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'Staging' as act_type, created_at, CONCAT('New staging item added via ', source_type) as detail 
     FROM staging_queue ORDER BY created_at DESC LIMIT 5)
    ORDER BY created_at DESC LIMIT 10
";
$recent_activity = $conn->query($activity_sql);

include __DIR__ . '/../design/admin_header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="admin-topbar">
    <h1>Dashboard</h1>
    <div class="who">Welcome back, <?= h($admin['full_name']) ?></div>
</div>

<?php if ($msg = flash_get('success')): ?>
    <div class="alert alert-success"><?= h($msg) ?></div>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Pending Staging</div><div class="kpi-value"><?= (int)$pending_count ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Published Conditions</div><div class="kpi-value"><?= (int)$published_count ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Image Scans</div><div class="kpi-value"><?= (int)$image_count ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Flagged Issues</div><div class="kpi-value"><?= (int)$flagged_count ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Cost Estimates</div><div class="kpi-value"><?= (int)$estimates_count ?></div></div>
</div>

<div class="split-screen" style="display:flex; gap: 20px; margin-top:20px; flex-wrap: wrap;">
    <div class="panel" style="flex:2; min-width: 400px;">
        <h2>Scans Overview (Last 7 Days)</h2>
        <div style="height: 250px;">
            <canvas id="scansChart"></canvas>
        </div>
    </div>
    <div class="panel" style="flex:1; min-width: 300px;">
        <h2>Prediction Breakdown</h2>
        <div style="height: 250px;">
            <canvas id="predChart"></canvas>
        </div>
    </div>
</div>
<div class="split-screen" style="display:flex; gap: 20px; margin-top:20px; flex-wrap: wrap;">
    
    <div class="panel" style="flex:1; min-width: 300px;">
        <h2>Staging Queue &mdash; Needs Review</h2>
        <table class="data-table" style="width: 100%;">
            <thead><tr><th>Condition</th><th>Source</th><th>Conf.</th></tr></thead>
            <tbody>
            <?php if ($recent_staging->num_rows === 0): ?><tr><td colspan="3">Queue is clear.</td></tr><?php endif; ?>
            <?php while ($row = $recent_staging->fetch_assoc()): ?>
                <tr>
                    <td><a href="conditions.php?view=staging&id=<?= (int)$row['id'] ?>"><?= h($row['extracted_condition_name'] ?: '(untitled)') ?></a></td>
                    <td><?= h($row['source_type']) ?></td>
                    <td><?= $row['confidence_score'] !== null ? round($row['confidence_score'], 1) . '%' : '-' ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <a href="conditions.php?view=staging" class="btn btn-primary btn-sm" style="margin-top:10px; display: inline-block;">Full queue &rarr;</a>
    </div>

    <div class="panel" style="flex:1; min-width: 300px;">
        <h2>Recent Image Scans</h2>
        <table class="data-table" style="width: 100%;">
            <thead><tr><th>Prediction</th><th>Conf.</th><th>Flagged</th></tr></thead>
            <tbody>
            <?php if ($recent_images->num_rows === 0): ?><tr><td colspan="3">No scans yet.</td></tr><?php endif; ?>
            <?php while ($row = $recent_images->fetch_assoc()): ?>
                <tr>
                    <td style="text-transform:capitalize;"><?= h($row['predicted_label'] ?: 'Unknown') ?></td>
                    <td><?= round($row['confidence_score'], 1) ?>%</td>
                    <td>
                        <?php if($row['is_flagged_false_positive']): ?>
                            <span style="color:red; font-weight:bold;">Yes</span>
                        <?php else: ?>
                            No
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <a href="/pet_system_real/admin/image-review.php" class="btn btn-primary btn-sm" style="margin-top:10px; display: inline-block;">Image review &rarr;</a>
    </div>

    <div class="panel" style="flex:1; min-width: 300px;">
        <h2>System Activity</h2>
        <ul style="list-style:none; padding:0; margin:0;">
            <?php if ($recent_activity->num_rows === 0): ?>
                <li>No recent activity to display.</li>
            <?php endif; ?>
            
            <?php while ($act = $recent_activity->fetch_assoc()): ?>
                <li style="margin-bottom:12px; border-bottom:1px solid #eee; padding-bottom:8px;">
                    <div style="font-size: 0.85em; color: #777; margin-bottom: 3px;">
                        <?= date('M j, Y, g:i a', strtotime($act['created_at'])) ?>
                    </div>
                    <div>
                        <strong style="color: #333;"><?= h($act['act_type']) ?>:</strong> 
                        <span style="color: #555;"><?= h($act['detail']) ?></span>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const scansCtx = document.getElementById('scansChart').getContext('2d');
    const scansLabels = <?= json_encode($scans_dates) ?>;
    const scansData = <?= json_encode($scans_counts) ?>;
    
    new Chart(scansCtx, {
        type: 'line',
        data: {
            labels: scansLabels.length ? scansLabels : ['No Data'],
            datasets: [{
                label: 'Image Scans Processed',
                data: scansData.length ? scansData : [0],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#4e73df',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    const predCtx = document.getElementById('predChart').getContext('2d');
    const predLabels = <?= json_encode($pred_labels) ?>;
    const predData = <?= json_encode($pred_counts) ?>;

    new Chart(predCtx, {
        type: 'doughnut',
        data: {
            labels: predLabels.length ? predLabels : ['No Data'],
            datasets: [{
                data: predData.length ? predData : [1],
                backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e', '#858796'], // Healthy, Diseased, Uncertain, Unknown/Grey
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../design/admin_footer.php'; ?>