<?php
// index.php — Smart entry point
// Guests       → home.php  (public landing page)
// Logged-in    → their role dashboard
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header('Location: ' . role_dashboard(current_role()));
} else {
    header('Location: ' . BASE_URL . 'home.php');
}
exit;

