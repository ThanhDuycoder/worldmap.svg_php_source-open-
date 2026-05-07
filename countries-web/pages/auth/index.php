<?php
declare(strict_types=1);
require_once __DIR__ . '/../../helpers/auth.php';
$viewer = currentUser();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Đăng nhập - Bản đồ Quốc gia</title>
  <link rel="stylesheet" href="../../assets/css/style.css" />
</head>
<body>
  <div class="wrap authWrap">
    <div class="card authCard">
      <div class="hd">
        <h2>Đăng nhập / Đăng ký</h2>
        <a class="authBtn" href="../../index.php">Về trang chủ</a>
      </div>
      <div class="bd">
        <?php if ($viewer): ?>
          <p>Bạn đang đăng nhập với <strong><?= htmlspecialchars((string)$viewer['email']) ?></strong></p>
          <a class="authBtn" href="../../api/auth/logout.php">Đăng xuất</a>
        <?php else: ?>
          <div class="authTabs">
            <button class="authTab active" data-tab="login" type="button">Đăng nhập</button>
            <button class="authTab" data-tab="register" type="button">Đăng ký</button>
          </div>

          <form id="loginForm" class="authForm">
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Mật khẩu" required />
            <button class="authBtn" type="submit">Đăng nhập</button>
          </form>

          <form id="registerForm" class="authForm hidden">
            <input type="text" name="name" placeholder="Họ và tên" required />
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Mật khẩu (>= 6 ký tự)" required />
            <button class="authBtn" type="submit">Đăng ký</button>
          </form>

          <div class="authDivider">hoặc</div>
          <a class="authBtn authGoogleBtn" href="../../api/oauth/google/start.php">Đăng nhập với Google</a>
          <p id="authMessage" class="status"></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    const tabs = document.querySelectorAll('.authTab');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const message = document.getElementById('authMessage');

    tabs.forEach((btn) => {
      btn.addEventListener('click', () => {
        tabs.forEach((x) => x.classList.remove('active'));
        btn.classList.add('active');
        const tab = btn.dataset.tab;
        loginForm.classList.toggle('hidden', tab !== 'login');
        registerForm.classList.toggle('hidden', tab !== 'register');
        message.textContent = '';
      });
    });

    async function submitForm(url, formEl) {
      const data = new FormData(formEl);
      message.textContent = 'Đang xử lý...';
      try {
        const res = await fetch(url, { method: 'POST', body: data });
        const json = await res.json();
        if (!res.ok || !json.ok) {
          throw new Error(json?.error?.message || 'Thao tác thất bại.');
        }
        message.textContent = 'Thành công. Đang chuyển hướng...';
        window.location.href = '../../index.php';
      } catch (err) {
        message.textContent = err.message || 'Đã xảy ra lỗi.';
      }
    }

    loginForm?.addEventListener('submit', (e) => {
      e.preventDefault();
      submitForm('../../api/auth/login.php', loginForm);
    });

    registerForm?.addEventListener('submit', (e) => {
      e.preventDefault();
      submitForm('../../api/auth/register.php', registerForm);
    });
  </script>
</body>
</html>
