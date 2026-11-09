<?php
require_once __DIR__ . '/../db/app.php';
require_once __DIR__ . '/../db/admin_auth.php';

$admin = current_admin();
$page_title = 'Conditions Management';
$conn = get_db_connection();

// Ensure columns exist for our new features (Recycle Bin & Species tracking)
$conn->query("ALTER TABLE conditions ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL");
$conn->query("ALTER TABLE staging_queue ADD COLUMN IF NOT EXISTS extracted_species ENUM('dog','cat','both','other') DEFAULT 'both'");

// Automated Cleanup (Auto-delete after 30 days)
$conn->query("DELETE FROM conditions WHERE deleted_at IS NOT NULL AND deleted_at < NOW() - INTERVAL 30 DAY");
$conn->query("DELETE FROM staging_queue WHERE status = 'rejected' AND reviewed_at < NOW() - INTERVAL 30 DAY");

// ==========================================
// HANDLE POST ACTIONS
// ==========================================
if (is_post() && csrf_verify()) {
    $action = $_POST['action_type'] ?? '';

    // 1. Unpublish
    if ($action === 'unpublish') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE conditions SET is_published = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        flash_set('success', "Condition has been unpublished.");
        redirect('./conditions.php');
    }
    
    // 2. Edit Published Condition
    if ($action === 'edit_condition') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['condition_name']);
        $species = $_POST['species'] ?? 'both';
        $summary = trim($_POST['summary']);
        $symptoms = trim($_POST['symptoms']);
        $rec_action = trim($_POST['recommended_action']);
        $severity = $_POST['severity'];
        
        $stmt = $conn->prepare("UPDATE conditions SET condition_name=?, species=?, summary=?, symptoms=?, recommended_action=?, severity=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $species, $summary, $symptoms, $rec_action, $severity, $id);
        $stmt->execute();
        flash_set('success', "Condition updated successfully.");
        redirect('./conditions.php');
    }

    // 3. Move to Recycle Bin (Soft Delete)
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE conditions SET deleted_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        flash_set('success', "Condition moved to Recycle Bin.");
        redirect('./conditions.php');
    }

    // 4. Restore from Recycle Bin
    if ($action === 'restore_condition') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE conditions SET deleted_at = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        flash_set('success', "Condition restored.");
        redirect('./conditions.php?view=recycle');
    }
    
    if ($action === 'restore_staging') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE staging_queue SET status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        flash_set('success', "Staging item returned to pending queue.");
        redirect('./conditions.php?view=recycle');
    }

    // 5. Delete Forever (Hard Delete)
    if ($action === 'hard_delete_condition') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM conditions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        flash_set('success', "Condition permanently deleted.");
        redirect('./conditions.php?view=recycle');
    }
    
    if ($action === 'hard_delete_staging') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM staging_queue WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        flash_set('success', "Staging item permanently deleted.");
        redirect('./conditions.php?view=recycle');
    }

    // 6. Review Staging Item
    if ($action === 'review_decision') {
        $id = (int)($_POST['id'] ?? 0);
        $condition_name = trim($_POST['condition_name'] ?? '');
        $species = $_POST['species'] ?? 'both';
        $summary = trim($_POST['summary'] ?? '');
        $symptoms = trim($_POST['symptoms'] ?? '');
        $rec_action = trim($_POST['action'] ?? '');
        $severity = $_POST['severity'] ?? 'medium';
        $decision = $_POST['decision'] ?? 'save';

        $stmt = $conn->prepare('UPDATE staging_queue SET extracted_condition_name=?, extracted_species=?, extracted_summary=?, extracted_symptoms=?, extracted_action=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?');
        $stmt->bind_param('ssssssi', $condition_name, $species, $summary, $symptoms, $rec_action, $admin['id'], $id);
        $stmt->execute();
        $stmt->close();

        if ($decision === 'approve') {
            $stmt = $conn->prepare('INSERT INTO conditions (condition_name, species, summary, symptoms, recommended_action, severity, is_published, source_staging_id) VALUES (?, ?, ?, ?, ?, ?, 1, ?)');
            $stmt->bind_param('ssssssi', $condition_name, $species, $summary, $symptoms, $rec_action, $severity, $id);
            $stmt->execute();
            
            $conn->query("UPDATE staging_queue SET status = 'approved' WHERE id = $id");
            flash_set('success', "Entry approved and published.");
        } elseif ($decision === 'reject') {
            $conn->query("UPDATE staging_queue SET status = 'rejected' WHERE id = $id");
            flash_set('success', "Entry rejected and moved to Recycle Bin.");
        }
        redirect('./conditions.php?view=staging');
    }

    // 7. Ingest New Source (Universal AI Extractor)
    if ($action === 'ingest_source') {
        $source_type = $_POST['source_type'] ?? 'url';
        $api_key = 'AQ.Ab8RN6J_xnDFDkYKKYolsTKEWVy1lZq0xP2svqcdZDvy8nkMYg'; 
        
        $prompt = "You are a veterinary assistant AI. Extract all mentioned pet diseases or conditions from the provided content. 
        Respond ONLY with a valid JSON array. Do not include markdown formatting.
        Each object in the array must have exactly these keys: 
        'condition_name' (string), 'species' (must be exactly 'dog', 'cat', 'both', or 'other'), 'summary' (string), 'symptoms' (string, comma separated), and 'action' (string, recommended action). 
        If no diseases are found, return [].";

        $parts = [];
        $source_reference = "";

        if ($source_type === 'url') {
            $url = trim($_POST['url'] ?? '');
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                flash_set('error', 'Please provide a valid URL.');
                redirect('./conditions.php'); exit;
            }
            $source_reference = $url;
            $context = stream_context_create(['http' => ['method' => 'GET', 'header' => "User-Agent: Mozilla/5.0\r\n"]]);
            $html_content = @file_get_contents($url, false, $context);
            
            if (!$html_content) {
                flash_set('error', 'Failed to fetch the URL content. The site might be blocking requests.');
                redirect('./conditions.php'); exit;
            }

            $html_content = preg_replace('@<script[^>]*?>.*?</script>@si', '', $html_content);
            $html_content = preg_replace('@<style[^>]*?>.*?</style>@si', '', $html_content);
            $clean_text = preg_replace('/\s+/', ' ', strip_tags($html_content));
            $clean_text = mb_substr($clean_text, 0, 50000);

            $parts[] = ["text" => $prompt . " Text to analyze: " . $clean_text];
        } 
        elseif ($source_type === 'pdf') {
            if (empty($_FILES) || !isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
                flash_set('error', 'File upload failed. The file might be too large for the server to process.');
                redirect('./conditions.php'); exit;
            }
            if ($_FILES['pdf_file']['type'] !== 'application/pdf') {
                flash_set('error', 'Invalid file format. Please upload a valid PDF file.');
                redirect('./conditions.php'); exit;
            }
            $max_size = 10 * 1024 * 1024; // 10 MB limit
            if ($_FILES['pdf_file']['size'] > $max_size) {
                flash_set('error', 'PDF is too large. Please upload a file smaller than 10MB.');
                redirect('./conditions.php'); exit;
            }

            $source_reference = "Uploaded PDF: " . $_FILES['pdf_file']['name'];
            $pdf_base64 = base64_encode(file_get_contents($_FILES['pdf_file']['tmp_name']));
            
            $parts[] = ["text" => $prompt];
            $parts[] = ["inlineData" => ["mimeType" => "application/pdf", "data" => $pdf_base64]];
        }

        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=" . $api_key;
        $data = ["contents" => [["parts" => $parts]], "generationConfig" => ["response_mime_type" => "application/json"]];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            flash_set('error', 'cURL Error: ' . curl_error($ch));
            redirect('./conditions.php'); exit;
        }
        curl_close($ch);

        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            flash_set('error', "Google API Error ($http_code): " . ($error_data['error']['message'] ?? 'Unknown error.'));
            redirect('./conditions.php'); exit;
        }

        $result_data = json_decode($response, true);
        
        if (isset($result_data['candidates'][0]['content']['parts'][0]['text'])) {
            $ai_json_string = trim(str_replace(['```json', '```'], '', $result_data['candidates'][0]['content']['parts'][0]['text']));
            $extracted_conditions = json_decode($ai_json_string, true);

            if (is_array($extracted_conditions) && count($extracted_conditions) > 0) {
                
                $stmt = $conn->prepare('INSERT INTO staging_queue (source_type, source_reference, extracted_condition_name, extracted_species, extracted_summary, extracted_symptoms, extracted_action, status, submitted_by) VALUES (?, ?, ?, ?, ?, ?, ?, "pending", ?)');
                $check_stmt = $conn->prepare('SELECT 1 FROM conditions WHERE LOWER(condition_name) = LOWER(?) UNION SELECT 1 FROM staging_queue WHERE LOWER(extracted_condition_name) = LOWER(?)');
                
                $count = 0; $skipped = 0;

                foreach ($extracted_conditions as $cond) {
                    $c_name = mb_substr($cond['condition_name'] ?? 'Unknown', 0, 150);
                    
                    $check_stmt->bind_param('ss', $c_name, $c_name);
                    $check_stmt->execute();
                    if ($check_stmt->get_result()->num_rows > 0) { $skipped++; continue; }

                    $c_spc = in_array(strtolower($cond['species'] ?? ''), ['dog','cat','both','other']) ? strtolower($cond['species']) : 'both';
                    $c_sum = $cond['summary'] ?? 'No summary provided.';
                    $c_sym = $cond['symptoms'] ?? '';
                    $c_act = $cond['action'] ?? '';
                    
                    $stmt->bind_param('sssssssi', $source_type, $source_reference, $c_name, $c_spc, $c_sum, $c_sym, $c_act, $admin['id']);
                    $stmt->execute();
                    $count++;
                }
                
                $msg = "Successfully extracted $count new conditions.";
                if ($skipped > 0) $msg .= " (Skipped $skipped duplicates).";
                flash_set('success', $msg);
                redirect('./conditions.php?view=staging'); exit;
            } else {
                flash_set('error', 'AI found no conditions in this source.');
            }
        } else {
            flash_set('error', 'Failed to read response from the AI extraction service.');
        }
        redirect('./conditions.php');
    }
}

