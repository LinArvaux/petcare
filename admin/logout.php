<?php
require_once __DIR__ . '/../db/app.php';
require_once __DIR__ . '/../db/admin_auth.php';

admin_logout();
session_start();
flash_set('success', 'You have been logged out.');
redirect('login.php');