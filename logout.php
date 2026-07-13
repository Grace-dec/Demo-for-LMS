<?php
// logout.php
require_once __DIR__ . '/config.php';
session_destroy();
header('Location: ' . BASE_URL . 'index.php?msg=logged_out');
exit;