<?php
// instructor/submissions.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('instructor');

$iid    = current_user_id();
$filter = (int)($_GET['assignment_id'] ?? 0);

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'grade') {
    $sid      = (int)$_POST['submission_id'];
    $score    = (int)$_POST['score'];
    $feedback = trim($_POST['feedback'] ?? '');
    $pdo->prepare(
        'UPDATE submissions SET score=?, feedback=?, graded_at=NOW()
         WHERE id=? AND assignment_id IN (SELECT id FROM assignments WHERE instructor_id=?)'
    )->execute([$score, $feedback, $sid, $iid]);
    set_flash('success', 'Grade saved.');
    header('Location: submissions.php' . ($filter ? "?assignment_id=$filter" : ''));
    exit;
}

// All assignments by this instructor for filter dropdown
$myAssignments = $pdo->query(
    "SELECT a.id, a.title, c.title AS course_title, a.due_date, a.points
     FROM assignments a JOIN courses c ON c.id=a.course_id
     WHERE a.instructor_id=$iid ORDER BY a.created_at DESC"
)->fetchAll();

// Build submissions query
$sql = "SELECT s.*, u.name AS student_name, a.title AS assign_title,
               a.due_date, a.points, c.title AS course_title
        FROM submissions s
        JOIN users u ON u.id=s.student_id
        JOIN assignments a ON a.id=s.assignment_id
        JOIN courses c ON c.id=a.course_id
        WHERE a.instructor_id=$iid";
if ($filter) $sql .= " AND s.assignment_id=$filter";
$sql .= " ORDER BY s.submitted_at DESC";
$submissions = $pdo->query($sql)->fetchAll();

$nav = [
    ['label'=>'Dashboard',   'href'=>BASE_URL.'instructor/dashboard.php',  ],
    ['label'=>'Assignments', 'href'=>BASE_URL.'instructor/assignments.php', ],
    ['label'=>'Submissions', 'href'=>BASE_URL.'instructor/submissions.php', ],
    ['label'=>'Gradebook',   'href'=>BASE_URL.'instructor/gradebook.php',   ],
    ['label'=>'My Course',     'href'=>BASE_URL.'instructor/course.php',            ],
    ['label'=>'Course Progress', 'href'=>BASE_URL.'instructor/course_progress.php', ],
];

layout_head('Submissions');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Submissions & Grading');
render_flash();
?>

<div class="card" style="padding:16px 24px">
  <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <div class="form-group" style="margin:0;flex:1;min-width:220px">
      <label>Filter by Assignment</label>
      <select name="assignment_id" onchange="this.form.submit()">
        <option value="">— All Assignments —</option>
        <?php foreach ($myAssignments as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $filter===$a['id']?'selected':'' ?>>
            <?= e($a['course_title']) ?> › <?= e($a['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($filter): ?>
      <a href="submissions.php" class="btn btn-outline btn-sm" style="margin-bottom:0">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h3>Submissions (<?= count($submissions) ?>)</h3>
    <span style="font-size:.8rem;color:#94a3b8">
      <?= count(array_filter($submissions, fn($s) => $s['score'] !== null)) ?> graded
    </span>
  </div>
  <?php if (!$submissions): ?>
    <p style="color:#94a3b8;padding:20px;text-align:center">No submissions found.</p>
  <?php else: ?>
  <?php foreach ($submissions as $sub):
    $status = submission_status($sub, $sub['due_date'] ?? '9999-01-01');
  ?>
  <div style="border:1px solid #e2e8f0;border-radius:10px;padding:18px;margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
      <div>
        <strong><?= e($sub['student_name']) ?></strong>
        <span style="color:#94a3b8;font-size:.82rem;margin-left:8px"><?= e($sub['assign_title']) ?> · <?= e($sub['course_title']) ?></span>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <span class="badge badge-<?= $status ?>"><?= ucfirst($status) ?></span>
        <span style="font-size:.78rem;color:#94a3b8"><?= date('M d, Y H:i', strtotime($sub['submitted_at'])) ?></span>
      </div>
    </div>

    <?php if ($sub['text_answer']): ?>
      <div style="background:#f8fafc;border-radius:8px;padding:12px;font-size:.88rem;margin-bottom:10px;color:#475569">
        <?= nl2br(e($sub['text_answer'])) ?>
      </div>
    <?php endif; ?>
    <?php if ($sub['file_path']): ?>
      <a href="<?= BASE_URL . e($sub['file_path']) ?>" class="btn btn-outline btn-sm" target="_blank" style="margin-bottom:10px">
        📎 Download File
      </a>
    <?php endif; ?>

    <!-- Grading form -->
    <form method="post" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;border-top:1px solid #f1f5f9;padding-top:12px;margin-top:4px">
      <input type="hidden" name="action"        value="grade">
      <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
      <div class="form-group" style="margin:0">
        <label>Score (/ <?= $sub['points'] ?>) <span id="score_pct" style="color:#94a3b8"></span></label>
        <input type="number" id="score" name="score" min="0" max="<?= $sub['points'] ?>"
               value="<?= $sub['score'] ?? '' ?>" style="width:100px">
        <input type="hidden" id="points_max" value="<?= $sub['points'] ?>">
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:200px">
        <label>Feedback</label>
        <input type="text" name="feedback" value="<?= e($sub['feedback'] ?? '') ?>" placeholder="Optional comment">
      </div>
      <button class="btn btn-success btn-sm" style="margin-bottom:0">
        <?= $sub['score'] !== null ? 'Update Grade' : 'Save Grade' ?>
      </button>
    </form>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php layout_close(); ?>