<?php
// student/course_detail.php
// Shows full course info, all modules, per-module completion checkbox,
// live progress bar, and related assignments for this course.

require_once __DIR__ . '/../config.php';
require_once ROOT_PATH . '/includes/layout.php';
require_role('student');

$sid = current_user_id();
$cid = (int)($_GET['id'] ?? 0);

if (!$cid) {
    set_flash('error', 'No course specified.');
    header('Location: courses.php'); exit;
}

// Verify enrollment
$enroll = $pdo->prepare(
    'SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?'
);
$enroll->execute([$sid, $cid]);
if (!$enroll->fetchColumn()) {
    set_flash('error', 'You are not enrolled in this course.');
    header('Location: courses.php'); exit;
}

// Course info
$course = $pdo->prepare(
    'SELECT c.*, u.name AS creator FROM courses c
     JOIN users u ON u.id=c.created_by
     WHERE c.id=? AND c.is_published=1'
);
$course->execute([$cid]);
$course = $course->fetch();

if (!$course) {
    set_flash('error', 'Course not found or not published.');
    header('Location: courses.php'); exit;
}

// Modules for this course
$modules = $pdo->query(
    "SELECT * FROM course_modules WHERE course_id=$cid ORDER BY sort_order, id"
)->fetchAll();

// Which modules has this student completed?
$done_ids = [];
if ($modules) {
    $mids = implode(',', array_column($modules, 'id'));
    $rows = $pdo->query(
        "SELECT module_id FROM module_progress WHERE user_id=$sid AND module_id IN ($mids)"
    )->fetchAll();
    foreach ($rows as $r) $done_ids[$r['module_id']] = true;
}

$total_modules   = count($modules);
$done_modules    = count($done_ids);
$progress_pct    = $total_modules > 0 ? (int)round($done_modules / $total_modules * 100) : 0;

// Published assignments for this course that the student can see
$assignments = $pdo->query(
    "SELECT a.*, 
            (SELECT score FROM submissions WHERE assignment_id=a.id AND student_id=$sid) AS my_score,
            (SELECT submitted_at FROM submissions WHERE assignment_id=a.id AND student_id=$sid) AS my_submitted_at,
            (SELECT feedback FROM submissions WHERE assignment_id=a.id AND student_id=$sid) AS my_feedback
     FROM assignments a
     WHERE a.course_id=$cid AND a.is_published=1
     ORDER BY a.due_date ASC"
)->fetchAll();

$nav = [
    ['label'=>'Dashboard',   'href'=>BASE_URL.'student/dashboard.php',        ],
    ['label'=>'My Courses',  'href'=>BASE_URL.'student/courses.php',           ],
    ['label'=>'Submit Work', 'href'=>BASE_URL.'student/submit_assignment.php', ],
    ['label'=>'My Grades',   'href'=>BASE_URL.'student/my_grades.php',         ],
];

layout_head(e($course['title']));
layout_sidebar($nav, current_role(), current_name());
layout_main_open('Course Detail');
render_flash();
?>

<!--COURSE HEADER -->
<div class="cd-hero">
  <div class="cd-hero-inner">
    <a href="<?= BASE_URL ?>student/courses.php" class="cd-back">← Back to Courses</a>
    <h1 class="cd-title"><?= e($course['title']) ?></h1>
    <p class="cd-meta">
      Created by <strong><?= e($course['creator']) ?></strong>
      &nbsp;·&nbsp;
      <?= $total_modules ?> module<?= $total_modules !== 1 ? 's' : '' ?>
      &nbsp;·&nbsp;
      <?= count($assignments) ?> assignment<?= count($assignments) !== 1 ? 's' : '' ?>
    </p>
    <?php if ($course['description']): ?>
      <p class="cd-desc"><?= nl2br(e($course['description'])) ?></p>
    <?php endif; ?>
  </div>
</div>

<!--  PROGRESS BAR -->
<div class="card cd-progress-card">
  <div class="cd-progress-header">
    <div>
      <span class="cd-progress-label">Your Progress</span>
      <span class="cd-progress-sub"><?= $done_modules ?> of <?= $total_modules ?> modules completed</span>
    </div>
    <span class="cd-progress-pct" id="pct-label"><?= $progress_pct ?>%</span>
  </div>
  <div class="cd-bar-track">
    <div class="cd-bar-fill" id="progress-bar" style="width:<?= $progress_pct ?>%"></div>
  </div>
  <?php if ($progress_pct === 100): ?>
    <div class="cd-complete-badge"> Course Complete!</div>
  <?php endif; ?>
