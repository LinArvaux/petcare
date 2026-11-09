<?php
require_once __DIR__ . '/../db/app.php';
require_once __DIR__ . '/../db/admin_auth.php';

$admin = current_admin();
$page_title = 'Dataset & Image Review';
$conn = get_db_connection();

// --- AUTOMATED DATABASE FIXES ---
$conn->query("ALTER TABLE image_classifications MODIFY COLUMN predicted_label VARCHAR(100) NOT NULL");
// Add Advanced Activity Tracking for the Undo Engine
$conn->query("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS target_id INT NULL");
$conn->query("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS target_type VARCHAR(50) NULL");
$conn->query("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS can_undo TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS undo_meta TEXT NULL");

// --- PATH DEFINITIONS ---
$dataset_base = __DIR__ . '/../dataset'; 
$staging_dir = __DIR__ . '/../static/uploads/staging';
$trash_dir = __DIR__ . '/../static/uploads/trash';
if (!is_dir($staging_dir)) mkdir($staging_dir, 0777, true);
if (!is_dir($trash_dir)) mkdir($trash_dir, 0777, true);

// --- HELPER: ADVANCED LOG ACTIVITY ---
function log_activity($conn, $admin_id, $action, $details, $target_id = null, $target_type = null, $can_undo = 0, $undo_meta = null) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action, details, target_id, target_type, can_undo, undo_meta) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issisis", $admin_id, $action, $details, $target_id, $target_type, $can_undo, $undo_meta);
    $stmt->execute();
}

// ==========================================
// HANDLE AJAX & POST ACTIONS
// ==========================================
$current_view = $_POST['current_view'] ?? ($_GET['view'] ?? 'dataset');

// AJAX: Lock all Undos when Retraining Starts
if (isset($_POST['lock_undos']) && csrf_verify()) {
    $conn->query("UPDATE activity_logs SET can_undo = 0");
    exit;
}

// --- START AI SERVER LOGIC ---
if (isset($_POST['start_ai_server'])) {
    if (csrf_verify()) {
        $base_dir = realpath(__DIR__ . '/../');
        $cmd = 'start "PetCare AI Server" /D "' . $base_dir . '" cmd /k "pet_env\Scripts\activate && python ml/app.py"';
        pclose(popen($cmd, "r"));
        
        log_activity($conn, $admin['id'], 'Started AI Server', 'Launched Python Flask backend.');
        flash_set('success', 'AI Server launched! Please check the new Command Prompt window.');
    }
    redirect("./image-review.php?view=" . urlencode($current_view));
}

// --- HANDLE UNDO ACTION ---
if (isset($_POST['undo_action'])) {
    if (csrf_verify()) {
        $log_id = (int)$_POST['log_id'];
        $res = $conn->query("SELECT * FROM activity_logs WHERE id = $log_id AND can_undo = 1");
        if ($log = $res->fetch_assoc()) {
            $tid = (int)$log['target_id'];
            $ttype = $log['target_type'];
            
            if ($ttype === 'dataset_images') {
                $img_res = $conn->query("SELECT * FROM dataset_images WHERE id = $tid");
                if ($img = $img_res->fetch_assoc()) {
                    $filename = basename($img['image_path']);
                    $staging_path = $staging_dir . '/' . $filename;
                    
                    if ($log['action'] === 'Approved Image') {
                        $dataset_path = $dataset_base . '/' . $img['proposed_category'] . '/' . $filename;
                        if (file_exists($dataset_path)) rename($dataset_path, $staging_path);
                    } elseif ($log['action'] === 'Rejected Image') {
                        $trash_path = $trash_dir . '/' . $filename;
                        if (file_exists($trash_path)) rename($trash_path, $staging_path);
                    }
                    $conn->query("UPDATE dataset_images SET status = 'pending' WHERE id = $tid");
                    flash_set('success', 'Action Undone! Image returned to the verification queue.');
                }
            } elseif ($ttype === 'image_classifications' && $log['action'] === 'Corrected AI Result') {
                $dest_path = $log['undo_meta'];
                if ($dest_path && file_exists($dest_path)) unlink($dest_path);
                $conn->query("UPDATE image_classifications SET is_flagged_false_positive = 0, corrected_label = NULL WHERE id = $tid");
                flash_set('success', 'AI correction Undone! Flag removed and image pulled from dataset.');
            }
            // Disable further undos for this specific log
            $conn->query("UPDATE activity_logs SET can_undo = 0 WHERE id = $log_id");
        }
    }
    redirect("./image-review.php?view=activity_log");
}

