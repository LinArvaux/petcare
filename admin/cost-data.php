<?php
require_once __DIR__ . '/../db/app.php';
require_once __DIR__ . '/../db/admin_auth.php';

$admin = current_admin();
$page_title = 'Cost Data Logs & Model Training';
$conn = get_db_connection();

$upload_message = '';
$csv_path = __DIR__ . '/../ml/historical_billing_records.csv';

// --- 1. Handle CSV Upload and Validation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['training_data'])) {
    $file = $_FILES['training_data'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpName = $file['tmp_name'];
        $handle = fopen($tmpName, 'r');
        
        if ($handle !== FALSE) {
            $headers = fgetcsv($handle);
            $expected_headers = ['species', 'disease_name', 'severity_level', 'pet_weight_kg', 'treatment_duration_days', 'clinic_tier', 'actual_total_cost'];
            
            if ($headers === $expected_headers) {
                if (move_uploaded_file($tmpName, $csv_path)) {
                    $command = escapeshellcmd("python3 " . __DIR__ . "/../ml/train_cost_model.py");
                    $output = shell_exec($command . " 2>&1"); 
                    $upload_message = "<div class='alert alert-success'>Data uploaded successfully. Model retrained! <br><small>Log: $output</small></div>";
                } else {
                    $upload_message = "<div class='alert alert-error'>Failed to save the uploaded file.</div>";
                }
            } else {
                $upload_message = "<div class='alert alert-error'>Invalid CSV structure. Please ensure headers match the required format.</div>";
            }
            fclose($handle);
        }
    } else {
        $upload_message = "<div class='alert alert-error'>Error uploading file. Code: " . $file['error'] . "</div>";
    }
}

// --- 2. Data Analytics: Parse CSV for Visualization ---
$csv_data = [];
$species_count = ['dog' => 0, 'cat' => 0];
$disease_costs = [];
$severity_costs = [];
$tier_costs = [];

if (file_exists($csv_path) && ($handle = fopen($csv_path, 'r')) !== FALSE) {
    $headers = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== FALSE) {
        if (count($headers) == count($row)) {
            $data = array_combine($headers, $row);
            $csv_data[] = $data;

            // Aggregate Species Count
            $species = strtolower($data['species']);
            if (isset($species_count[$species])) $species_count[$species]++;

            $cost = floatval($data['actual_total_cost']);

            // Aggregate Average Cost by Disease
            $disease = $data['disease_name'];
            if (!isset($disease_costs[$disease])) $disease_costs[$disease] = ['sum' => 0, 'count' => 0];
            $disease_costs[$disease]['sum'] += $cost;
            $disease_costs[$disease]['count']++;

            // Aggregate Average Cost by Severity
            $severity = $data['severity_level'];
            if (!isset($severity_costs[$severity])) $severity_costs[$severity] = ['sum' => 0, 'count' => 0];
            $severity_costs[$severity]['sum'] += $cost;
            $severity_costs[$severity]['count']++;

            // Aggregate Average Cost by Tier
            $tier = $data['clinic_tier'];
            if (!isset($tier_costs[$tier])) $tier_costs[$tier] = ['sum' => 0, 'count' => 0];
            $tier_costs[$tier]['sum'] += $cost;
            $tier_costs[$tier]['count']++;
        }
    }
    fclose($handle);
}

// Format Data for Chart.js
function formatChartData($array) {
    $labels = []; $data = [];
    foreach ($array as $key => $val) {
        $labels[] = $key;
        $data[] = round($val['sum'] / $val['count'], 2);
    }
    return ['labels' => $labels, 'data' => $data];
}

$chart_disease = formatChartData($disease_costs);
$chart_severity = formatChartData($severity_costs);
$chart_tier = formatChartData($tier_costs);

// Fetch recent queries for the bottom table
$rows = $conn->query("SELECT * FROM cost_estimates_log ORDER BY created_at DESC LIMIT 50");

include __DIR__ . '/../design/admin_header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="admin-topbar">
    <h1>Cost Estimate Logs & Model Training</h1>
</div>

<?= $upload_message ?>

