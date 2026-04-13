<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;

function sanitize_next(string $next): string
{
    $next = trim($next);
    if ($next === '') {
        return 'frontOffice.php';
    }

    if (str_contains($next, "\r") || str_contains($next, "\n")) {
        return 'frontOffice.php';
    }

    // Prevent open redirects to other origins / schemes.
    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $next) === 1) {
        return 'frontOffice.php';
    }

    if (str_starts_with($next, '//')) {
        return 'frontOffice.php';
    }

    return $next;
}

$next = isset($_GET['next']) && is_string($_GET['next']) ? sanitize_next($_GET['next']) : 'frontOffice.php';
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    Csrf::requireValid($_POST['_token'] ?? null);

    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id_PK, motdp FROM utilisateurs WHERE mail = :mail LIMIT 1');
        $stmt->execute([':mail' => $email]);
        $row = $stmt->fetch();

        $hash = is_array($row) ? (string)($row['motdp'] ?? '') : '';
        $id = is_array($row) ? (int)($row['id_PK'] ?? 0) : 0;

        if ($id > 0 && $hash !== '' && password_verify($password, $hash)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            Response::redirect($next);
        }

        $error = 'Invalid email or password.';
    }
}

$user = Auth::user();
$isLocal = in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
$nextEncoded = urlencode($next);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MediFlow | Login</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, .font-headline { font-family: 'Manrope', sans-serif; }
  </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 flex items-center justify-center px-4">
  <div class="w-full max-w-md bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
    <div class="mb-6">
      <div class="text-sm font-semibold text-slate-500">MediFlow</div>
      <h1 class="text-2xl font-extrabold tracking-tight text-blue-900">Login</h1>
      <p class="text-sm text-slate-500 mt-1">Sign in to like, comment, and access the CMS.</p>
    </div>

    <?php if ($user !== null): ?>
      <div class="mb-5 rounded-xl border border-slate-100 bg-slate-50 p-4">
        <div class="text-sm font-semibold">Already logged in</div>
        <div class="text-sm text-slate-600 mt-1">
          <?= e(trim((string)($user['prenom'] . ' ' . $user['nom']))) ?>
          <?php if (!empty($user['role_libelle'])): ?>
            <span class="text-slate-400">(<?= e((string)$user['role_libelle']) ?>)</span>
          <?php endif; ?>
        </div>
        <div class="flex gap-3 mt-4">
          <a class="flex-1 text-center py-2.5 rounded-xl bg-blue-700 text-white font-semibold hover:bg-blue-800" href="<?= e($next) ?>">Continue</a>
          <a class="flex-1 text-center py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50" href="logout.php?next=<?= e($nextEncoded) ?>">Logout</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($error !== null): ?>
      <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" action="login.php?next=<?= e($nextEncoded) ?>" class="space-y-4">
      <?= csrf_field() ?>

      <div>
        <label class="block text-xs font-bold text-slate-600">Email</label>
        <input name="email" type="email" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="name@mediflow.com" value="<?= e((string)($_POST['email'] ?? '')) ?>" />
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-600">Password</label>
        <input name="password" type="password" required class="mt-1 w-full rounded-xl border-slate-200" placeholder="••••••••" />
      </div>

      <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-700 text-white font-semibold hover:bg-blue-800">
        Sign In
      </button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
      <a class="text-slate-500 hover:text-blue-700 font-semibold" href="frontOffice.php">Front Office</a>
      <a class="text-slate-500 hover:text-blue-700 font-semibold" href="backOffice.php">Back Office</a>
    </div>

    <?php if ($isLocal): ?>
      <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 p-4">
        <div class="text-xs font-bold text-slate-600">Default dev accounts</div>
        <ul class="mt-2 text-xs text-slate-600 space-y-1">
          <li><span class="font-semibold">Admin</span>: admin2@mediflow.com / admin123</li>
          <li><span class="font-semibold">Magazine</span>: magazine@mediflow.com / magazine123</li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
