<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../includes/profile_bootstrap.php';
$adminName = htmlspecialchars($sharedProfileName, ENT_QUOTES, 'UTF-8');
$adminInitial = htmlspecialchars($sharedProfileInitials, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Accounts | Skwela College Enrollment System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=20261117-final">
    <link rel="stylesheet" href="../assets/css/profile-modal.css?v=20261117-final">
    <link rel="stylesheet" href="../assets/css/sidebar-unified.css?v=20261117-final">
    <style>
        .main-area { margin-left: 255px; min-width: 0; }
        .account-content { max-width: 1600px; margin: 0 auto; padding: 34px 38px 28px; }
        .account-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:24px; }
        .account-heading h1 { margin:0; color:#172d4d; font:800 27px Manrope,sans-serif; letter-spacing:-.6px; }
        .account-heading p { margin:8px 0 0; color:#718096; font-size:12px; }
        .account-eyebrow { margin:0 0 8px; color:#7290b4; font-size:9px; font-weight:700; letter-spacing:1.5px; }
        .account-actions { display:flex; gap:9px; flex-wrap:wrap; }
        .account-btn { border:0; border-radius:8px; padding:11px 15px; font:700 11px Inter,sans-serif; cursor:pointer; transition:.18s; }
        .account-btn:hover { transform:translateY(-1px); }
        .account-btn.primary { background:#2467b7; color:#fff; }
        .account-btn.secondary { background:#edf3fa; color:#315476; }
        .account-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:20px; }
        .account-stat { background:#fff; border:1px solid #e7edf5; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(15,23,42,.035); }
        .account-stat span { display:block; color:#718096; font-size:11px; margin-bottom:10px; }
        .account-stat strong { display:block; color:#102a56; font:800 27px Manrope,sans-serif; }
        .account-panel { background:#fff; border:1px solid #e7edf5; border-radius:12px; box-shadow:0 2px 10px rgba(15,23,42,.035); overflow:hidden; }
        .account-panel-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:21px 23px; border-bottom:1px solid #e7edf5; }
        .account-panel-head h2 { margin:0; color:#172d4d; font:800 17px Manrope,sans-serif; }
        .account-panel-head p { margin:6px 0 0; color:#718096; font-size:11px; }
        .account-toolbar { display:flex; align-items:center; gap:10px; padding:16px 22px; }
        .account-search { width:min(440px,100%); height:39px; border:1px solid #dfe7f0; border-radius:8px; padding:0 12px; color:#263b58; outline:none; font:12px Inter,sans-serif; }
        .account-search:focus { border-color:#2467b7; box-shadow:0 0 0 3px rgba(36,103,183,.1); }
        .account-table-wrap { overflow-x:auto; border-top:1px solid #edf1f6; }
        .account-table { width:100%; min-width:820px; border-collapse:collapse; font-size:11px; }
        .account-table th { padding:13px 14px; background:#f5f8fc; color:#60748d; font-size:9px; text-align:left; letter-spacing:.35px; white-space:nowrap; }
        .account-table td { padding:14px; border-top:1px solid #edf1f6; color:#51647d; vertical-align:middle; }
        .account-table tbody tr:hover { background:#f8fbff; }
        .account-status { display:inline-block; padding:5px 9px; border-radius:20px; font-size:9px; font-weight:700; text-transform:uppercase; }
        .account-status.active { background:#e7f8ee; color:#21845d; }
        .account-status.inactive { background:#fff5df; color:#98702c; }
        .account-status.suspended { background:#fff0ee; color:#b45244; }
        .account-row-action { border:0; border-radius:6px; padding:7px 10px; font:700 10px Inter,sans-serif; cursor:pointer; }
        .account-row-action.deactivate { background:#fff2ed; color:#b35a40; }
        .account-row-action.activate { background:#eaf8f0; color:#21845d; }
        .account-empty { padding:35px 15px !important; color:#8795a8 !important; text-align:center; }
        .account-table-meta { display:flex; justify-content:space-between; gap:12px; padding:0 22px 12px; color:#8998aa; font-size:10px; }
        .account-message { display:none; margin:0 22px 15px; border-radius:8px; padding:11px 13px; color:#9d3838; background:#fff1f1; font-size:11px; }
        .account-message.show { display:block; }
        .account-message.success { color:#21845d; background:#eaf8f0; }
        .account-overlay { position:fixed; inset:0; z-index:100; display:flex; align-items:center; justify-content:center; padding:18px; background:rgba(12,30,56,.55); }
        .account-overlay[hidden] { display:none !important; }
        .account-modal { width:min(510px,100%); background:#fff; border-radius:14px; padding:24px; box-shadow:0 20px 60px rgba(16,42,86,.2); }
        .account-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:15px; margin-bottom:20px; }
        .account-modal-head h2 { margin:0; color:#1c3455; font:800 19px Manrope,sans-serif; }
        .account-modal-head p { margin:0 0 6px; color:#7290b4; font-size:9px; font-weight:700; letter-spacing:1.3px; }
        .account-close { width:32px; height:32px; border:0; border-radius:7px; background:#f0f3f7; color:#60748d; font-size:20px; cursor:pointer; }
        .account-form { display:grid; gap:14px; }
        .account-field { display:flex; flex-direction:column; gap:7px; }
        .account-field label { color:#40536d; font-size:11px; font-weight:700; }
        .account-field input { width:100%; height:42px; border:1px solid #dce5ef; border-radius:8px; padding:0 11px; background:#fbfcfe; color:#253c59; font:12px Inter,sans-serif; }
        .account-field small { color:#8492a4; font-size:10px; }
        .account-modal-actions { display:flex; justify-content:flex-end; gap:9px; padding-top:17px; margin-top:6px; border-top:1px solid #e7edf5; }
        .account-toast { position:fixed; right:24px; bottom:24px; z-index:120; background:#173b6a; color:#fff; padding:12px 16px; border-radius:9px; font-size:11px; box-shadow:0 12px 30px rgba(16,42,86,.2); display:none; }
        .account-toast.show { display:block; }
        @media(max-width:1100px) { .main-area { margin-left:220px; } .account-content { padding-left:25px; padding-right:25px; } }
        @media(max-width:700px) { .main-area { margin-left:0; } .account-content { padding:24px 15px; } .account-heading { align-items:flex-start; flex-direction:column; } .account-stats { grid-template-columns:1fr; } .account-panel-head { align-items:flex-start; flex-direction:column; } .account-toolbar { padding:13px; } .account-search { width:100%; } }

        .account-folder-list { display:grid; gap:10px; padding:16px 22px 20px; }
        .account-folder { border:1px solid #e3ebf4; border-radius:11px; background:#fff; overflow:hidden; }
        .account-folder > summary { display:flex; align-items:center; gap:11px; min-height:54px; padding:12px 15px; cursor:pointer; list-style:none; color:#1b365e; }
        .account-folder > summary::-webkit-details-marker { display:none; }
        .account-folder .folder-number { display:grid; place-items:center; width:25px; height:25px; border-radius:8px; background:#edf4ff; color:#2467b7; font-size:10px; font-weight:800; }
        .account-folder > summary strong { color:#203b60; font-size:12px; }
        .account-folder > summary small { margin-left:auto; color:#7b8da4; font-size:10px; }
        .account-folder .folder-chevron { color:#2467b7; font-size:17px; transition:transform .18s ease; }
        .account-folder[open] > summary { background:#f8fbff; border-bottom:1px solid #e8eef6; }
        .account-folder[open] .folder-chevron { transform:rotate(180deg); }
        .account-folder-content { padding:12px; }
        .account-folder-table-wrap { overflow-x:auto; border:1px solid #e8eef6; border-radius:9px; }
        .account-folder-table { width:100%; min-width:820px; border-collapse:collapse; font-size:11px; }
        .account-folder-table th { padding:12px 13px; background:#f5f8fc; color:#60748d; font-size:9px; text-align:left; letter-spacing:.35px; white-space:nowrap; }
        .account-folder-table td { padding:13px; border-top:1px solid #edf1f6; color:#51647d; vertical-align:middle; }
        .account-folder-table tbody tr:hover { background:#f8fbff; }
        .account-folder-empty { padding:25px 14px; color:#8795a8; font-size:11px; text-align:center; }
        @media(max-width:700px) { .account-folder-list { padding:12px; } .account-folder > summary { padding:10px; gap:8px; } .account-folder-content { padding:8px; } }
</style>
</head>
<body>
<div class="app-shell">
    <?php $activeAdminPage = 'registrars'; require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="main-area">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle navigation">☰</button>
            <div class="breadcrumb"><span>Workspace</span><b>/</b><strong>Registrar Accounts</strong></div>
            <button type="button" class="shared-profile-trigger" onclick="openSharedProfile()" aria-label="Open administrator profile">
                <?php if ($sharedProfileImageUrl !== ''): ?>
                    <img class="shared-profile-avatar" src="<?= htmlspecialchars($sharedProfileImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Administrator profile picture">
                <?php else: ?>
                    <span class="shared-profile-avatar"><?= $adminInitial ?></span>
                <?php endif; ?>
                <span class="shared-profile-trigger-name"><?= $adminName ?></span>
            </button>
        </header>

        <div class="account-content">
            <section class="account-heading">
                <div>
                    <p class="account-eyebrow">SKWELA COLLEGE ENROLLMENT SYSTEM</p>
                    <h1>Registrar Accounts</h1>
                    <p>Manage registrar accounts, review account status, and create new registrar users.</p>
                </div>
                <div class="account-actions">
                    <button class="account-btn secondary" id="refreshButton" type="button">↻ Refresh</button>
                    <button class="account-btn primary" id="addButton" type="button">＋ Add Registrar</button>
                </div>
            </section>

            <section class="account-stats" aria-label="Registrar account summary">
                <article class="account-stat"><span>Total Registrar Accounts</span><strong id="totalCount">—</strong></article>
                <article class="account-stat"><span>Active Accounts</span><strong id="activeCount">—</strong></article>
                <article class="account-stat"><span>Inactive / Suspended</span><strong id="inactiveCount">—</strong></article>
            </section>

            <section class="account-panel">
                <div class="account-panel-head">
                    <div><h2>Registered Registrars</h2><p>Only accounts assigned the Registrar role are shown here.</p></div>
                </div>
                <div class="account-toolbar"><input class="account-search" type="search" id="searchInput" placeholder="Search by name, email, or account ID..."></div>
                <div class="account-table-meta"><span id="recordCount">Loading registrar accounts…</span><span>Account status is managed by Admin</span></div>
                <div class="account-message" id="pageMessage" role="status"></div>
                <div class="account-folder-list" id="registrarAccountFolders" aria-live="polite"><div class="account-folder-empty">Loading registrar accounts…</div></div>
            </section>
        </div>
    </main>
</div>

<div class="account-overlay" id="formOverlay" hidden>
    <section class="account-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="account-modal-head"><div><p>NEW REGISTRAR ACCOUNT</p><h2 id="modalTitle">Create Registrar Account</h2></div><button class="account-close" type="button" data-close aria-label="Close">×</button></div>
        <form id="registrarForm" class="account-form">
            <div class="account-field"><label for="fullName">Full Name *</label><input type="text" id="fullName" maxlength="150" placeholder="Enter registrar's full name" required></div>
            <div class="account-field"><label for="email">Email Address *</label><input type="email" id="email" maxlength="150" placeholder="registrar@college.edu" required></div>
            <div class="account-field"><label for="password">Temporary Password *</label><input type="password" id="password" minlength="10" autocomplete="new-password" required><small>Use at least 10 characters.</small></div>
            <div class="account-field"><label for="confirmPassword">Confirm Password *</label><input type="password" id="confirmPassword" minlength="10" autocomplete="new-password" required></div>
            <div class="account-message" id="formMessage" role="status"></div>
            <div class="account-modal-actions"><button class="account-btn secondary" type="button" data-close>Cancel</button><button class="account-btn primary" type="submit" id="saveButton">Create Account</button></div>
        </form>
    </section>
</div>
<div class="account-toast" id="toast" role="status" aria-live="polite"></div>

<?php require __DIR__ . '/../includes/profile_modal.php'; ?>
<script src="../assets/js/profile-modal.js" defer></script>
<script>
(() => {
    'use strict';
    const API = '../../backend/routes/registrar_accounts.php';
    const el = id => document.getElementById(id);
    let registrars = [];

    const escapeHTML = value => String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[ch]);

    async function request(options = {}) {
        const response = await fetch(API, {
            credentials: 'same-origin',
            ...options,
            headers: { 'Accept': 'application/json', ...(options.headers || {}) }
        });
        let result;
        try { result = await response.json(); }
        catch (_) { throw new Error('The server did not return valid JSON. Check the API file and PHP error log.'); }
        if (!response.ok || result.success === false) throw new Error(result.message || `Request failed (${response.status}).`);
        return result;
    }

    function toast(message) {
        const node = el('toast');
        node.textContent = message;
        node.classList.add('show');
        window.setTimeout(() => node.classList.remove('show'), 3000);
    }

    function pageMessage(message, isError = true) {
        const box = el('pageMessage');
        box.textContent = message;
        box.classList.add('show');
        box.classList.toggle('success', !isError);
    }

    function render() {
        const q = el('searchInput').value.trim().toLowerCase();
        const rows = registrars.filter(row => `${row.full_name || ''} ${row.email || ''} ${row.user_id || ''}`.toLowerCase().includes(q)).sort((a, b) => {
            const parts = value => {
                const p = String(value || '').trim().replace(/\s+/g, ' ').split(' ').filter(Boolean);
                return p.length > 1 ? [p[p.length - 1].toLowerCase(), p.slice(0, -1).join(' ').toLowerCase()] : [(p[0] || 'zzzz').toLowerCase(), ''];
            };
            const pa = parts(a.full_name), pb = parts(b.full_name);
            return pa[0].localeCompare(pb[0], 'en', { sensitivity: 'base' }) || pa[1].localeCompare(pb[1], 'en', { sensitivity: 'base' });
        });
        const active = registrars.filter(row => String(row.account_status).toLowerCase() === 'active').length;
        el('totalCount').textContent = registrars.length;
        el('activeCount').textContent = active;
        el('inactiveCount').textContent = registrars.length - active;
        el('recordCount').textContent = `${rows.length} of ${registrars.length} registrar accounts`;

        const groups = [
            { key: 'active', title: 'Active Registrar Accounts', number: 1 },
            { key: 'inactive', title: 'Inactive / Suspended Registrar Accounts', number: 2 }
        ];
        const heading = `<thead><tr><th>#</th><th>ACCOUNT ID</th><th>REGISTRAR NAME (LAST, FIRST)</th><th>EMAIL ADDRESS</th><th>STATUS</th><th>LAST LOGIN</th><th>DATE CREATED</th><th>ACTION</th></tr></thead>`;
        el('registrarAccountFolders').innerHTML = groups.map(group => {
            const groupRows = rows.filter(row => {
                const status = String(row.account_status || 'inactive').toLowerCase();
                return group.key === 'active' ? status === 'active' : status !== 'active';
            });
            const body = groupRows.length ? groupRows.map((row, index) => {
                const status = String(row.account_status || 'inactive').toLowerCase();
                const action = status === 'active' ? 'Deactivate' : 'Activate';
                const parts = String(row.full_name || '').trim().replace(/\s+/g, ' ').split(' ').filter(Boolean);
                const displayName = parts.length > 1 ? `${parts.pop()}, ${parts.join(' ')}` : (parts[0] || '—');
                return `<tr>
                    <td>${index + 1}</td>
                    <td>#${Number(row.user_id)}</td>
                    <td><strong>${escapeHTML(displayName)}</strong></td>
                    <td>${escapeHTML(row.email)}</td>
                    <td><span class="account-status ${escapeHTML(status)}">${escapeHTML(status)}</span></td>
                    <td>${escapeHTML(row.last_login || 'Never')}</td>
                    <td>${escapeHTML(row.created_at || '—')}</td>
                    <td><button type="button" class="account-row-action ${status === 'active' ? 'deactivate' : 'activate'}" data-status-id="${Number(row.user_id)}">${action}</button></td>
                </tr>`;
            }).join('') : `<tr><td colspan="8" class="account-empty">No accounts in this folder.</td></tr>`;
            return `<details class="account-folder">
                <summary><span class="folder-number">${group.number}</span><strong>${group.title}</strong><small>${groupRows.length} account(s)</small><span class="folder-chevron">⌄</span></summary>
                <div class="account-folder-content"><div class="account-folder-table-wrap"><table class="account-folder-table">${heading}<tbody>${body}</tbody></table></div></div>
            </details>`;
        }).join('');
    }

    async function load() {
        el('registrarAccountFolders').innerHTML = '<div class="account-folder-empty">Loading registrar accounts…</div>';
        try {
            const result = await request();
            registrars = Array.isArray(result.registrars) ? result.registrars : [];
            el('pageMessage').classList.remove('show');
            render();
        } catch (error) {
            registrars = [];
            el('registrarAccountFolders').innerHTML = `<div class="account-folder-empty">${escapeHTML(error.message)}</div>`;
            el('recordCount').textContent = 'Unable to load accounts';
            pageMessage(error.message, true);
            el('totalCount').textContent = '—';
            el('activeCount').textContent = '—';
            el('inactiveCount').textContent = '—';
        }
    }

    function openForm() {
        el('registrarForm').reset();
        el('formMessage').textContent = '';
        el('formMessage').classList.remove('show', 'success');
        el('formOverlay').hidden = false;
        el('fullName').focus();
    }
    function closeForm() { el('formOverlay').hidden = true; }

    el('addButton').addEventListener('click', openForm);
    el('refreshButton').addEventListener('click', load);
    el('searchInput').addEventListener('input', render);
    document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', closeForm));
    el('formOverlay').addEventListener('click', event => { if (event.target === el('formOverlay')) closeForm(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeForm(); });
    el('registrarAccountFolders').addEventListener('click', async event => {
        const button = event.target.closest('[data-status-id]');
        if (!button) return;
        const account = registrars.find(item => Number(item.user_id) === Number(button.dataset.statusId));
        if (!account) return;
        const newStatus = String(account.account_status).toLowerCase() === 'active' ? 'inactive' : 'active';
        if (!window.confirm(`Change ${account.full_name}'s account status to ${newStatus}?`)) return;
        button.disabled = true;
        try {
            const result = await request({
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_status', user_id: Number(account.user_id), account_status: newStatus })
            });
            toast(result.message || 'Account status updated.');
            await load();
        } catch (error) { toast(error.message); button.disabled = false; }
    });
    el('registrarForm').addEventListener('submit', async event => {
        event.preventDefault();
        const fullName = el('fullName').value.trim();
        const email = el('email').value.trim().toLowerCase();
        const password = el('password').value;
        const confirmPassword = el('confirmPassword').value;
        const formMessage = el('formMessage');
        formMessage.classList.remove('success');
        formMessage.classList.add('show');
        if (password.length < 10) { formMessage.textContent = 'Password must contain at least 10 characters.'; return; }
        if (password !== confirmPassword) { formMessage.textContent = 'Passwords do not match.'; return; }
        const save = el('saveButton');
        save.disabled = true;
        save.textContent = 'Creating account…';
        try {
            const result = await request({
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', full_name: fullName, email, password })
            });
            closeForm();
            toast(result.message || 'Registrar account created successfully.');
            await load();
        } catch (error) {
            formMessage.textContent = error.message;
        } finally {
            save.disabled = false;
            save.textContent = 'Create Account';
        }
    });

    const menuToggle = el('menuToggle');
    if (menuToggle) menuToggle.addEventListener('click', () => el('sidebar').classList.toggle('open'));
    load();
})();
</script>
</body>
</html>