<!-- The Upload Interface -->
<div class="panel" style="margin-bottom: 20px;">
    <h2>Upload New Billing Data</h2>
    <p style="color: var(--bark-700);">Upload a CSV file to continuously train the regression model with new local clinic data.</p>
    <form method="POST" enctype="multipart/form-data" style="margin-top: 15px; display: flex; gap: 10px; align-items: center;">
        <input type="file" name="training_data" accept=".csv" required class="form-control" style="max-width: 400px;">
        <button type="submit" class="btn btn-primary">Upload & Retrain Model</button>
    </form>
    <small style="display:block; margin-top:10px; color: gray;">
        <strong>Required Headers:</strong> species, disease_name, severity_level, pet_weight_kg, treatment_duration_days, clinic_tier, actual_total_cost
    </small>
</div>

<!-- Data Analytics Visualization -->
<div class="panel" style="margin-bottom: 20px;">
    <h2>Current Training Data Overview</h2>
    <p style="color: var(--bark-700); margin-bottom: 20px;">Analytics derived from the active regression dataset (<?= count($csv_data) ?> total records).</p>
    
    <?php if (empty($csv_data)): ?>
        <div class="alert alert-error">No training data available yet. Please upload a CSV to view analytics.</div>
    <?php else: ?>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <!-- Species Distribution -->
            <div style="flex: 1; min-width: 250px; background: var(--cream); padding: 15px; border-radius: 8px;">
                <h3 style="text-align: center; font-size: 1rem; color: var(--bark-900);">Species Distribution</h3>
                <div style="height: 200px;"><canvas id="speciesChart"></canvas></div>
            </div>
            
            <!-- Avg Cost by Disease -->
            <div style="flex: 2; min-width: 350px; background: var(--cream); padding: 15px; border-radius: 8px;">
                <h3 style="text-align: center; font-size: 1rem; color: var(--bark-900);">Average Cost by Disease (₱)</h3>
                <div style="height: 200px;"><canvas id="diseaseChart"></canvas></div>
            </div>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
            <!-- Avg Cost by Tier -->
            <div style="flex: 1; min-width: 300px; background: var(--cream); padding: 15px; border-radius: 8px;">
                <h3 style="text-align: center; font-size: 1rem; color: var(--bark-900);">Cost Escalation by Clinic Tier</h3>
                <div style="height: 200px;"><canvas id="tierChart"></canvas></div>
            </div>
            
            <!-- Avg Cost by Severity -->
            <div style="flex: 1; min-width: 300px; background: var(--cream); padding: 15px; border-radius: 8px;">
                <h3 style="text-align: center; font-size: 1rem; color: var(--bark-900);">Cost Escalation by Severity</h3>
                <div style="height: 200px;"><canvas id="severityChart"></canvas></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Queries Log -->
<div class="panel">
    <h2>Recent User Queries</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Query ID</th>
                <th>Disease Searched</th>
                <th>Quoted Base Cost (₱)</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows->num_rows === 0): ?>
                <tr><td colspan="4">No cost estimates generated yet.</td></tr>
            <?php endif; ?>
            <?php while ($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['id'] ?></td>
                    <td><?= h($row['disease_name']) ?></td>
                    <td>₱<?= number_format($row['estimated_cost'], 2) ?></td>
                    <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hasData = <?= !empty($csv_data) ? 'true' : 'false' ?>;
    if (!hasData) return;

    // 1. Species Doughnut Chart
    new Chart(document.getElementById('speciesChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Dogs', 'Cats'],
            datasets: [{
                data: [<?= $species_count['dog'] ?>, <?= $species_count['cat'] ?>],
                backgroundColor: ['#4c7a42', '#d98e3c']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 2. Disease Bar Chart
    new Chart(document.getElementById('diseaseChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_disease['labels']) ?>,
            datasets: [{
                label: 'Average Cost (₱)',
                data: <?= json_encode($chart_disease['data']) ?>,
                backgroundColor: '#8fb48a'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 3. Clinic Tier Line Chart
    new Chart(document.getElementById('tierChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_tier['labels']) ?>,
            datasets: [{
                label: 'Average Cost (₱)',
                data: <?= json_encode($chart_tier['data']) ?>,
                backgroundColor: '#9a7150'
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 4. Severity Escalation Chart
    new Chart(document.getElementById('severityChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_severity['labels']) ?>,
            datasets: [{
                label: 'Average Cost (₱)',
                data: <?= json_encode($chart_severity['data']) ?>,
                borderColor: '#b34a3a',
                backgroundColor: 'rgba(179, 74, 58, 0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
});
</script>

<?php include __DIR__ . '/../design/admin_footer.php'; ?>