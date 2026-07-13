<?php
// instructor/assignments.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('instructor');

$iid       = current_user_id();
$filter_cid = (int)($_GET['course_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $cid    = (int)$_POST['course_id'];
        $title  = trim($_POST['title'] ?? '');
        $inst   = trim($_POST['instructions'] ?? '');
        $due    = $_POST['due_date'] ?? '';
        $pts    = (int)($_POST['points'] ?? 100);

        if ($cid && $title) {
            $pdo->prepare(
                'INSERT INTO assignments (course_id, instructor_id, title, instructions, due_date, points)
                 VALUES (?,?,?,?,?,?)'
            )->execute([$cid, $iid, $title, $inst, $due ?: null, $pts]);
            set_flash('success', 'Assignment created.');
        }
    }

    if ($action === 'toggle_publish') {
        $aid = (int)$_POST['assignment_id'];
        $cur = (int)$pdo->query("SELECT is_published FROM assignments WHERE id=$aid AND instructor_id=$iid")->fetchColumn();
        $pdo->prepare('UPDATE assignments SET is_published=? WHERE id=? AND instructor_id=?')
            ->execute([!$cur, $aid, $iid]);
        set_flash('success', 'Publish status updated.');
    }

    if ($action === 'delete') {
        $aid = (int)$_POST['assignment_id'];
        $pdo->prepare('DELETE FROM assignments WHERE id=? AND instructor_id=?')->execute([$aid, $iid]);
        set_flash('success', 'Assignment deleted.');
    }

    header('Location: assignments.php' . ($filter_cid ? "?course_id=$filter_cid" : ''));
    exit;
}

// Courses this instructor is enrolled in
$courses = $pdo->query(
    "SELECT c.* FROM courses c JOIN enrollments e ON e.course_id=c.id
     WHERE e.user_id=$iid AND c.is_published=1 ORDER BY c.title"
)->fetchAll();

// Assignments
$sql = 'SELECT a.*, c.title AS course_title FROM assignments a JOIN courses c ON c.id=a.course_id
        WHERE a.instructor_id=' . $iid;
if ($filter_cid) $sql .= ' AND a.course_id=' . $filter_cid;
$sql .= ' ORDER BY a.created_at DESC';
$assignments = $pdo->query($sql)->fetchAll();

$nav = [
    ['label'=>'Dashboard',   'href'=>BASE_URL.'instructor/dashboard.php',  ],
    ['label'=>'Assignments', 'href'=>BASE_URL.'instructor/assignments.php', ],
    ['label'=>'Submissions', 'href'=>BASE_URL.'instructor/submissions.php', ],
    ['label'=>'Gradebook',   'href'=>BASE_URL.'instructor/gradebook.php',   ],
    ['label'=>'My Course',     'href'=>BASE_URL.'instructor/course.php',            ],
    ['label'=>'Course Progress', 'href'=>BASE_URL.'instructor/course_progress.php', ],
];

layout_head('Assignments');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Assignments');
render_flash();
?>

<div class="card">
  <div class="card-header"><h3>Create Assignment</h3></div>
  <form method="post">
    <input type="hidden" name="action" value="create">
    <div style="display:grid;gap:12px;grid-template-columns:1fr 1fr">
      <div class="form-group" style="margin:0">
        <label>Course *</label>
        <select name="course_id" required>
          <option value="">— select —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $filter_cid===$c['id']?'selected':'' ?>><?= e($c['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label>Title *</label>
        <input type="text" name="title" required placeholder="Assignment title">
      </div>
      <div class="form-group" style="margin:0">
        <label>Due Date</label>
        <input type="datetime-local" name="due_date">
      </div>
      <div class="form-group" style="margin:0">
        <label>Points</label>
        <input type="number" name="points" value="100" min="0" max="999">
      </div>
    </div>
    <div class="form-group" style="margin-top:12px">
      <label>Instructions</label>
      <textarea name="instructions" rows="3" placeholder="What should students do?"></textarea>
    </div>
    <button class="btn btn-primary">Create Assignment</button>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h3>My Assignments <?= $filter_cid ? '(filtered)' : '' ?> (<?= count($assignments) ?>)</h3>
    <?php if ($filter_cid): ?><a href="assignments.php" class="btn btn-outline btn-sm">Show All</a><?php endif; ?>
  </div>
  <?php if (!$assignments): ?>
    <p style="color:#94a3b8;padding:20px;text-align:center">No assignments yet.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Title</th><th>Course</th><th>Due</th><th>Points</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($assignments as $a): ?>
        <tr>
          <td><strong><?= e($a['title']) ?></strong></td>
          <td><?= e($a['course_title']) ?></td>
          <td>
            <?php if ($a['due_date']): ?>
              <span class="due-info <?= strtotime($a['due_date']) < time() ? 'overdue' : '' ?>">
                <?= date('M d, Y H:i', strtotime($a['due_date'])) ?>
              </span>
            <?php else: ?><span style="color:#94a3b8">No deadline</span><?php endif; ?>
          </td>
          <td><?= $a['points'] ?></td>
          <td><span class="badge badge-<?= $a['is_published'] ? 'published':'draft' ?>">
            <?= $a['is_published'] ? 'Published':'Draft' ?>
          </span></td>
          <td style="display:flex;gap:6px">
            <form method="post" style="display:inline">
              <input type="hidden" name="action"        value="toggle_publish">
              <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
              <button class="btn btn-amber btn-sm"><?= $a['is_published'] ? 'Unpublish':'Publish' ?></button>
            </form>
            <a href="<?= BASE_URL ?>instructor/submissions.php?assignment_id=<?= $a['id'] ?>"
               class="btn btn-outline btn-sm">Submissions</a>
            <form method="post" style="display:inline">
              <input type="hidden" name="action"        value="delete">
              <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
              <button class="btn btn-danger btn-sm" data-confirm="Delete this assignment?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php layout_close(); ?>