// --- HANDLE FLAG & CORRECT AI RESULT ---
if (isset($_POST['flag_and_correct'])) {
    if (csrf_verify()) {
        $id = (int)$_POST['classification_id'];
        $correct_label = $_POST['correct_label'];
        $img_path = str_replace('\\', '/', $_POST['image_path']); 
        $original_img_path = str_replace('/heatmap_', '/', $img_path);
        
        $stmt = $conn->prepare("UPDATE image_classifications SET is_flagged_false_positive = 1, corrected_label = ? WHERE id = ?");
        $stmt->bind_param("si", $correct_label, $id);
        $stmt->execute();
        
        $src = realpath(__DIR__ . '/..' . $original_img_path);
        $dest_path = null;
        if ($src && file_exists($src)) {
            $dest_dir = $dataset_base . '/' . $correct_label;
            if (!is_dir($dest_dir)) mkdir($dest_dir, 0777, true);
            $dest_path = $dest_dir . '/' . basename($src);
            copy($src, $dest_path);
        } else {
            $src_fallback = realpath(__DIR__ . '/..' . $img_path);
            if ($src_fallback && file_exists($src_fallback)) {
                $dest_dir = $dataset_base . '/' . $correct_label;
                if (!is_dir($dest_dir)) mkdir($dest_dir, 0777, true);
                $dest_path = $dest_dir . '/' . basename($src_fallback);
                copy($src_fallback, $dest_path);
            }
        }

        // Log with Undo Meta
        log_activity($conn, $admin['id'], 'Corrected AI Result', "Flagged image #$id and assigned to $correct_label.", $id, 'image_classifications', 1, $dest_path);
        flash_set('success', 'Image flagged! It was added to the dataset for future training.');
    }
    redirect("./image-review.php?view=" . urlencode($current_view));
}

// --- HANDLE STAGING UPLOAD ---
if (isset($_POST['upload_dataset_image'])) {
    if (csrf_verify() && isset($_FILES['dataset_file']) && $_FILES['dataset_file']['error'] === UPLOAD_ERR_OK) {
        
        $type = $_POST['dataset_type']; 
        $species = $_POST['species']; 
        $final_folder = '';

        if ($type === 'Diseased') {
            if ($_POST['folder_type'] === 'existing') {
                $disease = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['existing_disease']);
            } else {
                $disease = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $_POST['new_disease_name']));
            }
            $final_folder = 'Diseased/' . $species . '/' . $disease;
        } else {
            $final_folder = 'Healthy/' . $species;
        }

        $ext = pathinfo($_FILES['dataset_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('staging_') . '.' . $ext;
        $target_path = $staging_dir . '/' . $filename;
        
        if (move_uploaded_file($_FILES['dataset_file']['tmp_name'], $target_path)) {
            $stmt = $conn->prepare("INSERT INTO dataset_images (image_path, proposed_category, status, uploaded_by) VALUES (?, ?, 'pending', ?)");
            $stmt->bind_param("ssi", $filename, $final_folder, $admin['id']);
            $stmt->execute();
            
            log_activity($conn, $admin['id'], 'Uploaded Image', "Uploaded image to staging for $final_folder.");
            flash_set('success', 'Image uploaded to staging queue for verification.');
        } else {
            flash_set('error', 'Failed to upload image.');
        }
    }
    redirect("./image-review.php?view=" . urlencode($current_view));
}