// ==========================================
// VIEW ROUTING & SEARCH/SORT LOGIC
// ==========================================
$view = $_GET['view'] ?? 'published';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'date_desc';

$safe_search = $conn->real_escape_string($search);
$search_sql_cond = $search ? " AND condition_name LIKE '%$safe_search%'" : "";
$search_sql_stage = $search ? " AND extracted_condition_name LIKE '%$safe_search%'" : "";

// Define sorting logic based on the view
$order_sql_cond = match($sort) {
    'name_asc' => 'ORDER BY condition_name ASC',
    'name_desc' => 'ORDER BY condition_name DESC',
    'date_asc' => 'ORDER BY created_at ASC',
    default => 'ORDER BY created_at DESC', // date_desc
};

$order_sql_stage = match($sort) {
    'name_asc' => 'ORDER BY extracted_condition_name ASC',
    'name_desc' => 'ORDER BY extracted_condition_name DESC',
    'date_asc' => 'ORDER BY created_at ASC',
    default => 'ORDER BY created_at DESC', 
};

// Fetch data
if ($view === 'published') {
    $rows = $conn->query("SELECT * FROM conditions WHERE is_published = 1 AND deleted_at IS NULL $search_sql_cond $order_sql_cond");
} elseif ($view === 'staging') {
    $rows = $conn->query("SELECT * FROM staging_queue WHERE status = 'pending' $search_sql_stage $order_sql_stage");
} elseif ($view === 'recycle') {
    // For recycle bin, we typically just order by the deleted/rejected date
    $del_conditions = $conn->query("SELECT * FROM conditions WHERE deleted_at IS NOT NULL $search_sql_cond ORDER BY deleted_at DESC");
    $rej_staging = $conn->query("SELECT * FROM staging_queue WHERE status = 'rejected' $search_sql_stage ORDER BY reviewed_at DESC");
}

