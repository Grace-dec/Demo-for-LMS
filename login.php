<?php
// login.php — Dedicated login page
require_once __DIR__ . '/config.php';

// Already logged in → go straight to dashboard
if (is_logged_in()) {
    header('Location: ' . role_dashboard(current_role()));
    exit;
}

$error    = '';
$redirect = '';   // only set to a REAL target when something protected needs it

// Only honour a redirect that points at a protected internal page
// (e.g. student/course_detail.php?id=3)
// Ignore generic values like "home.php" — those should never override the dashboard.
$SAFE_REDIRECTS = ['student/', 'instructor/', 'admin/'];
$raw = $_GET['redirect'] ?? '';
foreach ($SAFE_REDIRECTS as $prefix) {
    if (str_starts_with($raw, $prefix)) {
        $redirect = $raw;
        break;
    }
}

// ── Handle form submission ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $postRedir=      $_POST['redirect'] ?? '';

    // Validate redirect from POST too
    $safePostRedir = '';
    foreach ($SAFE_REDIRECTS as $prefix) {
        if (str_starts_with($postRedir, $prefix)) {
            $safePostRedir = $postRedir;
            break;
        }
    }

    if (!$email || !$password) {
        $error = 'Please fill in both fields.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_active']) {
                $error = 'Your account has been disabled. Please contact the administrator.';
            } else {
                // ── Successful login ────────────────────────
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];

                // Priority: safe internal redirect → role dashboard
                if ($safePostRedir) {
                    $dest = BASE_URL . ltrim($safePostRedir, '/');
                } else {
                    // Always send to the correct dashboard by role — no exceptions
                    $dest = role_dashboard($user['role']);
                }

                header('Location: ' . $dest);
                exit;
            }
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}

// System messages passed via ?msg=
$msg_map = [
    'login_required' => 'Please sign in to access that page.',
    'access_denied'  => 'You do not have permission to view that page.',
    'logged_out'     => 'You have been logged out successfully.',
    'registered'     => '✅ Account created! Sign in below — you will go straight to your dashboard.',
];
$info = $msg_map[$_GET['msg'] ?? ''] ?? '';

// Pre-fill email when coming from register.php redirect
$prefill_email = isset($_GET['email']) ? trim($_GET['email']) : '';