// --- HANDLE APPROVE / REJECT ---
if (isset($_POST['review_action'])) {
    if (csrf_verify()) {
        $id = (int)$_POST['image_id'];
        $action = $_POST['review_action'];
        
        $stmt = $conn->prepare("SELECT image_path, proposed_category FROM dataset_images WHERE id = ? AND status = 'pending'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $filename = basename($row['image_path']);
            $local_path = $staging_dir . '/' . $filename;
            
            if ($action === 'Approve') {
                $target_dir = $dataset_base . '/' . $row['proposed_category'];
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $final_path = $target_dir . '/' . $filename;
                if (file_exists($local_path) && rename($local_path, $final_path)) {
                    $conn->query("UPDATE dataset_images SET status = 'approved' WHERE id = $id");
                    log_activity($conn, $admin['id'], 'Approved Image', "Approved image into {$row['proposed_category']}.", $id, 'dataset_images', 1);
                    flash_set('success', 'Approved! Added to ' . h($row['proposed_category']));
                }
            } elseif ($action === 'Reject') {
                $trash_path = $trash_dir . '/' . $filename;
                if (file_exists($local_path) && rename($local_path, $trash_path)) {
                    $conn->query("UPDATE dataset_images SET status = 'trash' WHERE id = $id");
                    log_activity($conn, $admin['id'], 'Rejected Image', "Rejected image intended for {$row['proposed_category']}.", $id, 'dataset_images', 1);
                    flash_set('success', 'Upload rejected and moved to recycle bin.');
                }
            }
        }
    }
    redirect("./image-review.php?view=" . urlencode($current_view));
}

// ==========================================
// VIEW ROUTING & DATA FETCHING
// ==========================================
function get_subfolders($path) {
    return is_dir($path) ? array_map('basename', array_filter(glob($path . '/*'), 'is_dir')) : [];
}

$cat_diseases = get_subfolders($dataset_base . '/Diseased/cat');
$dog_diseases = get_subfolders($dataset_base . '/Diseased/dog');

$browse_folder = $_GET['browse'] ?? null;
$browse_images = [];
if ($browse_folder) {
    // Prevent directory traversal attacks
    $safe_folder = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $browse_folder);
    $full_dir = $dataset_base . '/' . $safe_folder;
    if (is_dir($full_dir)) {
        $files = glob($full_dir . '/*.{jpg,jpeg,png}', GLOB_BRACE);
        foreach($files as $f) {
            $browse_images[] = '/pet_system_real/dataset/' . $safe_folder . '/' . basename($f);
        }
    }
}

if ($current_view === 'dataset') {
    $pending_images = $conn->query("SELECT * FROM dataset_images WHERE status = 'pending' ORDER BY created_at DESC");
} elseif ($current_view === 'ai_review') {
    $classifications = $conn->query("SELECT * FROM image_classifications ORDER BY created_at DESC LIMIT 50");
} elseif ($current_view === 'activity_log') {
    $activities = $conn->query("SELECT a.*, ad.full_name FROM activity_logs a LEFT JOIN admins ad ON a.admin_id = ad.id ORDER BY a.created_at DESC LIMIT 50");
}

include __DIR__ . '/../design/admin_header.php';
?>

