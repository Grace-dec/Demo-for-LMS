<?php
// config.php  — Loaded first by every page

// Adjust BASE_URL if you rename the folder or use a sub-folder
define('BASE_URL', '/test-project/');

// Absolute path to project root (no trailing slash)
define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/functions.php';