<?php
require_once __DIR__ . '/config/app.php';

$conn = get_db_connection();
$email = 'admin@petcare.local';
$new_password = 'Admin@12345';

// This generates a real, valid bcrypt hash for the password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    echo "<h1>Password successfully updated!</h1> <p>You can now log in.</p>";
} else {
    echo "Error updating password.";
}
$stmt->close();
?>