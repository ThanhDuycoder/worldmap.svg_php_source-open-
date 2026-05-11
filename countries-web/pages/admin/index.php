<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
$viewer = requireAdmin();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Countries Web</title>
  <link rel="stylesheet" href="../../assets/css/style.css" />
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="hd">
        <h2>Quản trị</h2>
        <div style="display:flex; gap:8px; align-items:center;">
          <span class="pill">Xin chào, <?= htmlspecialchars((string)$viewer['name']) ?></span>
          <a class="authBtn" href="../../index.php">Về trang chủ</a>
        </div>
      </div>
      <div class="bd">
        <div class="adminTabs">
          <button class="adminTab active" data-tab="stats" type="button">Tổng quan</button>
          <button class="adminTab" data-tab="users" type="button">Người dùng</button>
          <button class="adminTab" data-tab="settings" type="button">Cấu hình</button>
          <button class="adminTab" data-tab="cache" type="button">Cache</button>
          <button class="adminTab" data-tab="logs" type="button">Logs</button>
        </div>

        <p class="status" id="adminStatus">Đang tải...</p>

        <div id="tab_stats" class="adminTabPanel">
          <div class="adminCards" id="statsCards"></div>
        </div>

        <div id="tab_users" class="card adminTabPanel hidden" style="margin-top:12px;">
          <div class="hd">
            <h2>Người dùng</h2>
            <button class="authBtn" id="reloadBtn" type="button">Tải lại</button>
          </div>
          <div class="bd" style="overflow:auto;">
            <table class="adminTable" id="usersTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Email</th>
                  <th>Tên đăng nhập</th>
                  <th>Tên hiển thị</th>
                  <th>Provider</th>
                  <th>Admin</th>
                  <th>Bị chặn</th>
                  <th>Tạo lúc</th>
                  <th>Hành động</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>

        <div id="tab_settings" class="card adminTabPanel hidden" style="margin-top:12px;">
          <div class="hd">
            <h2>Cấu hình Gemini</h2>
            <button class="authBtn" id="settingsReloadBtn" type="button">Tải lại</button>
          </div>
          <div class="bd">
            <form id="settingsForm" class="adminForm">
              <label>
                <div class="pill">GEMINI model</div>
                <input name="gemini_model" placeholder="gemini-1.5-flash-latest" required />
              </label>
              <label>
                <div class="pill">Temperature (0..2)</div>
                <input name="gemini_temperature" placeholder="0.4" required />
              </label>
              <label>
                <div class="pill">Max output tokens (128..8192)</div>
                <input name="gemini_max_output_tokens" placeholder="4096" required />
              </label>
              <div style="display:flex; gap:8px; align-items:center; margin-top:10px;">
                <button class="authBtn" type="submit">Lưu cấu hình</button>
                <span class="status" id="settingsStatus"></span>
              </div>
            </form>
          </div>
        </div>

        <div id="tab_cache" class="card adminTabPanel hidden" style="margin-top:12px;">
          <div class="hd">
            <h2>Cache</h2>
          </div>
          <div class="bd">
            <p class="status">Cache quốc gia nằm ở file `cache/countries.json`.</p>
            <button class="authBtn" id="clearCacheBtn" type="button">Xoá cache quốc gia</button>
            <span class="status" id="cacheStatus" style="margin-left:10px;"></span>
          </div>
        </div>

        <div id="tab_logs" class="card adminTabPanel hidden" style="margin-top:12px;">
          <div class="hd">
            <h2>Logs</h2>
            <div style="display:flex; gap:8px; align-items:center;">
              <button class="authBtn" id="logsReloadBtn" type="button">Tải lại</button>
            </div>
          </div>
          <div class="bd">
            <pre class="adminLog" id="logBox">(chưa có log)</pre>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const statusEl = document.getElementById('adminStatus');
    const reloadBtn = document.getElementById('reloadBtn');
    const tbody = document.querySelector('#usersTable tbody');
    const statsCards = document.getElementById('statsCards');
    const settingsForm = document.getElementById('settingsForm');
    const settingsStatus = document.getElementById('settingsStatus');
    const settingsReloadBtn = document.getElementById('settingsReloadBtn');
    const clearCacheBtn = document.getElementById('clearCacheBtn');
    const cacheStatus = document.getElementById('cacheStatus');
    const logsReloadBtn = document.getElementById('logsReloadBtn');
    const logBox = document.getElementById('logBox');

    // Tabs
    const tabBtns = document.querySelectorAll('.adminTab');
    function showTab(name) {
      tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
      document.querySelectorAll('.adminTabPanel').forEach(p => p.classList.add('hidden'));
      const panel = document.getElementById('tab_' + name);
      panel?.classList.remove('hidden');
    }
    tabBtns.forEach(btn => btn.addEventListener('click', () => showTab(btn.dataset.tab)));

    function setStatus(msg) {
      statusEl.textContent = msg;
    }

    function esc(s) {
      return String(s ?? '').replace(/[&<>\"']/g, (c) => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'
      }[c]));
    }

    async function apiGet(path) {
      const res = await fetch(path, { headers: { 'Accept': 'application/json' }});
      const json = await res.json().catch(() => null);
      if (!res.ok || !json?.ok) throw new Error(json?.error?.message || `Yêu cầu thất bại (${res.status})`);
      return json.data;
    }

    async function apiPost(path, body) {
      const res = await fetch(path, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(body || {})
      });
      const json = await res.json().catch(() => null);
      if (!res.ok || !json?.ok) throw new Error(json?.error?.message || `Yêu cầu thất bại (${res.status})`);
      return json.data;
    }

    function renderUsers(users) {
      tbody.innerHTML = '';
      for (const u of (users || [])) {
        const tr = document.createElement('tr');
        const isAdmin = Number(u.is_admin) === 1;
        const isBanned = Number(u.is_banned) === 1;

        tr.innerHTML = `
          <td>${esc(u.id)}</td>
          <td>${esc(u.email)}</td>
          <td>${esc(u.username)}</td>
          <td>${esc(u.name)}</td>
          <td>${esc(u.provider)}</td>
          <td>
            <label style="display:inline-flex;gap:8px;align-items:center;">
              <input type="checkbox" ${isAdmin ? 'checked' : ''} data-action="toggle-admin" data-id="${esc(u.id)}" />
              <span class="pill">${isAdmin ? 'admin' : 'user'}</span>
            </label>
          </td>
          <td>
            <label style="display:inline-flex;gap:8px;align-items:center;">
              <input type="checkbox" ${isBanned ? 'checked' : ''} data-action="toggle-ban" data-id="${esc(u.id)}" />
              <span class="pill">${isBanned ? 'banned' : 'ok'}</span>
            </label>
          </td>
          <td>${esc(u.created_at)}</td>
          <td>
            <button class="authBtn" data-action="delete-user" data-id="${esc(u.id)}" type="button">Xoá</button>
          </td>
        `;
        tbody.appendChild(tr);
      }
    }

    function renderStats(stats) {
      if (!statsCards) return;
      statsCards.innerHTML = '';
      const items = [
        { label: 'Tổng người dùng', value: stats?.users_total ?? 0 },
        { label: 'Admin', value: stats?.users_admin ?? 0 },
        { label: 'Bị chặn', value: stats?.users_banned ?? 0 },
      ];
      for (const it of items) {
        const div = document.createElement('div');
        div.className = 'adminCard';
        div.innerHTML = `<div class="k">${esc(it.label)}</div><div class="v">${esc(it.value)}</div>`;
        statsCards.appendChild(div);
      }
    }

    async function loadStats() {
      const stats = await apiGet('../../api/admin/stats.php');
      renderStats(stats);
    }

    async function loadSettings() {
      const s = await apiGet('../../api/admin/settings_get.php');
      settingsForm.gemini_model.value = s.gemini_model || '';
      settingsForm.gemini_temperature.value = s.gemini_temperature || '';
      settingsForm.gemini_max_output_tokens.value = s.gemini_max_output_tokens || '';
    }

    async function loadLogs() {
      const data = await apiGet('../../api/admin/logs_tail.php?lines=200');
      const lines = data?.lines || [];
      logBox.textContent = lines.length ? lines.join('\n') : '(chưa có log)';
    }

    async function loadUsers() {
      setStatus('Đang tải danh sách người dùng...');
      const data = await apiGet('../../api/admin/users.php');
      renderUsers(data?.users || []);
      setStatus(`Đã tải ${data?.users?.length || 0} người dùng.`);
    }

    tbody.addEventListener('click', async (e) => {
      const el = e.target;
      const action = el?.dataset?.action;
      const id = Number(el?.dataset?.id || 0);
      if (!action || !id) return;

      if (action === 'delete-user') {
        if (!confirm('Xoá người dùng này?')) return;
        try {
          await apiPost('../../api/admin/user_delete.php', { id });
          await loadUsers();
        } catch (err) {
          alert(err?.message || 'Không xoá được người dùng.');
        }
      }
    });

    tbody.addEventListener('change', async (e) => {
      const el = e.target;
      if (!el?.dataset) return;
      if (el.dataset.action !== 'toggle-admin' && el.dataset.action !== 'toggle-ban') return;
      const id = Number(el.dataset.id || 0);
      if (!id) return;
      const field = el.dataset.action === 'toggle-admin' ? 'is_admin' : 'is_banned';
      const value = !!el.checked;
      try {
        await apiPost('../../api/admin/user_update.php', { id, [field]: value });
        await loadUsers();
      } catch (err) {
        alert(err?.message || (field === 'is_admin' ? 'Không cập nhật được quyền admin.' : 'Không cập nhật được trạng thái chặn.'));
        // revert UI state
        el.checked = !value;
      }
    });

    reloadBtn?.addEventListener('click', () => loadUsers().catch(err => setStatus(err?.message || 'Lỗi tải.')));

    settingsReloadBtn?.addEventListener('click', () => loadSettings().catch(err => settingsStatus.textContent = err?.message || 'Lỗi tải.'));

    settingsForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      settingsStatus.textContent = 'Đang lưu...';
      try {
        const body = {
          gemini_model: settingsForm.gemini_model.value,
          gemini_temperature: settingsForm.gemini_temperature.value,
          gemini_max_output_tokens: settingsForm.gemini_max_output_tokens.value,
        };
        await apiPost('../../api/admin/settings_update.php', body);
        settingsStatus.textContent = 'Đã lưu.';
      } catch (err) {
        settingsStatus.textContent = err?.message || 'Không lưu được.';
      }
    });

    clearCacheBtn?.addEventListener('click', async () => {
      if (!confirm('Xoá cache quốc gia?')) return;
      cacheStatus.textContent = 'Đang xoá...';
      try {
        const data = await apiPost('../../api/admin/cache_clear.php', {});
        cacheStatus.textContent = `Đã xoá: ${(data?.deleted || []).join(', ') || '(không có file)'}`;
      } catch (err) {
        cacheStatus.textContent = err?.message || 'Xoá cache thất bại.';
      }
    });

    logsReloadBtn?.addEventListener('click', () => loadLogs().catch(() => {}));

    async function boot() {
      setStatus('Đang tải...');
      await Promise.allSettled([loadStats(), loadUsers(), loadSettings(), loadLogs()]);
      setStatus('Sẵn sàng.');
      showTab('stats');
    }

    boot().catch((err) => setStatus(err?.message || 'Không tải được.'));
  </script>
</body>
</html>