</div>

<!--  TWO-COLUMN LAYOUT-->
<div class="cd-layout">

  <!-- LEFT: MODULES -->
  <div class="cd-col-main">
    <div class="card">
      <div class="card-header">
        <h3> Course Modules</h3>
        <span style="font-size:.8rem;color:#94a3b8"><?= $total_modules ?> total</span>
      </div>

      <?php if (!$modules): ?>
        <p style="color:#94a3b8;padding:20px;text-align:center">
          No modules have been added to this course yet.
        </p>
      <?php else: ?>

      <div class="cd-module-list" id="module-list">
        <?php foreach ($modules as $idx => $m):
          $is_done = !empty($done_ids[$m['id']]);
        ?>
        <div class="cd-module <?= $is_done ? 'cd-module--done' : '' ?>" id="module-<?= $m['id'] ?>">

          <!-- Module number + check button -->
          <button class="cd-check-btn <?= $is_done ? 'cd-check-btn--done' : '' ?>"
                  onclick="toggleModule(<?= $m['id'] ?>, <?= $cid ?>)"
                  title="<?= $is_done ? 'Mark incomplete' : 'Mark complete' ?>">
            <?= $is_done ? '✓' : ($idx + 1) ?>
          </button>

          <!-- Module content -->
          <div class="cd-module-body">
            <div class="cd-module-title"><?= e($m['title']) ?></div>

            <?php if ($m['content']): ?>
              <div class="cd-module-content"><?= nl2br(e($m['content'])) ?></div>
            <?php endif; ?>

            <div class="cd-module-footer">
              <?php if ($m['file_path']): ?>
                <a href="<?= BASE_URL . e($m['file_path']) ?>" target="_blank"
                   class="btn btn-outline btn-sm">
                  📎 Download File
                </a>
              <?php endif; ?>
              <span class="cd-status-pill <?= $is_done ? 'cd-pill--done' : 'cd-pill--todo' ?>"
                    id="pill-<?= $m['id'] ?>">
                <?= $is_done ? 'Completed' : 'Not started' ?>
              </span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php endif; ?>
    </div>
  </div><!-- /cd-col-main -->

  <!-- RIGHT: SIDEBAR INFO -->
  <div class="cd-col-side">

    <!-- Assignments -->
    <div class="card">
      <div class="card-header">
        <h3>Assignments</h3>
        <span style="font-size:.8rem;color:#94a3b8"><?= count($assignments) ?></span>
      </div>

      <?php if (!$assignments): ?>
        <p style="color:#94a3b8;font-size:.84rem;padding:8px 0">No assignments yet.</p>
      <?php else: ?>
        <div class="cd-assign-list">
          <?php foreach ($assignments as $a):
            $submitted = !empty($a['my_submitted_at']);
            $graded    = $a['my_score'] !== null;
            $overdue   = $a['due_date'] && strtotime($a['due_date']) < time() && !$submitted;
          ?>
          <div class="cd-assign-item">
            <div class="cd-assign-title"><?= e($a['title']) ?></div>
            <div class="cd-assign-meta">
              <?php if ($a['due_date']): ?>
                <span class="<?= $overdue ? 'cd-overdue' : 'cd-due' ?>">
                  Due <?= date('M d, Y', strtotime($a['due_date'])) ?>
                </span>
                &nbsp;·&nbsp;
              <?php endif; ?>
              <span><?= $a['points'] ?> pts</span>
            </div>
            <?php if ($graded): ?>
              <div class="cd-assign-grade">
                Score: <strong><?= $a['my_score'] ?>/<?= $a['points'] ?></strong>
                (<?= round($a['my_score']/$a['points']*100) ?>%)
                <?php if ($a['my_feedback']): ?>
                  <div style="font-size:.78rem;color:#475569;font-style:italic;margin-top:2px">
                    "<?= e($a['my_feedback']) ?>"
                  </div>
                <?php endif; ?>
              </div>
            <?php elseif ($submitted): ?>
              <span class="badge badge-submitted" style="font-size:.72rem">Submitted</span>
            <?php else: ?>
              <a href="<?= BASE_URL ?>student/submit_assignment.php?assignment_id=<?= $a['id'] ?>"
                 class="btn btn-primary btn-sm" style="margin-top:4px">Submit</a>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Progress Summary Card -->
    <div class="card cd-summary-card">
      <h3 style="font-size:.95rem;font-weight:600;color:#1a2744;margin-bottom:14px">Summary</h3>
      <div class="cd-summary-row">
        <span>Modules done</span>
        <strong id="sum-done"><?= $done_modules ?>/<?= $total_modules ?></strong>
      </div>
      <div class="cd-summary-row">
        <span>Assignments submitted</span>
        <strong><?= count(array_filter($assignments, fn($a) => !empty($a['my_submitted_at']))) ?>/<?= count($assignments) ?></strong>
      </div>
      <div class="cd-summary-row">
        <span>Graded</span>
        <strong><?= count(array_filter($assignments, fn($a) => $a['my_score'] !== null)) ?>/<?= count($assignments) ?></strong>
      </div>
      <div class="cd-summary-row">
        <span>Overall progress</span>
        <strong id="sum-pct"><?= $progress_pct ?>%</strong>
      </div>
    </div>

  </div><!-- /cd-col-side -->
