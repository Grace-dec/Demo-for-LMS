<?php
// includes/functions.php — Shared utility functions

/** Escape output safely */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Flash message helpers (stored in session) */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/** Render the flash banner HTML */
function render_flash(): void {
    $f = get_flash();
    if (!$f) return;
    $cls = $f['type'] === 'success' ? 'alert-success' : 'alert-error';
    echo '<div class="alert ' . $cls . '">' . e($f['message']) . '</div>';
}

/**
 * Handle a file upload and return the relative path, or '' on failure.
 * $field  = $_FILES key
 * $dest   = destination folder relative to project root (no leading slash)
 */
function handle_upload(string $field, string $dest = 'uploads'): string {
    if (empty($_FILES[$field]['name'])) return '';

    $root    = dirname(__DIR__) . DIRECTORY_SEPARATOR;
    $destDir = $root . $dest . DIRECTORY_SEPARATOR;
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $ext      = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allowed  = ['pdf','doc','docx','txt','png','jpg','jpeg','webp','gif','zip','pptx','xlsx'];
    if (!in_array($ext, $allowed, true)) return '';

    $filename = uniqid('file_', true) . '.' . $ext;
    $target   = $destDir . $filename;

    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        return $dest . '/' . $filename;
    }
    return '';
}

/** Submission status label */
function submission_status(array $sub, string $dueDate): string {
    if (empty($sub)) return 'missing';
    if (!empty($sub['submitted_at']) && strtotime($sub['submitted_at']) > strtotime($dueDate)) {
        return 'late';
    }
    return 'submitted';
}