<?php
declare(strict_types=1);
require_once __DIR__ . '/../helpers/auth.php';
$viewer = currentUser();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Bản đồ Quốc gia</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <h1>Bản đồ Quốc gia</h1>
        <p>Nhấp vào bản đồ để xem thông tin chi tiết về từng quốc gia</p>
      </div>
      <div class="search">
        <input id="searchInput" placeholder="Tìm quốc gia (ví dụ: Việt Nam, Nhật Bản, Pháp)..." autocomplete="off" />
        <div class="pill" id="status"></div>
      </div>
      <div class="authArea">
        <?php if ($viewer): ?>
          <span class="pill">Xin chào, <?= htmlspecialchars((string)$viewer['name']) ?></span>
          <?php if (isAdmin($viewer)): ?>
            <a class="authBtn" href="pages/admin/index.php">Admin</a>
          <?php endif; ?>
          <a class="authBtn" href="api/auth/logout.php">Đăng xuất</a>
        <?php else: ?>
          <a class="authBtn" href="pages/auth/index.php">Đăng nhập / Đăng ký</a>
        <?php endif; ?>
      </div>
    </div>