</div><!-- /cd-layout -->


<!--PAGE-SPECIFIC STYLES-->
<style>
/* Hero */
.cd-hero {
  background: linear-gradient(135deg, #1a2744 0%, #2d4a8a 100%);
  border-radius: 12px;
  margin-bottom: 22px;
  padding: 36px 36px 30px;
  color: #fff;
}
.cd-back {
  display: inline-block;
  font-size: .8rem;
  color: rgba(255,255,255,.6);
  margin-bottom: 14px;
  transition: color .15s;
}
.cd-back:hover { color: #fbbf24; }
.cd-title { font-family: 'Playfair Display', serif; font-size: 1.9rem; line-height: 1.2; margin-bottom: 10px; }
.cd-meta  { font-size: .84rem; color: rgba(255,255,255,.65); margin-bottom: 10px; }
.cd-desc  { font-size: .9rem; color: rgba(255,255,255,.8); line-height: 1.6; margin-top: 10px; max-width: 680px; }

/* Progress card */
.cd-progress-card { padding: 20px 24px; margin-bottom: 22px; }
.cd-progress-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; }
.cd-progress-label { display: block; font-weight: 600; font-size: .9rem; color: #1a2744; }
.cd-progress-sub   { display: block; font-size: .78rem; color: #94a3b8; margin-top: 2px; }
.cd-progress-pct   { font-size: 1.7rem; font-weight: 700; color: #1a2744; line-height: 1; }
.cd-bar-track      { height: 12px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.cd-bar-fill       { height: 100%; background: linear-gradient(90deg, #1a2744, #f59e0b);
                     border-radius: 99px; transition: width .5s cubic-bezier(.4,0,.2,1); }
.cd-complete-badge { margin-top: 10px; font-size: .88rem; font-weight: 600; color: #15803d;
                     background: #dcfce7; border-radius: 6px; padding: 6px 12px; display: inline-block; }

/* Two-column layout */
.cd-layout      { display: grid; grid-template-columns: 1fr 320px; gap: 22px; align-items: start; }
@media (max-width: 900px) { .cd-layout { grid-template-columns: 1fr; } }

/* Module list */
.cd-module-list { display: flex; flex-direction: column; gap: 0; }
.cd-module {
  display: flex;
  gap: 14px;
  padding: 16px 0;
  border-bottom: 1px solid #f1f5f9;
  align-items: flex-start;
  transition: background .15s;
}
.cd-module:last-child { border-bottom: none; }
.cd-module--done { background: #f0fdf4; border-radius: 8px; padding: 16px 10px; margin: 0 -10px; }

/* Circular check button */
.cd-check-btn {
  flex-shrink: 0;
  width: 36px; height: 36px;
  border-radius: 50%;
  border: 2px solid #e2e8f0;
  background: #fff;
  font-size: .82rem;
  font-weight: 700;
  color: #94a3b8;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s;
  font-family: var(--font);
}
.cd-check-btn:hover { border-color: #f59e0b; color: #f59e0b; transform: scale(1.08); }
.cd-check-btn--done { background: #16a34a; border-color: #16a34a; color: #fff; }
.cd-check-btn--done:hover { background: #dc2626; border-color: #dc2626; }

.cd-module-body  { flex: 1; min-width: 0; }
.cd-module-title { font-weight: 600; font-size: .95rem; color: #1a2744; margin-bottom: 5px; }
.cd-module-content {
  font-size: .84rem; color: #475569; line-height: 1.55;
  margin-bottom: 8px; white-space: pre-wrap; word-break: break-word;
}
.cd-module-footer { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

/* Status pills */
.cd-status-pill { font-size: .72rem; font-weight: 600; padding: 3px 10px; border-radius: 99px; }
.cd-pill--done  { background: #dcfce7; color: #15803d; }
.cd-pill--todo  { background: #f1f5f9; color: #94a3b8; }

/* Assignments sidebar */
.cd-assign-list { display: flex; flex-direction: column; gap: 12px; }
.cd-assign-item { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
.cd-assign-item:last-child { border-bottom: none; }
.cd-assign-title { font-weight: 600; font-size: .88rem; color: #1a2744; margin-bottom: 3px; }
.cd-assign-meta  { font-size: .76rem; color: #94a3b8; margin-bottom: 4px; }
.cd-assign-grade { font-size: .82rem; color: #1a2744; margin-top: 4px; }
.cd-due    { color: #94a3b8; }
.cd-overdue { color: #dc2626; font-weight: 600; }

/* Summary card */
.cd-summary-card { padding: 18px 20px; }
.cd-summary-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 7px 0; border-bottom: 1px solid #f1f5f9; font-size: .85rem; color: #475569;
}
.cd-summary-row:last-child { border-bottom: none; }
.cd-summary-row strong { color: #1a2744; }

/* Spinner for async toggle */
@keyframes spin { to { transform: rotate(360deg); } }
.cd-spinning { animation: spin .5s linear infinite; pointer-events: none; opacity: .5; }
</style>

<!--JAVASCRIPT — async progress toggle -->
<script>
async function toggleModule(moduleId, courseId) {
  const btn      = document.querySelector(`#module-${moduleId} .cd-check-btn`);
  const moduleEl = document.getElementById(`module-${moduleId}`);
  const pill     = document.getElementById(`pill-${moduleId}`);

  // Spinner feedback
  btn.classList.add('cd-spinning');

  try {
    const fd = new FormData();
    fd.append('module_id', moduleId);
    fd.append('course_id', courseId);

    const res  = await fetch('mark_progress.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (!data.success) {
      alert('Could not save progress. Please try again.');
      return;
    }

    // Update button appearance
    if (data.completed) {
      btn.classList.add('cd-check-btn--done');
      btn.textContent = '✓';
      btn.title = 'Mark incomplete';
      moduleEl.classList.add('cd-module--done');
      pill.textContent = 'Completed';
      pill.className = 'cd-status-pill cd-pill--done';
    } else {
      btn.classList.remove('cd-check-btn--done');
      // Restore module number (position in list)
      const allModules = [...document.querySelectorAll('#module-list .cd-module')];
      btn.textContent = allModules.indexOf(moduleEl) + 1;
      btn.title = 'Mark complete';
      moduleEl.classList.remove('cd-module--done');
      pill.textContent = 'Not started';
      pill.className = 'cd-status-pill cd-pill--todo';
    }

    // Animate progress bar
    const bar      = document.getElementById('progress-bar');
    const pctLabel = document.getElementById('pct-label');
    const sumDone  = document.getElementById('sum-done');
    const sumPct   = document.getElementById('sum-pct');
    const total    = data.total;

    bar.style.width = data.percent + '%';
    pctLabel.textContent = data.percent + '%';
    sumDone.textContent  = data.done + '/' + total;
    sumPct.textContent   = data.percent + '%';

    // Show/hide completion badge
    const existing = document.querySelector('.cd-complete-badge');
    if (data.percent === 100 && !existing) {
      const badge = document.createElement('div');
      badge.className = 'cd-complete-badge';
      badge.textContent = ' Course Complete!';
      document.querySelector('.cd-bar-track').after(badge);
    } else if (data.percent < 100 && existing) {
      existing.remove();
    }

  } catch (err) {
    console.error(err);
    alert('Network error. Please check your connection.');
  } finally {
    btn.classList.remove('cd-spinning');
  }
}
</script>

<?php layout_close(); ?>
