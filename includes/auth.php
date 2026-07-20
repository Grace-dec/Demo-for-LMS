<?php
// includes/auth.php  — Session + role helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Redirect to login page if not logged in */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php?msg=login_required');
        exit;
    }
}

/** Restrict a page to specific role(s) only */
function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . BASE_URL . 'login.php?msg=access_denied');
        exit;
    }
}

/** Check if anyone is currently logged in */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/** Get the logged-in user's ID */
function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

/** Get the logged-in user's role */
function current_role(): string {
    return $_SESSION['role'] ?? '';
}

/** Get the logged-in user's name */
function current_name(): string {
    return $_SESSION['name'] ?? '';
}

/** After login, send user to their correct dashboard based on role */
function role_dashboard(string $role): string {
    return match ($role) {
        'admin'      => BASE_URL . 'admin/dashboard.php',
        'instructor' => BASE_URL . 'instructor/dashboard.php',
        default      => BASE_URL . 'student/dashboard.php',
    };
}