// Live stats for the left panel
$stat_courses  = $pdo->query('SELECT COUNT(*) FROM courses WHERE is_published=1')->fetchColumn();
$stat_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — lightlearn LMS</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <style>
    /* ── Page layout ────────────────────────────────────── */
    .login-page {
      min-height: 100vh;
      display: flex;
    }

    /* ── Left decorative panel ──────────────────────────── */
    .login-left {
      display: none;
      flex: 1;
      background: linear-gradient(145deg, #386fe5 0%, #3961d2 50%, #3670da 100%);
      position: relative;
      overflow: hidden;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      padding: 60px 50px;
      color: #fff;
      text-align: center;
    }
    @media (min-width: 900px) { .login-left { display: flex; } }

    /* Decorative circles */
    .login-left::before {
      content: '';
      position: absolute;
      width: 420px; height: 420px;
      background: rgba(255,255,255,.04);
      border-radius: 50%;
      top: -100px; left: -100px;
    }
    .login-left::after {
      content: '';
      position: absolute;
      width: 280px; height: 280px;
      background: rgba(245,158,11,.08);
      border-radius: 50%;
      bottom: -60px; right: -60px;
    }

    .login-left-logo  { font-size: 3.2rem; margin-bottom: 18px; }
    .login-left-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.1rem; font-weight: 700;
      line-height: 1.25; margin-bottom: 14px;
    }
    .login-left-sub {
      font-size: .92rem; color: rgba(255,255,255,.65);
      line-height: 1.75; max-width: 320px;
    }
    .login-left-stats {
      display: flex; gap: 36px; margin-top: 44px;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.10);
      border-radius: 14px; padding: 20px 32px;
    }
    .login-left-stat strong {
      display: block; font-size: 1.9rem; font-weight: 700; color: #f59e0b;
    }
    .login-left-stat span {
      font-size: .74rem; color: rgba(255,255,255,.50);
      text-transform: uppercase; letter-spacing: .08em;
    }

    /* Role preview badges */
    .login-roles {
      display: flex; gap: 10px; margin-top: 30px; flex-wrap: wrap;
      justify-content: center;
    }
    .login-role-badge {
      padding: 6px 16px; border-radius: 99px; font-size: .78rem; font-weight: 600;
      border: 1px solid rgba(255,255,255,.15); color: rgba(255,255,255,.75);
    }
    .login-role-badge.admin      { border-color: #a78bfa; color: #c4b5fd; }
    .login-role-badge.instructor { border-color: #60a5fa; color: #93c5fd; }
    .login-role-badge.student    { border-color: #34d399; color: #6ee7b7; }

    /* ── Right form panel ───────────────────────────────── */
    .login-right {
      flex: 0 0 100%;
      display: flex; align-items: center; justify-content: center;
      background: #f8fafc; padding: 40px 24px;
    }
    @media (min-width: 900px) { .login-right { flex: 0 0 460px; } }

    .login-box { width: 100%; max-width: 390px; }

    .login-back {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: .82rem; color: #94a3b8; margin-bottom: 30px;
      text-decoration: none; transition: color .15s;
    }
    .login-back:hover { color: #1a2744; }

    .login-header { margin-bottom: 26px; }
    .login-header h1 {
      font-size: 1.7rem; font-weight: 700; color: #1a2744; margin-bottom: 6px;
    }
    .login-header p { font-size: .87rem; color: #94a3b8; line-height: 1.55; }

    /* Divider */
    .login-divider {
      display: flex; align-items: center; gap: 12px;
      margin: 20px 0; font-size: .76rem; color: #cbd5e1;
    }
    .login-divider::before, .login-divider::after {
      content: ''; flex: 1; height: 1px; background: #e2e8f0;
    }

    /* Register link */
    .login-register-row {
      text-align: center; margin-top: 22px;
      font-size: .85rem; color: #94a3b8;
    }
    .login-register-row a { color: #1a2744; font-weight: 600; text-decoration: none; }
    .login-register-row a:hover { text-decoration: underline; }

    /* Role destination notice — shown AFTER role is chosen */
    .login-dest-notice {
      display: flex; align-items: center; gap: 10px;
      background: #f0f9ff; border: 1px solid #bae6fd;
      border-radius: 10px; padding: 11px 14px;
      font-size: .82rem; color: #0369a1; margin-bottom: 18px;
    }
    .login-dest-notice span { font-size: 1.2rem; }

    /* Password toggle */
    .input-wrap { position: relative; }
    .input-wrap input { padding-right: 44px; }
    .pw-toggle {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      font-size: 1rem; color: #94a3b8; padding: 4px;
      transition: color .15s;
    }
    .pw-toggle:hover { color: #1a2744; }


  </style>
</head>
<body>
<div class="login-page">

  <!-- ════════════════════════════════════════════════════
       LEFT  —  decorative brand panel
  ════════════════════════════════════════════════════ -->
  <div class="login-left">
    <div class="login-left-logo"></div>
    <div class="login-left-title">Welcome Back<br>to lightlearn</div>


  <!-- ════════════════════════════════════════════════════
       RIGHT  —  login form
  ════════════════════════════════════════════════════ -->
  <div class="login-right">
    <div class="login-box">

      <a href="home.php" class="login-back">← Back to Home</a>

      <div class="login-header">
        <h1>Sign In</h1>
      </div>


      <!-- Error / info alerts -->
      <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>
      <?php if ($info): ?>
        <div class="alert alert-info"><?= e($info) ?></div>
      <?php endif; ?>

      <!-- ── Login form ──────────────────────────────── -->
      <form method="post" action="login.php">
        <!--
          Only pass redirect if it's a real internal protected page.
          Leave empty when coming from home.php so users always land
          on their correct role dashboard.
        -->
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required autofocus
                 value="<?= e($_POST['email'] ?? $prefill_email) ?>"
                 placeholder="you@example.com">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input type="password" id="password" name="password"
                   required placeholder="••••••••">
            <button type="button" class="pw-toggle" id="pw-toggle"
                    title="Show / hide password">👁</button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block"
                style="padding:13px;font-size:.95rem;margin-top:6px;border-radius:10px">
          Sign In →
        </button>
      </form>

      <div class="login-divider">or</div>

      <a href="home.php" class="btn btn-outline btn-block"
         style="justify-content:center;padding:11px;border-radius:10px">
        Browse Courses Without an Account
      </a>

      <div class="login-register-row">
        No account yet? &nbsp;<a href="register.php">Register for free</a>
      </div>

    </div>
  </div>

</div><!-- .login-page -->

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
// Password show/hide toggle
document.getElementById('pw-toggle').addEventListener('click', function () {
  const inp = document.getElementById('password');
  if (inp.type === 'password') {
    inp.type = 'text';
    this.textContent = '🙈';
  } else {
    inp.type = 'password';
    this.textContent = '👁';
  }
});
</script>
</body>
</html>