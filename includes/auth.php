<?php
// includes/auth.php  — Session + role helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Redirect to login page (not index) with a message */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php?msg=login_required');
        exit;
    }
}

/** Restrict page to specific role(s) */
function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . BASE_URL . 'login.php?msg=access_denied');
        exit;
    }
}

function is_logged_in(): bool   { return !empty($_SESSION['user_id']); }
function current_user_id(): int  { return (int)($_SESSION['user_id'] ?? 0); }
function current_role(): string  { return $_SESSION['role'] ?? ''; }
function current_name(): string  { return $_SESSION['name'] ?? ''; }

/** After login, redirect to the correct role dashboard */
function role_dashboard(string $role): string {
    return match ($role) {
        'admin'      => BASE_URL . 'admin/dashboard.php',
        'instructor' => BASE_URL . 'instructor/dashboard.php',
        default      => BASE_URL . 'student/dashboard.php',
    };
}