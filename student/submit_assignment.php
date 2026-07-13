<?php
// student/submit_assignment.php
require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('student');

$sid        = current_user_id();
$pre_select = (int)($_GET['assignment_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aid   = (int)$_POST['assignment_id'];
    $text  = trim($_POST['text_answer'] ?? '');
    $fpath = handle_upload('submission_file', 'uploads/submissions');

    if (!$aid) {
        set_flash('error', 'Please select an assignment.');
    } elseif (!$text && !$fpath) {
        set_flash('error', 'Please provide a text answer or upload a file.');
    } else {
        // Check enrollment
        $ok = $pdo->query(
            "SELECT 1 FROM assignments a JOIN enrollments e ON e.course_id=a.course_id
             WHERE a.id=$aid AND e.user_id=$sid"
        )->fetchColumn();

        if (!$ok) {
            set_flash('error', 'You are not enrolled in this course.');
        } else {
            try {
                $pdo->prepare(
                    'INSERT INTO submissions (assignment_id, student_id, text_answer, file_path)
                     VALUES (?,?,?,?)
                     ON DUPLICATE KEY UPDATE text_answer=VALUES(text_answer), file_path=VALUES(file_path), submitted_at=NOW()'
                )->execute([$aid, $sid, $text, $fpath]);
                set_flash('success', 'Assignment submitted successfully!');
            } catch (PDOException $e) {
                set_flash('error', 'Submission failed: ' . $e->getMessage());
            }
        }
    }
    header('Location: submit_assignment.php'); exit;
}

// Available assignments: published, enrolled, with due date info
$assignments = $pdo->query(
    "SELECT a.*, c.title AS course_title FROM assignments a
     JOIN courses c ON c.id=a.course_id
     JOIN enrollments e ON e.course_id=c.id
     WHERE e.user_id=$sid AND a.is_published=1
     ORDER BY a.due_date ASC"
)->fetchAll();

// Already-submitted map
$submitted_map = [];
foreach ($pdo->query("SELECT assignment_id FROM submissions WHERE student_id=$sid")->fetchAll() as $s) {
    $submitted_map[$s['assignment_id']] = true;
}

$nav = [
    ['label'=>'Dashboard',   'href'=>BASE_URL.'student/dashboard.php',         ],
    ['label'=>'My Courses',  'href'=>BASE_URL.'student/courses.php',            ],
    ['label'=>'Submit Work', 'href'=>BASE_URL.'student/submit_assignment.php',  ],
    ['label'=>'My Grades',   'href'=>BASE_URL.'student/my_grades.php',          ],
];

layout_head('Submit Assignment');
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Submit Assignment');
render_flash();
?>

<div class="card">
  <div class="card-header"><h3>Submit an Assignment</h3></div>
  <form method="post" enctype="multipart/form-data">
    <div class="form-group">
      <label>Select Assignment *</label>
      <select name="assignment_id" required id="asgn-select" onchange="showInfo(this.value)">
        <option value="">— choose —</option>
        <?php foreach ($assignments as $a): ?>
          <option value="<?= $a['id'] ?>"
                  data-due="<?= $a['due_date'] ? date('M d, Y H:i', strtotime($a['due_date'])) : 'No deadline' ?>"
                  data-inst="<?= e($a['instructions'] ?? 'No instructions provided.') ?>"
                  data-pts="<?= $a['points'] ?>"
                  data-submitted="<?= !empty($submitted_map[$a['id']]) ? '1':'0' ?>"
                  <?= $pre_select===$a['id']?'selected':'' ?>>
            <?= e($a['course_title']) ?> › <?= e($a['title']) ?>
            <?= !empty($submitted_map[$a['id']]) ? ' ✓' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div id="asgn-info" style="display:none;background:#f8fafc;border-radius:8px;padding:14px;margin-bottom:16px;font-size:.88rem;border:1px solid #e2e8f0">
      <div><strong>Due:</strong> <span id="info-due"></span></div>
      <div><strong>Points:</strong> <span id="info-pts"></span></div>
      <div style="margin-top:6px"><strong>Instructions:</strong></div>
      <div id="info-inst" style="color:#475569;margin-top:4px"></div>
      <div id="info-resubmit" style="margin-top:8px"></div>
    </div>

    <div class="form-group">
      <label>Text Answer</label>
      <textarea name="text_answer" rows="5" placeholder="Type your answer here (optional if uploading a file)..."></textarea>
    </div>

    <div class="form-group">
      <label for="submission_file">Upload File (PDF, DOCX, ZIP, image, etc.)</label>
      <input type="file" id="submission_file" name="submission_file">
      <small style="color:#94a3b8;font-size:.78rem">Allowed: pdf, doc, docx, txt, png, jpg, zip, pptx, xlsx</small>
    </div>

    <button class="btn btn-primary">Submit Assignment</button>
  </form>
</div>

<script>
function showInfo(val) {
    const sel   = document.getElementById('asgn-select');
    const opt   = sel.options[sel.selectedIndex];
    const panel = document.getElementById('asgn-info');
    if (!val) { panel.style.display='none'; return; }
    document.getElementById('info-due').textContent  = opt.dataset.due;
    document.getElementById('info-pts').textContent  = opt.dataset.pts + ' points';
    document.getElementById('info-inst').textContent = opt.dataset.inst;
    const resubDiv = document.getElementById('info-resubmit');
    if (opt.dataset.submitted === '1') {
        resubDiv.innerHTML = '<span style="color:#f59e0b;font-weight:600">⚠ You have already submitted this. Submitting again will overwrite.</span>';
    } else { resubDiv.innerHTML = ''; }
    panel.style.display = 'block';
}
document.addEventListener('DOMContentLoaded', () => {
    const s = document.getElementById('asgn-select');
    if (s.value) showInfo(s.value);
});
</script>

<?php layout_close(); ?>