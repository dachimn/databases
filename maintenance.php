<?php
declare(strict_types=1);

function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$status = null;
$message = '';

if (is_post()) {
    require_once __DIR__ . '/dbconnect.php';

    $u = trim($_POST['username'] ?? '');
    $p = (string)($_POST['password'] ?? '');

    if ($u === '' || $p === '') {
        $status = 'error';
        $message = 'Username and password are required.';
    } else {
        $mysqli = @new mysqli($host, $user, $pass, $dbname);
        if ($mysqli->connect_errno) {
            $status = 'error';
            $message = 'Service unavailable. Try again later.';
        } else {
            $sql = 'SELECT `UserPassword` FROM `users` WHERE `UserName` = ? LIMIT 1';
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param('s', $u);
                $stmt->execute();
                $stmt->bind_result($hash);
                $rowFound = $stmt->fetch();
                $stmt->close();

                if ($rowFound && $hash && password_verify($p, $hash)) {
                    $status = 'ok';
                    $message = 'Login successful.';
                } else {
                    $status = 'error';
                    $message = 'Invalid username or password.';
                }
            } else {
                $status = 'error';
                $message = 'Service unavailable. Try again later.';
            }
            $mysqli->close();
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login</title>
  <link rel="stylesheet" href="loginStyle.css" />
</head>
<body>
  <div class="container">
    <h1 class="title">Login</h1>
    <p class="subtitle">Authenticate to continue</p>

    <div class="card">
      <?php if ($status === 'error'): ?>
        <div class="alert">
          <strong>Error:</strong> <span><?php echo h($message); ?></span>
        </div>
      <?php elseif ($status === 'ok'): ?>
        <div class="alert" style="background:#ecfdf5;border-color:#bbf7d0;color:#065f46;">
          <strong>Success:</strong> <span><?php echo h($message); ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="login.php" class="form">
        <div class="field">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" required placeholder="e.g., dachi"
                 value="<?php echo isset($_POST['username']) ? h($_POST['username']) : ''; ?>" />
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required placeholder="••••••••" />
        </div>

        <button type="submit" class="button">Sign In</button>
      </form>
    </div>
  </div>
</body>
</html>
