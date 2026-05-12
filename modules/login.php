<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/db.php';
require_once '../config/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $result = attempt_login($pdo, $email, $password);

    if (is_string($result)) {
        $error = $result;
    } else {
        create_session($pdo, $result);
        header('Location: ../index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In — MTRTS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            olfu: {
              green:      '#1a5c2a',
              'green-md': '#1f6e32',
              'green-lt': '#256b38',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen flex">

  <div class="hidden md:flex w-1/2 bg-olfu-green flex-col items-center justify-between py-12 px-10 relative overflow-hidden">

    <div class="absolute -top-24 -left-24 w-80 h-80 rounded-full border border-white/10"></div>
    <div class="absolute top-10 -left-10  w-56 h-56 rounded-full border border-white/10"></div>
    <div class="absolute -bottom-20 -right-20 w-96 h-96 rounded-full border border-white/10"></div>
    <div class="absolute bottom-20 right-10  w-60 h-60 rounded-full border border-white/10"></div>

    <div></div>

    <!-- Centre content -->
    <div class="relative flex flex-col items-center text-center gap-6">

      <!-- Logo -->
      <img src="../public/assets/images/logo.png" alt="OLFU Logo" class="w-35 h-35 object-contain drop-shadow-md" />

      <div>
        <h1 class="text-white text-2xl font-bold tracking-wide">Our Lady of Fatima University</h1>
        <p class="text-white/60 text-sm italic mt-1">Veritas et Misericordia</p>
      </div>

      <!-- Divider -->
      <div class="w-10 h-px bg-white/30"></div>

      <!-- App card -->
      <div class="bg-white/10 border border-white/20 rounded-xl px-8 py-6 max-w-xs">
        <p class="text-white font-bold text-base leading-snug">Media Technology Repair Tracker</p>
        <p class="text-white/70 text-sm mt-2 leading-relaxed">
          Asset management and repair ticketing system for institutional AV equipment
        </p>
      </div>
    </div>

    <!-- Footer -->
    <p class="relative text-white/40 text-xs">&copy; 2026 OLFU &mdash; MTRTS v1.0</p>
  </div>

  <!-- ── RIGHT PANEL ────────────────────────────────────────── -->
  <div class="flex-1 flex items-center justify-center bg-gray-50 px-6 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-100 px-10 py-10">

      <!-- Heading -->
      <div class="mb-7">
        <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
        <p class="text-gray-500 text-sm mt-1">Sign in to your MTRTS account</p>
      </div>

      <!-- Error banner -->
      <?php if ($error !== ''): ?>
        <div class="mb-5 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Google SSO (placeholder — disabled until SSO is implemented) -->
      <button
        type="button"
        disabled
        title="Google SSO is not yet available"
        class="w-full flex items-center justify-center gap-3 border border-gray-200 rounded-lg py-2.5 text-sm font-medium text-gray-500 bg-white cursor-not-allowed opacity-60 mb-5"
      >
        <!-- Google "G" icon -->
        <svg class="w-5 h-5" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
          <path fill="#EA4335" d="M24 9.5c3.14 0 5.95 1.08 8.17 2.85l6.08-6.08C34.49 3.06 29.55 1 24 1 14.82 1 6.98 6.48 3.27 14.24l7.07 5.49C12.09 13.37 17.6 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.52 24.5c0-1.64-.15-3.22-.42-4.74H24v8.97h12.69c-.55 2.94-2.2 5.43-4.68 7.1l7.18 5.58C43.28 37.33 46.52 31.38 46.52 24.5z"/>
          <path fill="#FBBC05" d="M10.34 28.27A14.58 14.58 0 0 1 9.5 24c0-1.48.26-2.91.72-4.26l-7.07-5.49A23.95 23.95 0 0 0 .5 24c0 3.86.93 7.51 2.58 10.74l7.26-6.47z"/>
          <path fill="#34A853" d="M24 47c5.55 0 10.2-1.84 13.6-4.98l-7.18-5.58c-1.84 1.24-4.2 1.97-6.42 1.97-6.4 0-11.91-3.87-13.66-9.14l-7.26 6.47C6.98 41.52 14.82 47 24 47z"/>
        </svg>
        Continue with Google
      </button>

      <!-- Divider -->
      <div class="flex items-center gap-3 mb-5">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="text-xs text-gray-400">or sign in with email</span>
        <div class="flex-1 h-px bg-gray-200"></div>
      </div>

      <!-- Login form -->
      <form method="POST" action="" novalidate>

        <!-- Email -->
        <div class="mb-4">
          <label for="email" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
            Email Address
          </label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="you@olfu.edu.ph"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
            class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm text-gray-800 placeholder-gray-400
                   focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent transition"
          />
        </div>

        <!-- Password -->
        <div class="mb-1">
          <label for="password" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1.5">
            Password
          </label>
          <div class="relative">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
              class="w-full border border-gray-200 rounded-lg px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder-gray-400
                     focus:outline-none focus:ring-2 focus:ring-olfu-green focus:border-transparent transition"
            />
            <!-- Toggle visibility -->
            <button
              type="button"
              onclick="togglePassword()"
              class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600"
              tabindex="-1"
              aria-label="Toggle password visibility"
            >
              <!-- Eye icon -->
              <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <!-- Eye-off icon (hidden by default) -->
              <svg id="icon-eye-off" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.087-3.288M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.384 5.293M3 3l18 18"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Forgot password -->
        <div class="flex justify-end mb-6">
          <a href="#" class="text-xs text-olfu-green hover:underline font-medium">Forgot password?</a>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          class="w-full bg-olfu-green hover:bg-olfu-green-md active:bg-olfu-green-lt text-white font-semibold
                 rounded-lg py-2.5 text-sm transition-colors duration-150"
        >
          Sign In
        </button>

      </form>

      <!-- Footer note -->
      <p class="text-center text-xs text-gray-400 mt-7 leading-relaxed">
        Don&rsquo;t have an account? Contact your
        <strong class="text-gray-600 font-semibold">IT Administrator</strong>
        to get access.
      </p>

    </div>
  </div>

</body>
<script>
  function togglePassword() {
    const input  = document.getElementById('password');
    const eyeOn  = document.getElementById('icon-eye');
    const eyeOff = document.getElementById('icon-eye-off');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    eyeOn.classList.toggle('hidden', isHidden);
    eyeOff.classList.toggle('hidden', !isHidden);
  }
</script>
</html>
