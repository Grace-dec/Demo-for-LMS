<?php
// home.php — Public main dashboard
// Visible to EVERYONE (logged in or not).
// When a guest tries to use a feature, they are redirected to login.php.

require_once __DIR__ . '/config.php';

// Fetch published courses (always visible to everyone)
$courses = $pdo->query(
    "SELECT c.*, u.name AS creator,
            (SELECT COUNT(*) FROM course_modules  WHERE course_id=c.id) AS module_count,
            (SELECT COUNT(*) FROM enrollments     WHERE course_id=c.id) AS enrolled_count,
            (SELECT COUNT(*) FROM assignments     WHERE course_id=c.id AND is_published=1) AS assignment_count
     FROM courses c
     JOIN users u ON u.id = c.created_by
     WHERE c.is_published = 1
     ORDER BY c.created_at DESC"
)->fetchAll();

// Platform stats for the hero section
$stat_courses  = $pdo->query('SELECT COUNT(*) FROM courses   WHERE is_published=1')->fetchColumn();
$stat_students = $pdo->query("SELECT COUNT(*) FROM users     WHERE role='student'")->fetchColumn();
$stat_instructors = $pdo->query("SELECT COUNT(*) FROM users  WHERE role='instructor'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lightlearn — Online Learning Platform</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/home.css">
</head>
<body class="home-body">

<!-- ═══════════════════════════════════════════════════════
     TOP NAVIGATION BAR
═══════════════════════════════════════════════════════ -->
<header class="hd-navbar">
  <div class="hd-navbar-inner">

   <!-- Brand -->
<a href="home.php" class="hd-brand">
  <img src="assets\image\logo2.png.PNG" 
       alt="LightLearn Logo" 
       style="height:40px; width:auto; object-fit:contain;">Lightlearn
</a>

    <!-- Center nav links -->
    <nav class="hd-nav-links">
      <a href="home.php" class="hd-nav-link active">Home</a>
      <a href="#courses-section" class="hd-nav-link">Courses</a>
      <a href="#about-section"   class="hd-nav-link">About</a>
      <a href="#contact-section" class="hd-nav-link">Contact</a>
    </nav>

    <!-- Right: auth buttons or user menu -->
    <div class="hd-nav-auth">
      <?php if (is_logged_in()): ?>
        <a href="<?= role_dashboard(current_role()) ?>" class="btn btn-amber btn-sm">
          Go to Dashboard →
        </a>
        <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
      <?php else: ?>
        <a href="login.php"    class="btn btn-outline btn-sm">Sign In</a>
        <a href="register.php" class="btn btn-primary btn-sm">Register Free</a>
      <?php endif; ?>
    </div>

    <!-- Mobile hamburger -->
    <button class="hd-hamburger" id="hd-hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>

  <!-- Mobile menu -->
  <div class="hd-mobile-menu" id="hd-mobile-menu">
    <a href="home.php">Home</a>
    <a href="#courses-section">Courses</a>
    <a href="#about-section">About</a>
    <a href="#contact-section">Contact</a>
    <hr style="border-color:rgba(255,255,255,.15);margin:10px 0">
    <?php if (is_logged_in()): ?>
      <a href="<?= role_dashboard(current_role()) ?>">My Dashboard</a>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Sign In</a>
      <a href="register.php">Create Account</a>
    <?php endif; ?>
  </div>
</header>


<!-- ═══════════════════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════════════════ -->
<section class="hd-hero">
  <div class="hd-hero-bg"></div>
  <div class="hd-hero-content">
    <h1 class="hd-hero-title">
      Learn Without<br>
      <span class="hd-hero-highlight">Limits</span>
    </h1>
    <p class="hd-hero-sub">
      Discover world-class courses taught by expert instructors.
      Build real skills, earn certificates, and advance your career — at your own pace.
    </p>
    <div class="hd-hero-actions">
      <?php if (!is_logged_in()): ?>
        <a href="register.php" class="btn hd-btn-hero-primary">Start Learning Free</a>
        <a href="#courses-section" class="btn hd-btn-hero-outline">Browse Courses ↓</a>
      <?php else: ?>
        <a href="<?= role_dashboard(current_role()) ?>" class="btn hd-btn-hero-primary">Go to My Dashboard</a>
        <a href="#courses-section" class="btn hd-btn-hero-outline">Browse Courses ↓</a>
      <?php endif; ?>
    </div>

    

  <!-- Hero decorative shapes -->
  <div class="hd-hero-shapes">
    <div class="hd-shape hd-shape-1"></div>
    <div class="hd-shape hd-shape-2"></div>
    <div class="hd-shape hd-shape-3"></div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     COURSES SECTION
═══════════════════════════════════════════════════════ -->
<section class="hd-section" id="courses-section">
  <div class="hd-container">

    <div class="hd-section-header">
      <div>
        <p class="hd-section-label">What We Offer</p>
        <h2 class="hd-section-title">Published Courses</h2>
        <p class="hd-section-sub">Browse our catalogue. Sign in to enroll and access full content.</p>
      </div>
      <?php if (!is_logged_in()): ?>
        <a href="register.php" class="btn btn-primary">Join to Enroll →</a>
      <?php else: ?>
        <a href="student/courses.php" class="btn btn-primary">My Courses →</a>
      <?php endif; ?>
    </div>

    <?php if (!$courses): ?>
      <div class="hd-empty-state">
        <div class="hd-empty-icon">📚</div>
        <h3>No Courses Published Yet</h3>
        <p>Check back soon — new courses are on the way!</p>
      </div>

    <?php else: ?>
    <div class="hd-courses-grid">
      <?php foreach ($courses as $c):
        // Pick a gradient colour based on course id for visual variety
        $gradients = [
          'linear-gradient(135deg,#1a2744,#2d4a8a)',
          'linear-gradient(135deg,#7c3aed,#4f46e5)',
          'linear-gradient(135deg,#0f766e,#0891b2)',
          'linear-gradient(135deg,#b45309,#d97706)',
          'linear-gradient(135deg,#be123c,#e11d48)',
          'linear-gradient(135deg,#15803d,#16a34a)',
        ];
        $grad = $gradients[$c['id'] % count($gradients)];
      ?>
      <div class="hd-course-card">

        <!-- Course image area -->
        <div class="hd-course-img" style="background:<?= $grad ?>">
          <?php if (!empty($c['image_path']) && file_exists(ROOT_PATH . '/' . $c['image_path'])): ?>
            <img src="<?= BASE_URL . e($c['image_path']) ?>" alt="<?= e($c['title']) ?>">
          <?php else: ?>
            <!-- Placeholder shown until admin uploads an image -->
            <div class="hd-course-img-placeholder">
              <span class="hd-placeholder-icon">📘</span>
              <span class="hd-placeholder-text">Course Image</span>
            </div>
          <?php endif; ?>
          <div class="hd-course-badge"><?= $c['module_count'] ?> module<?= $c['module_count']!=1?'s':'' ?></div>
        </div>

        <!-- Card body -->
        <div class="hd-course-body">
          <h3 class="hd-course-title"><?= e($c['title']) ?></h3>

          <p class="hd-course-desc">
            <?= e($c['description'] ? substr($c['description'], 0, 110) . (strlen($c['description']) > 110 ? '…' : '') : 'No description provided.') ?>
          </p>

          <div class="hd-course-meta">
            <span class="hd-meta-item">👤 <?= e($c['creator']) ?></span>
            <span class="hd-meta-item">👥 <?= $c['enrolled_count'] ?> enrolled</span>
            <span class="hd-meta-item">📝 <?= $c['assignment_count'] ?> assignment<?= $c['assignment_count']!=1?'s':'' ?></span>
          </div>
        </div>

        <!-- Card footer / CTA -->
        <div class="hd-course-footer">
          <?php if (is_logged_in()): ?>
            <?php if (current_role() === 'student'): ?>
              <!-- Check if already enrolled -->
              <?php
                $enrolCheck = $pdo->prepare('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?');
                $enrolCheck->execute([current_user_id(), $c['id']]);
                $already = $enrolCheck->fetchColumn();
              ?>
              <?php if ($already): ?>
                <a href="student/course_detail.php?id=<?= $c['id'] ?>"
                   class="btn btn-success hd-btn-full">Continue Learning →</a>
              <?php else: ?>
                <a href="student/courses.php" class="btn btn-primary hd-btn-full">Enroll Now →</a>
              <?php endif; ?>
            <?php else: ?>
              <!-- Admin / Instructor -->
              <a href="<?= role_dashboard(current_role()) ?>" class="btn btn-amber hd-btn-full">Go to Dashboard</a>
            <?php endif; ?>
          <?php else: ?>
            <!-- Guest — prompt to sign in -->
            <a href="login.php" class="btn btn-primary hd-btn-full">
              Sign In to Enroll →
            </a>
          <?php endif; ?>

          <div class="hd-course-date">
            Added <?= date('M Y', strtotime($c['created_at'])) ?>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════════════════════ -->
<section class="hd-section hd-section-alt" id="about-section">
  <div class="hd-container">
    <div class="hd-section-header" style="margin-bottom:40px">
      <div>
        <p class="hd-section-label">Simple Process</p>
        <h2 class="hd-section-title">How Lightlearn Works</h2>
      </div>
    </div>
    <div class="hd-steps-grid">
      <div class="hd-step">
        <div class="hd-step-num">1</div>
        <h3 class="hd-step-title">Create Your Account</h3>
        <p class="hd-step-desc">Register for free as a student or instructor. Admins set up the platform and publish courses.</p>
      </div>
      <div class="hd-step-arrow">→</div>
      <div class="hd-step">
        <div class="hd-step-num">2</div>
        <h3 class="hd-step-title">Enroll in Courses</h3>
        <p class="hd-step-desc">Browse the catalogue, pick your course, and enroll in one click. Start learning immediately.</p>
      </div>
      <div class="hd-step-arrow">→</div>
      <div class="hd-step">
        <div class="hd-step-num">3</div>
        <h3 class="hd-step-title">Learn & Submit</h3>
        <p class="hd-step-desc">Work through modules at your own pace, submit assignments, and receive personalised feedback.</p>
      </div>
      <div class="hd-step-arrow">→</div>
      <div class="hd-step">
        <div class="hd-step-num">4</div>
        <h3 class="hd-step-title">Track Progress</h3>
        <p class="hd-step-desc">Watch your progress bar grow, check your gradebook, and celebrate completing each course.</p>
      </div>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════
     CTA BANNER
═══════════════════════════════════════════════════════ -->
<?php if (!is_logged_in()): ?>
<section class="hd-cta-banner">
  <div class="hd-container hd-cta-inner">
    <div>
      <h2 class="hd-cta-title">Ready to Start Learning?</h2>
      <p class="hd-cta-sub">Join hundreds of students already growing their skills on Lightlearn.</p>
    </div>
    <div class="hd-cta-btns">
      <a href="register.php" class="btn hd-btn-cta-primary">Create Free Account</a>
      <a href="login.php"    class="btn hd-btn-cta-outline">Sign In</a>
    </div>
  </div>
</section>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════ -->
<footer class="hd-footer" id="contact-section">
  <div class="hd-container">
    <div class="hd-footer-grid">

      <div class="hd-footer-brand">
        <div class="hd-footer-logo"> Lightlearn</div>
        <p class="hd-footer-tagline">
          Empowering learners and educators with a simple, powerful learning management system.
        </p>
      </div>

      <div class="hd-footer-col">
        <div class="hd-footer-heading">Platform</div>
        <a href="home.php">Home</a>
        <a href="#courses-section">Courses</a>
        <a href="register.php">Register</a>
        <a href="login.php">Sign In</a>
      </div>

      <div class="hd-footer-col">
        <div class="hd-footer-heading">Roles</div>
        <a href="register.php?role=student">Students</a>
        <a href="register.php?role=instructor">Instructors</a>
        <a href="login.php">Administrators</a>
      </div>

      <div class="hd-footer-col">
        <div class="hd-footer-heading">Contact</div>
        <span> admin@lms.com</span>
        <span>Lightlearn Academy</span>
        <span>+237 681 392 805</span>
      </div>

    </div>
    <div class="hd-footer-bottom">
      <span>© <?= date('Y') ?> Lightlearn LMS. </span>
      <span>
        <?php if (!is_logged_in()): ?>
          <a href="login.php">Sign In</a> &nbsp;·&nbsp; <a href="register.php">Register</a>
        <?php else: ?>
          <a href="<?= role_dashboard(current_role()) ?>">Dashboard</a> &nbsp;·&nbsp; <a href="logout.php">Logout</a>
        <?php endif; ?>
      </span>
    </div>
  </div>
</footer>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script src="<?= BASE_URL ?>assets/js/home.js"></script>
</body>
</html>