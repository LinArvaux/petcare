<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
define('APP_NAME', 'PetCare AI');
define('BASE_URL', '/');

// Updated to point to the static/uploads/ directory
define('UPLOAD_PET_IMAGES', __DIR__ . '/../static/uploads/pet_images/');
define('UPLOAD_KNOWLEDGE_SOURCES', __DIR__ . '/../static/uploads/knowledge_sources/');

// Updated to look inside the current 'db/' directory
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';