include __DIR__ . '/../design/admin_header.php';
?>

<style>
/* Modal Base Styles */
.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
.modal-content { background: #fff; padding: 25px; border-radius: 8px; width: 500px; max-width: 90%; box-shadow: 0 4px 15px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.modal-close { cursor: pointer; font-size: 1.2rem; font-weight: bold; border: none; background: none; }
.btn-group { display: flex; gap: 10px; margin-bottom: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
/* .search-bar { display: flex; gap: 10px; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px; align-items: center; } */
.search-bar { display: flex; gap: 10px; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px; align-items: center; width: 100%; box-sizing: border-box; }
#loadingOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 9999; flex-direction: column; justify-content: center; align-items: center; }
.spinner { border: 6px solid #f3f3f3; border-top: 6px solid #2e7d32; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin-bottom: 15px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<div class="admin-topbar">
    <h1>Conditions Management</h1>
</div>

<?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= h($msg) ?></div><?php endif; ?>

<!-- Top Navigation Buttons -->
<div class="btn-group">
    <a href="?view=published" class="btn <?= $view === 'published' ? 'btn-primary' : 'btn-secondary' ?>">Published</a>
    <a href="?view=staging" class="btn <?= $view === 'staging' ? 'btn-primary' : 'btn-secondary' ?>">Unpublished (Staging)</a>
    <a href="?view=recycle" class="btn <?= $view === 'recycle' ? 'btn-primary' : 'btn-secondary' ?>">Recycle Bin</a>
    <button onclick="document.getElementById('ingestModal').style.display='flex'" class="btn btn-primary" style="margin-left: auto;">+ New Source</button>
</div>

<!-- Search and Sort Form -->
<form class="search-bar" method="GET" action="conditions.php">
    <input type="hidden" name="view" value="<?= h($view) ?>">
    
    <!-- flex: 1 makes this input take up all remaining empty space -->
    <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search conditions..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; min-width: 250px;">
    
    <!-- Fixed width and flex-shrink: 0 keeps the select box compact -->
    <select name="sort" style="width: 160px; flex-shrink: 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
        <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Date: Newest</option>
        <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Date: Oldest</option>
        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name: A-Z</option>
        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name: Z-A</option>
    </select>
    
    <button type="submit" class="btn btn-primary" style="flex-shrink: 0; padding: 10px 20px;">Search</button>
    <?php if ($search || $sort !== 'date_desc'): ?>
        <a href="?view=<?= h($view) ?>" class="btn btn-secondary" style="flex-shrink: 0; padding: 10px 20px; text-decoration: none;">Clear</a>
    <?php endif; ?>
</form>

<!-- VIEW: PUBLISHED -->
<?php if ($view === 'published'): ?>
<div class="panel">
    <table class="data-table">
        <thead>
            <tr><th>Condition Name</th><th>Species</th><th>Severity</th><th>Published Date</th><th style="width: 250px;">Actions</th></tr>
        </thead>
        <tbody>
            <?php if ($rows->num_rows === 0): ?><tr><td colspan="5">No conditions found.</td></tr><?php endif; ?>
            <?php while ($row = $rows->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= h($row['condition_name']) ?></strong></td>
                    <td style="text-transform: capitalize;"><?= h($row['species']) ?></td>
                    <td><span class="pill pill-<?= h($row['severity']) ?>"><?= h($row['severity']) ?></span></td>
                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td style="display: flex; gap: 5px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)'>Edit</button>
                        <form method="POST" onsubmit="return confirm('Unpublish this condition?');" style="margin:0;">
                            <?= csrf_field() ?><input type="hidden" name="action_type" value="unpublish"><input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm">Unpublish</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Move this condition to the Recycle Bin?');" style="margin:0;">
                            <?= csrf_field() ?><input type="hidden" name="action_type" value="delete"><input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-reject btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- VIEW: STAGING QUEUE -->
<?php elseif ($view === 'staging'): ?>
<div class="panel">
    <table class="data-table">
        <thead><tr><th>Condition</th><th>Species</th><th>Source</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if ($rows->num_rows === 0): ?><tr><td colspan="5">No items in the staging queue.</td></tr><?php endif; ?>
        <?php while ($row = $rows->fetch_assoc()): ?>
            <tr>
                <td><?= h($row['extracted_condition_name']) ?></td>
                <td style="text-transform: capitalize;"><?= h($row['extracted_species']) ?></td>
                <td><?= h($row['source_type']) ?></td>
                <td><span class="pill pill-<?= h($row['status']) ?>"><?= h($row['status']) ?></span></td>
                <td><button type="button" class="btn btn-secondary btn-sm" onclick='openReviewModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)'>Review</button></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- VIEW: RECYCLE BIN -->
<?php elseif ($view === 'recycle'): ?>
<div class="panel">
    <h3 style="margin-top: 0;">Deleted Conditions</h3>
    <table class="data-table" style="margin-bottom: 30px;">
        <thead><tr><th>Condition Name</th><th>Species</th><th>Deleted At</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if ($del_conditions->num_rows === 0): ?><tr><td colspan="4">No deleted conditions found.</td></tr><?php endif; ?>
            <?php while ($row = $del_conditions->fetch_assoc()): ?>
                <tr>
                    <td><?= h($row['condition_name']) ?></td>
                    <td style="text-transform: capitalize;"><?= h($row['species']) ?></td>
                    <td><?= date('M j, Y H:i', strtotime($row['deleted_at'])) ?></td>
                    <td style="display: flex; gap: 5px;">
                        <form method="POST" style="margin:0;">
                            <?= csrf_field() ?><input type="hidden" name="action_type" value="restore_condition"><input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Restore</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Permanently delete this condition?');" style="margin:0;">
                            <?= csrf_field() ?><input type="hidden" name="action_type" value="hard_delete_condition"><input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-reject btn-sm">Delete Forever</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3>Rejected Staging Items</h3>
    <table class="data-table">
        <thead><tr><th>Extracted Name</th><th>Species</th><th>Rejected At</th><th>Actions</th></tr></thead>
        <tbody>
            <?php if ($rej_staging->num_rows === 0): ?><tr><td colspan="4">No rejected staging items found.</td></tr><?php endif; ?>
            <?php while ($row = $rej_staging->fetch_assoc()): ?>
                <tr>
                    <td><?= h($row['extracted_condition_name']) ?></td>
                    <td style="text-transform: capitalize;"><?= h($row['extracted_species']) ?></td>
                    <td><?= date('M j, Y H:i', strtotime($row['reviewed_at'])) ?></td>
                    <td style="display: flex; gap: 5px;">
                        <form method="POST" style="margin:0;">
                            <?= csrf_field() ?><input type="hidden" name="action_type" value="restore_staging"><input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Restore to Pending</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Permanently delete this staging item?');" style="margin:0;">
                            <?= csrf_field() ?><input type="hidden" name="action_type" value="hard_delete_staging"><input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-reject btn-sm">Delete Forever</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ========================================== -->
<!-- MODALS -->
<!-- ========================================== -->
<div id="loadingOverlay">
    <div class="spinner"></div><h2 style="color: #333;">Extracting AI Knowledge...</h2><p style="color: #666;">This may take 10-20 seconds. Please do not close the page.</p>
</div>

<!-- MODAL: Ingest New Source -->
<div id="ingestModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header"><h2 style="margin:0;">Ingest a New Source</h2><button class="modal-close" onclick="document.getElementById('ingestModal').style.display='none'">&times;</button></div>
        <form method="POST" action="./conditions.php" enctype="multipart/form-data" onsubmit="triggerLoading()">
            <?= csrf_field() ?><input type="hidden" name="action_type" value="ingest_source">
            <div class="form-group"><label>Source Type</label>
                <select name="source_type" id="source_type" onchange="toggleSourceFields()">
                    <option value="url">Web URL (article, vet resource)</option><option value="pdf">PDF Document</option>
                </select>
            </div>
            <div class="form-group" id="url_field"><label>Website URL</label><input type="url" name="url" placeholder="https://example.com/article"></div>
            <div class="form-group" id="pdf_field" style="display: none;"><label>Upload PDF Document (Max 10MB)</label><input type="file" name="pdf_file" accept=".pdf" style="border: none; padding: 0;" onchange="if(this.files[0].size > 10485760){ alert('File is too large! Please select a PDF smaller than 10MB.'); this.value=''; }"></div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Submit for Extraction</button>
        </form>
    </div>
</div>

<!-- MODAL: Edit Published Condition -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header"><h2 style="margin:0;">Edit Condition</h2><button class="modal-close" onclick="document.getElementById('editModal').style.display='none'">&times;</button></div>
        <form method="POST" action="./conditions.php">
            <?= csrf_field() ?><input type="hidden" name="action_type" value="edit_condition"><input type="hidden" name="id" id="edit_id">
            <div class="form-group"><label>Condition Name</label><input type="text" name="condition_name" id="edit_name" required></div>
            <div class="form-group"><label>Species</label>
                <select name="species" id="edit_species">
                    <option value="dog">Dog</option><option value="cat">Cat</option><option value="both">Both</option><option value="other">Other</option>
                </select>
            </div>
            <div class="form-group"><label>Summary</label><textarea name="summary" id="edit_summary" rows="2" required></textarea></div>
            <div class="form-group"><label>Symptoms</label><textarea name="symptoms" id="edit_symptoms" rows="2" required></textarea></div>
            <div class="form-group"><label>Recommended Action</label><textarea name="recommended_action" id="edit_action" rows="2" required></textarea></div>
            <div class="form-group"><label>Severity</label>
                <select name="severity" id="edit_severity">
                    <option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="emergency">Emergency</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Save Changes</button>
        </form>
    </div>
</div>

<!-- MODAL: Review Staging Entry -->
<div id="reviewModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header"><h2 style="margin:0;">Review Entry</h2><button type="button" class="modal-close" onclick="document.getElementById('reviewModal').style.display='none'">&times;</button></div>
        <form method="POST" action="./conditions.php">
            <?= csrf_field() ?><input type="hidden" name="action_type" value="review_decision"><input type="hidden" name="id" id="review_id">
            <div class="form-group"><label>Condition Name</label><input type="text" name="condition_name" id="review_name" required></div>
            <div class="form-group"><label>Species</label>
                <select name="species" id="review_species">
                    <option value="dog">Dog</option><option value="cat">Cat</option><option value="both">Both</option><option value="other">Other</option>
                </select>
            </div>
            <div class="form-group"><label>Summary</label><textarea name="summary" id="review_summary" rows="3" required></textarea></div>
            <div class="form-group"><label>Symptoms</label><textarea name="symptoms" id="review_symptoms" rows="3"></textarea></div>
            <div class="form-group"><label>Recommended Action</label><textarea name="action" id="review_action" rows="3"></textarea></div>
            <div class="form-group"><label>Severity</label>
                <select name="severity" id="review_severity">
                    <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="emergency">Emergency</option>
                </select>
            </div>
            <div style="margin-top:16px; display:flex; gap:10px;">
                <button type="submit" name="decision" value="approve" class="btn btn-primary" style="flex:1;">Approve &amp; Publish</button>
                <button type="submit" name="decision" value="reject" class="btn btn-reject" style="flex:1;" onclick="return confirm('Reject this entry and send to Recycle Bin?');">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSourceFields() {
    var type = document.getElementById('source_type').value;
    document.getElementById('url_field').style.display = (type === 'url') ? 'block' : 'none';
    document.getElementById('pdf_field').style.display = (type === 'pdf') ? 'block' : 'none';
    document.querySelector('input[name="url"]').required = (type === 'url');
    document.querySelector('input[name="pdf_file"]').required = (type === 'pdf');
}

function triggerLoading() {
    document.getElementById('ingestModal').style.display = 'none';
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function openEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.condition_name;
    document.getElementById('edit_species').value = data.species || 'both';
    document.getElementById('edit_summary').value = data.summary;
    document.getElementById('edit_symptoms').value = data.symptoms;
    document.getElementById('edit_action').value = data.recommended_action;
    document.getElementById('edit_severity').value = data.severity;
    document.getElementById('editModal').style.display = 'flex';
}

function openReviewModal(data) {
    document.getElementById('review_id').value = data.id;
    document.getElementById('review_name').value = data.extracted_condition_name || '';
    document.getElementById('review_species').value = data.extracted_species || 'both';
    document.getElementById('review_summary').value = data.extracted_summary || '';
    document.getElementById('review_symptoms').value = data.extracted_symptoms || '';
    document.getElementById('review_action').value = data.extracted_action || '';
    document.getElementById('review_severity').value = data.severity || 'medium';
    document.getElementById('reviewModal').style.display = 'flex';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('ingestModal')) document.getElementById('ingestModal').style.display = "none";
    if (event.target == document.getElementById('editModal')) document.getElementById('editModal').style.display = "none";
    if (event.target == document.getElementById('reviewModal')) document.getElementById('reviewModal').style.display = "none";
}

toggleSourceFields();
</script>

<?php include __DIR__ . '/../design/admin_footer.php'; ?>