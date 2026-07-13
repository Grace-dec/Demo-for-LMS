<?php
// student/mark_progress.php
// Called via fetch() from course_detail.php to toggle a module's completion.
// Returns JSON: { success: bool, completed: bool, percent: int }

require_once __DIR__ . '/../config.php';
require_role('student');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$sid       = current_user_id();
$module_id = (int)($_POST['module_id'] ?? 0);
$course_id = (int)($_POST['course_id'] ?? 0);

if (!$module_id || !$course_id) {
    echo json_encode(['success' => false, 'error' => 'Missing IDs']);
    exit;
}

// Verify the student is actually enrolled in this course
$enrolled = $pdo->prepare(
    'SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?'
);
$enrolled->execute([$sid, $course_id]);
if (!$enrolled->fetchColumn()) {
    echo json_encode(['success' => false, 'error' => 'Not enrolled']);
    exit;
}

// Check current state
$existing = $pdo->prepare(
    'SELECT id FROM module_progress WHERE user_id=? AND module_id=?'
);
$existing->execute([$sid, $module_id]);
$row = $existing->fetch();

if ($row) {
    // Already marked — toggle off (un-complete)
    $pdo->prepare('DELETE FROM module_progress WHERE user_id=? AND module_id=?')
        ->execute([$sid, $module_id]);
    $completed = false;
} else {
    // Mark as complete
    $pdo->prepare(
        'INSERT INTO module_progress (user_id, module_id, course_id) VALUES (?,?,?)'
    )->execute([$sid, $module_id, $course_id]);
    $completed = true;
}

// Recalculate overall course progress
$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM course_modules WHERE course_id=?');
$totalStmt->execute([$course_id]);
$total = (int)$totalStmt->fetchColumn();

$done  = (int)$pdo->query(
    "SELECT COUNT(*) FROM module_progress
     WHERE user_id=$sid AND course_id=$course_id"
)->fetchColumn();

$percent = $total > 0 ? (int)round($done / $total * 100) : 0;

echo json_encode([
    'success'   => true,
    'completed' => $completed,
    'done'      => $done,
    'total'     => $total,
    'percent'   => $percent,
]);
