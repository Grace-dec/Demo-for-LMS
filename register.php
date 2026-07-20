<?php
// register.php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header('Location: ' . role_dashboard(current_role()));
    exit;
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';
    $role     = $_POST['role']          ?? 'student';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['student','instructor'], true)) {
        $error = 'Invalid role selected.';
    } else {
        // Check duplicate email
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'That email address is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)');
            $ins->execute([$name, $email, $hash, $role]);

            // ✅ FIX: Redirect straight to login page with a success message
            // This ensures the user is taken to login.php and then to their
            // correct role dashboard — not back to home.php
            header('Location: ' . BASE_URL . 'login.php?msg=registered&email=' . urlencode($email));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — lightlearn LMS</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
  <img src="<?= BASE_URL ?>assets\image\logo2.png.PNG" 
       alt="LightLearn Logo" 
       style="height:60px; width:auto; object-fit:contain; margin-bottom:8px;">
  <small>Create your account</small>
</div>

    <h2>New Account</h2>

    <?php if ($error):   ?><div class="alert alert-error"><?=   e($error)   ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <form method="post" action="">
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required
               value="<?= e($_POST['name'] ?? '') ?>" placeholder="Jane Doe">
      </div>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required
               value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label for="role">I am a</label>
        <select id="role" name="role">
          <option value="student"     <?= (($_POST['role'] ?? '') === 'student')     ? 'selected' : '' ?>>Student</option>
          <option value="instructor"  <?= (($_POST['role'] ?? '') === 'instructor')  ? 'selected' : '' ?>>Instructor</option>
        </select>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="Min 6 characters">
      </div>
      <div class="form-group">
        <label for="confirm">Confirm Password</label>
        <input type="password" id="confirm" name="confirm" required placeholder="Repeat password">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>

    <p style="text-align:center; margin-top:18px; font-size:.86rem; color:#94a3b8;">
      Already have an account? <a href="login.php" style="color:#1a2744; font-weight:600;">Sign in</a>
    </p>
  </div>
  <script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>