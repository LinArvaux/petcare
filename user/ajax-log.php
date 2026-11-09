<?php
require_once __DIR__ . '/../db/app.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) exit;

$conn = get_db_connection();

if ($data['action'] === 'log_image' && isset($data['label'], $data['confidence'], $data['image_path'])) {
    $stmt = $conn->prepare("INSERT INTO image_classifications (predicted_label, confidence_score, image_path) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $data['label'], $data['confidence'], $data['image_path']);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'classification_id' => $conn->insert_id]);
    $stmt->close();
    exit;
}

if ($data['action'] === 'log_cost' && isset($data['disease'], $data['cost'])) {
    
    $cond_stmt = $conn->prepare("SELECT id FROM conditions WHERE REPLACE(LOWER(condition_name), '_', ' ') = REPLACE(LOWER(?), '_', ' ') LIMIT 1");
    $cond_stmt->bind_param("s", $data['disease']);
    $cond_stmt->execute();
    $res = $cond_stmt->get_result();
    $condition_id = ($row = $res->fetch_assoc()) ? $row['id'] : null;
    $cond_stmt->close();

    $class_id = !empty($data['classification_id']) ? $data['classification_id'] : null;
    $species  = $data['species'] ?? '';
    $severity = $data['severity_level'] ?? '';
    $weight   = $data['weight'] ?? 0;
    $duration = $data['duration'] ?? 0;
    $tier     = $data['tier'] ?? '';

    $stmt = $conn->prepare("INSERT INTO cost_estimates_log (classification_id, condition_id, disease_name, species, severity_level, pet_weight_kg, treatment_duration_days, clinic_tier, estimated_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssdisd", $class_id, $condition_id, $data['disease'], $species, $severity, $weight, $duration, $tier, $data['cost']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    $stmt->close();
    exit;
}