<style>
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
.modal-content { background-color: #fff; margin: 5% auto; padding: 20px; border-radius: 8px; max-width: 500px; position: relative; }
.close-btn { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #aaa; }
.close-btn:hover { color: #000; }
.form-group { margin-bottom: 15px; }
.folder-box { background: #f4f6f8; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
.folder-tag { display: inline-block; background: #fff; border: 1px solid #ccc; padding: 3px 8px; border-radius: 4px; margin: 3px; font-size: 0.85em; text-decoration: none; color: #333; cursor: pointer;}
.folder-tag:hover { background: #e0e0e0; }
.btn-group { display: flex; gap: 10px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
</style>

<div class="admin-topbar">
    <h1>Image AI & Dataset</h1>
</div>

<?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= h($msg) ?></div><?php endif; ?>
<div id="retrainOutput"></div>

<!-- Top Navigation Buttons -->
<div class="btn-group">
    <a href="?view=dataset" class="btn <?= $current_view === 'dataset' ? 'btn-primary' : 'btn-secondary' ?>">Dataset Management</a>
    <a href="?view=ai_review" class="btn <?= $current_view === 'ai_review' ? 'btn-primary' : 'btn-secondary' ?>">Review AI</a>
    <a href="?view=activity_log" class="btn <?= $current_view === 'activity_log' ? 'btn-primary' : 'btn-secondary' ?>">Activity Log (Recycle Bin)</a>
    
    <div style="margin-left: auto; display: flex; gap: 10px;">
        <form method="POST" style="margin:0;">
            <?= csrf_field() ?>
            <input type="hidden" name="current_view" value="<?= h($current_view) ?>">
            <button type="submit" name="start_ai_server" class="btn btn-outline" onclick="return confirm('Launch Python server in background?');">Start AI Server</button>
        </form>
        <button id="btnRetrain" class="btn btn-primary" onclick="retrainModel()">Retrain Model</button>
    </div>
</div>

<!-- ========================================== -->
<!-- VIEW: DATASET MANAGEMENT -->
<!-- ========================================== -->
<?php if ($current_view === 'dataset'): ?>
<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
    <div style="flex: 1; min-width: 300px;">
        <div class="panel">
            <h2>Add to Dataset</h2>
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button class="btn btn-primary" onclick="openModal('modalDiseased')">+ Upload Diseased</button>
                <button class="btn btn-outline" onclick="openModal('modalHealthy')">+ Upload Healthy</button>
            </div>
        </div>
        <div class="panel" style="margin-top: 20px;">
            <h2>Current Folders <small style="font-weight:normal; font-size:0.7em; color:gray;">(Click to view images)</small></h2>
            <div class="folder-box">
                <strong>Diseased / Cat /</strong><br>
                <?php foreach($cat_diseases as $f): ?><a href="?view=dataset&browse=Diseased/cat/<?= h($f) ?>" class="folder-tag"><?= h($f) ?></a><?php endforeach; ?>
            </div>
            <div class="folder-box">
                <strong>Diseased / Dog /</strong><br>
                <?php foreach($dog_diseases as $f): ?><a href="?view=dataset&browse=Diseased/dog/<?= h($f) ?>" class="folder-tag"><?= h($f) ?></a><?php endforeach; ?>
            </div>
            <div class="folder-box">
                <strong>Healthy /</strong><br>
                <a href="?view=dataset&browse=Healthy/cat" class="folder-tag">cat</a> 
                <a href="?view=dataset&browse=Healthy/dog" class="folder-tag">dog</a>
            </div>
        </div>
    </div>

    <div style="flex: 2; min-width: 400px;">
        <div class="panel">
            <h2>Verification Queue</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Target Folder</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_images->num_rows === 0): ?>
                        <tr><td colspan="3">No pending uploads.</td></tr>
                    <?php endif; ?>
                    <?php while ($img = $pending_images->fetch_assoc()): $display_name = basename($img['image_path']); ?>
                        <tr>
                            <td>
                                <a href="../static/uploads/staging/<?= h($display_name) ?>" target="_blank">
                                    <img src="../static/uploads/staging/<?= h($display_name) ?>" style="height: 60px; width: 60px; border-radius: 4px; object-fit: cover;">
                                </a>
                            </td>
                            <td><strong><?= h($img['proposed_category']) ?></strong><br><small><?= date('M j, Y g:i A', strtotime($img['created_at'])) ?></small></td>
                            <td>
                                <form method="POST" style="display:flex; gap: 5px;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="current_view" value="<?= h($current_view) ?>">
                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                    <button type="submit" name="review_action" value="Approve" class="btn btn-sm btn-primary">Approve</button>
                                    <button type="submit" name="review_action" value="Reject" class="btn btn-sm btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- VIEW: REVIEW AI -->
<!-- ========================================== -->
<?php elseif ($current_view === 'ai_review'): ?>
<div class="panel" style="margin-bottom: 20px;">
    <h2>Recent AI Predictions</h2>
    <p style="color: var(--bark-700); margin-bottom: 15px;">Review AI results. If the AI was wrong, flag the image and assign the correct category to improve future training.</p>
    <table class="data-table">
        <thead>
            <tr>
                <th>Images (Original & Heatmap)</th>
                <th>AI Prediction</th>
                <th>Confidence</th>
                <th>Date</th>
                <th>Flag & Correct</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($classifications->num_rows === 0): ?>
                <tr><td colspan="5">No images analyzed yet.</td></tr>
            <?php endif; ?>
            <?php while ($row = $classifications->fetch_assoc()): 
                $heatmap_path = str_replace('\\', '/', $row['image_path']);
                $original_path = str_replace('/heatmap_', '/', $heatmap_path);
            ?>
                <tr>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <div style="text-align: center;">
                                <a href="..<?= h($original_path) ?>" target="_blank">
                                    <img src="..<?= h($original_path) ?>" style="height: 50px; width: 50px; border-radius: 4px; object-fit: cover;" title="Original Image" alt="Original">
                                </a>
                                <div style="font-size: 0.7em; color: gray;">Original</div>
                            </div>
                            <div style="text-align: center;">
                                <a href="..<?= h($heatmap_path) ?>" target="_blank">
                                    <img src="..<?= h($heatmap_path) ?>" style="height: 50px; width: 50px; border-radius: 4px; object-fit: cover;" title="AI Heatmap" alt="Heatmap">
                                </a>
                                <div style="font-size: 0.7em; color: gray;">Heatmap</div>
                            </div>
                        </div>
                    </td>
                    <td style="text-transform: capitalize;"><strong><?= h($row['predicted_label']) ?></strong></td>
                    <td><?= round($row['confidence_score'], 1) ?>%</td>
                    <td><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></td>
                    <td>
                        <?php if(!$row['is_flagged_false_positive']): ?>
                            <div id="flag-btn-<?= $row['id'] ?>">
                                <button type="button" class="btn btn-sm btn-outline" onclick="showFlagForm(<?= $row['id'] ?>)">🚩 Flag</button>
                            </div>
                            <form id="flag-form-<?= $row['id'] ?>" method="POST" style="display:none; gap: 5px; align-items: center; flex-wrap: wrap;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="current_view" value="<?= h($current_view) ?>">
                                <input type="hidden" name="classification_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="image_path" value="<?= h($row['image_path']) ?>">
                                
                                <select name="correct_label" required class="form-control" style="width: 150px; font-size: 0.85em; padding: 5px;">
                                    <option value="">Select Correct...</option>
                                    <optgroup label="Healthy">
                                        <option value="Healthy/cat">Healthy / Cat</option>
                                        <option value="Healthy/dog">Healthy / Dog</option>
                                    </optgroup>
                                    <?php if(!empty($cat_diseases)): ?>
                                        <optgroup label="Diseased / Cat">
                                            <?php foreach($cat_diseases as $d): ?><option value="Diseased/cat/<?= h($d) ?>"><?= h($d) ?></option><?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <?php if(!empty($dog_diseases)): ?>
                                        <optgroup label="Diseased / Dog">
                                            <?php foreach($dog_diseases as $d): ?><option value="Diseased/dog/<?= h($d) ?>"><?= h($d) ?></option><?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                                <button type="submit" name="flag_and_correct" class="btn btn-sm btn-reject">Save</button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="hideFlagForm(<?= $row['id'] ?>)">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #b34a3a; font-weight: bold;">Flagged</span><br>
                            <small style="color: gray;">Corrected to: <?= h($row['corrected_label']) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- ========================================== -->
<!-- VIEW: ACTIVITY LOG & RECYCLE BIN -->
<!-- ========================================== -->
<?php elseif ($current_view === 'activity_log'): ?>
<div class="panel">
    <h2>Activity History & Recycle Bin</h2>
    <p style="color: var(--bark-700); margin-bottom: 15px;">Review past actions. You can <strong>Undo</strong> recent approvals, rejections, or flags as long as the AI has not been retrained yet.</p>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Details</th>
                <th>Undo</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($activities->num_rows === 0): ?>
                <tr><td colspan="5">No recent activity.</td></tr>
            <?php endif; ?>
            <?php while ($log = $activities->fetch_assoc()): ?>
                <tr <?= $log['can_undo'] ? 'style="background-color: #fdfdfd;"' : '' ?>>
                    <td style="white-space: nowrap;"><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></td>
                    <td><?= h($log['full_name'] ?? 'System') ?></td>
                    <td><strong><?= h($log['action']) ?></strong></td>
                    <td><?= h($log['details']) ?></td>
                    <td>
                        <?php if ($log['can_undo']): ?>
                            <form method="POST" style="margin:0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                                <button type="submit" name="undo_action" class="btn btn-sm btn-primary" onclick="return confirm('Undo this action?');">Undo</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #aaa; font-size: 0.85em;">Finalized</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ========================================== -->
<!-- MODALS (Rendered on all views) -->
<!-- ========================================== -->

<!-- MODAL: Folder Browser (Read Only) -->
<?php if ($browse_folder): ?>
<div id="modalBrowse" class="modal" style="display:block;">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close-btn" onclick="window.location.href='?view=dataset'">&times;</span>
        <h2>Viewing Folder: <span style="color: var(--moss-900);"><?= h($browse_folder) ?></span></h2>
        <p style="color: gray; font-size: 0.9em; margin-bottom: 15px;">Read-only view of images currently used for AI training in this category.</p>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap; max-height: 500px; overflow-y: auto; background: #f4f6f8; padding: 15px; border-radius: 8px;">
            <?php if(empty($browse_images)): ?>
                <p>No images found in this folder yet.</p>
            <?php else: ?>
                <?php foreach($browse_images as $img): ?>
                    <a href="<?= h($img) ?>" target="_blank">
                        <img src="<?= h($img) ?>" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ccc; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: Upload Diseased -->
<div id="modalDiseased" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('modalDiseased')">&times;</span>
        <h2>Upload Diseased Image</h2>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="upload_dataset_image" value="1">
            <input type="hidden" name="dataset_type" value="Diseased">
            <input type="hidden" name="current_view" value="<?= h($current_view) ?>">
            
            <div class="form-group">
                <label>Select Image:</label>
                <input type="file" name="dataset_file" accept="image/*" required class="form-control">
            </div>

            <div class="form-group">
                <label>Species:</label>
                <select name="species" id="diseasedSpecies" class="form-control" onchange="updateDiseaseDropdown()">
                    <option value="cat">Cat</option>
                    <option value="dog">Dog</option>
                </select>
            </div>

            <div class="form-group" style="background: #f9f9f9; padding: 10px; border-radius: 5px;">
                <label><input type="radio" name="folder_type" value="existing" checked onchange="toggleDiseaseMode(this.value)"> Existing Folder</label>
                <label style="margin-left:15px;"><input type="radio" name="folder_type" value="new" onchange="toggleDiseaseMode(this.value)"> Create New Folder</label>
                
                <div id="divExisting" style="margin-top:10px;">
                    <select name="existing_disease" id="diseaseSelect" class="form-control"></select>
                </div>
                
                <div id="divNew" style="margin-top:10px; display:none;">
                    <input type="text" name="new_disease_name" placeholder="Enter disease name (e.g., Dermatitis)" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Send to Verification</button>
        </form>
    </div>
</div>

<!-- MODAL: Upload Healthy -->
<div id="modalHealthy" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('modalHealthy')">&times;</span>
        <h2>Upload Healthy Image</h2>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="upload_dataset_image" value="1">
            <input type="hidden" name="dataset_type" value="Healthy">
            <input type="hidden" name="current_view" value="<?= h($current_view) ?>">
            
            <div class="form-group">
                <label>Select Image:</label>
                <input type="file" name="dataset_file" accept="image/*" required class="form-control">
            </div>

            <div class="form-group">
                <label>Species Folder:</label>
                <select name="species" class="form-control">
                    <option value="cat">Healthy / Cat</option>
                    <option value="dog">Healthy / Dog</option>
                </select>
            </div>

            <button type="submit" class="btn btn-outline btn-block">Send to Verification</button>
        </form>
    </div>
</div>

<!-- MODAL: Retrain Terminal -->
<div id="modalRetrain" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <h2>AI Training Status</h2>
        <div style="background: #1e1e1e; color: #4af626; padding: 15px; border-radius: 5px; height: 350px; overflow-y: auto; font-family: monospace; white-space: pre-wrap; font-size: 14px;" id="terminalOutput">Initializing connection to Python server...</div>
        <button class="btn btn-primary btn-block" style="margin-top:15px; display: none;" id="btnRetrainClose" onclick="location.reload()">Done! Close & Refresh</button>
    </div>
</div>

<script>
// --- UI TOGGLES ---
function showFlagForm(id) {
    document.getElementById('flag-btn-' + id).style.display = 'none';
    document.getElementById('flag-form-' + id).style.display = 'flex';
}
function hideFlagForm(id) {
    document.getElementById('flag-btn-' + id).style.display = 'block';
    document.getElementById('flag-form-' + id).style.display = 'none';
}

// --- MODALS & DROPDOWNS ---
const catDiseases = <?= json_encode(array_values($cat_diseases)) ?>;
const dogDiseases = <?= json_encode(array_values($dog_diseases)) ?>;

function updateDiseaseDropdown() {
    const species = document.getElementById('diseasedSpecies').value;
    const select = document.getElementById('diseaseSelect');
    select.innerHTML = '';
    
    const list = species === 'cat' ? catDiseases : dogDiseases;
    if (list.length === 0) {
        select.innerHTML = '<option value="">(No existing folders found)</option>';
    } else {
        list.forEach(d => { select.innerHTML += `<option value="${d}">${d}</option>`; });
    }
}

function toggleDiseaseMode(val) {
    document.getElementById('divExisting').style.display = val === 'existing' ? 'block' : 'none';
    document.getElementById('divNew').style.display = val === 'new' ? 'block' : 'none';
}

function openModal(id) { 
    document.getElementById(id).style.display = 'block'; 
    if(id === 'modalDiseased') updateDiseaseDropdown();
}
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// --- RETRAIN LOGIC ---
let logInterval;
const csrfToken = "<?= csrf_token() ?>";

async function retrainModel() {
    if(!confirm("Start retraining? This will lock the AI for a few minutes and permanently finalize all recent dataset changes.")) return;

    // Send a silent request to lock all "Undo" states in the database
    let formData = new FormData();
    formData.append('lock_undos', '1');
    formData.append('csrf_token', csrfToken);
    await fetch('image-review.php', { method: 'POST', body: formData });

    openModal('modalRetrain');
    const terminal = document.getElementById('terminalOutput');
    const closeBtn = document.getElementById('btnRetrainClose');
    
    closeBtn.style.display = 'none';
    terminal.innerHTML = "Connecting to Python Server...\n";

    logInterval = setInterval(async () => {
        try {
            let res = await fetch("http://127.0.0.1:5000/training-log");
            let json = await res.json();
            terminal.innerHTML = json.log;
            terminal.scrollTop = terminal.scrollHeight;
        } catch(e) {}
    }, 1000);

    try {
        const res = await fetch("http://127.0.0.1:5000/retrain-model", { method: "POST" });
        const json = await res.json();
        
        clearInterval(logInterval);
        
        try {
            let finalLog = await fetch("http://127.0.0.1:5000/training-log");
            let finalJson = await finalLog.json();
            terminal.innerHTML = finalJson.log;
            terminal.scrollTop = terminal.scrollHeight;
        } catch(e) {}

        closeBtn.style.display = 'block';
        closeBtn.innerHTML = json.error ? "Finished with Errors (Close)" : "Done! Close & Refresh";
        closeBtn.className = json.error ? "btn btn-reject btn-block" : "btn btn-primary btn-block";

    } catch (err) {
        clearInterval(logInterval);
        terminal.innerHTML += "\n\n[FATAL ERROR] Lost connection to Python server. Did the console close?";
        closeBtn.style.display = 'block';
        closeBtn.className = "btn btn-reject btn-block";
        closeBtn.innerHTML = "Close Window";}
}
</script>
<?php include __DIR__ . '/../design/admin_footer.php'; ?>