<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

$adminName = htmlspecialchars(
    $_SESSION['full_name'] ?? 'Administrator',
    ENT_QUOTES,
    'UTF-8'
);
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

<style>
:root {
    --navy: #102a56;
    --blue: #2467b7;
    --ink: #17263d;
    --muted: #718096;
    --line: #e7edf5;
    --paper: #f5f7fb;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--paper);
    color: var(--ink);
    font-family: Inter, sans-serif;
    font-size: 14px;
}

button, input {
    font: inherit;
}

.topbar {
    height: 70px;
    background: white;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 35px;
}

.brand {
    color: var(--navy);
    font: 800 17px Manrope, sans-serif;
    letter-spacing: 1px;
}

.topbar-user {
    color: #526780;
    font-size: 12px;
}

.page {
    max-width: 1450px;
    margin: auto;
    padding: 35px;
}

.back-link {
    display: inline-block;
    margin-bottom: 25px;
    color: #2867aa;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.back-link:hover {
    text-decoration: underline;
}

.heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 25px;
}

.eyebrow {
    font-size: 9px;
    color: #7290b4;
    letter-spacing: 1.5px;
    font-weight: 700;
    margin: 0 0 8px;
}

h1 {
    font: 800 28px Manrope, sans-serif;
    margin: 0;
    color: #172d4d;
}

.subtitle {
    color: var(--muted);
    font-size: 12px;
    margin-top: 8px;
}

.btn {
    border: 0;
    border-radius: 8px;
    padding: 11px 15px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.primary {
    background: var(--blue);
    color: white;
}

.light {
    background: #edf3fa;
    color: #315476;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.summary-card {
    padding: 19px;
    background: white;
    border: 1px solid var(--line);
    border-radius: 12px;
}

.summary-card span {
    display: block;
    color: #8190a4;
    font-size: 10px;
    margin-bottom: 12px;
}

.summary-card strong {
    font: 800 27px Manrope, sans-serif;
    color: #1c3a64;
}

.panel {
    background: white;
    border: 1px solid var(--line);
    border-radius: 13px;
    box-shadow: 0 10px 30px rgba(16,42,86,.04);
    overflow: hidden;
}

.panel-heading {
    padding: 21px 23px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    border-bottom: 1px solid var(--line);
}

.panel-heading h2 {
    font: 800 17px Manrope, sans-serif;
    margin: 0;
}

.toolbar {
    display: flex;
    gap: 10px;
    padding: 17px 22px;
    flex-wrap: wrap;
}

.search {
    height: 39px;
    flex: 1;
    min-width: 220px;
    max-width: 420px;
    border: 1px solid #dfe7f0;
    border-radius: 8px;
    padding: 0 12px;
    outline: none;
}

.search:focus {
    border-color: var(--blue);
}

.table-meta {
    padding: 0 22px 12px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 10px;
    color: #8998aa;
}

.table-wrap {
    overflow-x: auto;
    border-top: 1px solid var(--line);
}

table {
    width: 100%;
    min-width: 760px;
    border-collapse: collapse;
    font-size: 11px;
}

th {
    background: #f5f8fc;
    text-align: left;
    padding: 14px;
    color: #60748d;
    font-size: 9px;
    text-transform: uppercase;
    white-space: nowrap;
}

td {
    padding: 14px;
    border-top: 1px solid #edf1f6;
    color: #51647d;
}

tbody tr:hover {
    background: #f8fbff;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
}

.badge.active {
    background: #e7f8ee;
    color: #21845d;
}

.badge.inactive {
    background: #fff5df;
    color: #98702c;
}

.badge.suspended {
    background: #fff0ee;
    color: #b45244;
}

.actions {
    display: flex;
    gap: 7px;
    white-space: nowrap;
}

.action {
    border: 0;
    border-radius: 6px;
    padding: 7px 10px;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
}

.activate {
    background: #eaf8f0;
    color: #21845d;
}

.deactivate {
    background: #fff2ed;
    color: #b35a40;
}

.empty {
    padding: 40px;
    text-align: center;
    color: #94a1b1;
}

.footer {
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    color: #91a0b1;
    font-size: 9px;
}

.overlay {
    position: fixed;
    inset: 0;
    background: rgba(12,30,56,.55);
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-y: auto;
}

.overlay[hidden] {
    display: none !important;
}

.modal {
    width: 100%;
    max-width: 510px;
    background: white;
    border-radius: 14px;
    padding: 25px;
    box-shadow: 0 20px 60px #102a5630;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.modal-header h2 {
    margin: 0;
    font: 800 19px Manrope, sans-serif;
    color: #1c3455;
}

.close-modal {
    border: 0;
    width: 32px;
    height: 32px;
    border-radius: 7px;
    background: #f0f3f7;
    font-size: 20px;
    color: #60748d;
    cursor: pointer;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field label {
    color: #40536d;
    font-size: 11px;
    font-weight: 700;
}

.field input {
    height: 43px;
    width: 100%;
    padding: 0 11px;
    background: #fbfcfe;
    border: 1px solid #dce5ef;
    border-radius: 8px;
    outline: none;
    font-size: 12px;
    color: #253c59;
}

.field input:focus {
    border-color: var(--blue);
    background: white;
    box-shadow: 0 0 0 3px rgba(53,122,192,.10);
}

.field small {
    color: #8492a4;
    font-size: 10px;
    line-height: 1.5;
}

.message {
    margin-top: 15px;
    padding: 12px;
    border-radius: 8px;
    color: #a33d3d;
    background: #fff1f1;
    font-size: 11px;
    line-height: 1.5;
}

.message.success {
    color: #21845d;
    background: #eaf8f0;
}

.message[hidden] {
    display: none !important;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding-top: 20px;
    margin-top: 22px;
    border-top: 1px solid var(--line);
}

.toast {
    position: fixed;
    right: 25px;
    bottom: 25px;
    background: #173b6a;
    color: white;
    padding: 13px 17px;
    border-radius: 9px;
    font-size: 11px;
    box-shadow: 0 12px 30px #102a5630;
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition: .25s;
    z-index: 100;
}

.toast.show {
    opacity: 1;
    transform: translateY(0);
}

@media (max-width: 700px) {
    .page {
        padding: 20px 13px;
    }

    .topbar {
        padding: 0 15px;
    }

    .heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .cards {
        grid-template-columns: 1fr 1fr;
    }

    .toolbar {
        padding: 13px;
    }

    .search {
        width: 100%;
        max-width: none;
    }

    .modal {
        padding: 18px;
    }
}
</style>
</head>

<body>

<header class="topbar">
    <div class="brand">SKWELA COLLEGE PORTAL</div>
    <div class="topbar-user">Administrator: <?= $adminName ?></div>
</header>

<main class="page">

    <a href="dashboard.php" style="display:inline-block;margin-bottom:25px;color:#2867aa;text-decoration:none;font-size:12px;font-weight:600;">
        ← Back to Admin Dashboard
    </a>

    <section class="heading">

        <div>
            <p class="eyebrow">SYSTEM USER MANAGEMENT</p>
            <h1>Registrar Accounts</h1>
            <p class="subtitle">
                Create and manage authorized registrar accounts.
            </p>
        </div>

        <button type="button" class="btn primary" id="addButton">
            + Create Registrar Account
        </button>

    </section>

    <section class="cards">

        <div class="summary-card">
            <span>TOTAL REGISTRAR ACCOUNTS</span>
            <strong id="totalCount">—</strong>
        </div>

        <div class="summary-card">
            <span>ACTIVE ACCOUNTS</span>
            <strong id="activeCount">—</strong>
        </div>

        <div class="summary-card">
            <span>INACTIVE / SUSPENDED</span>
            <strong id="inactiveCount">—</strong>
        </div>

    </section>

    <section class="panel">

        <div class="panel-heading">

            <div>
                <p class="eyebrow">DATABASE RECORDS</p>
                <h2>Registrar Account List</h2>
            </div>

        </div>

        <div class="toolbar">

            <input
                class="search"
                type="search"
                id="searchInput"
                placeholder="Search registrar name or email..."
            >

            <button type="button" class="btn light" id="refreshButton">
                ↻ Refresh
            </button>

        </div>

        <div class="table-meta">
            <span id="recordCount">Loading accounts...</span>
            <span>Passwords are never displayed.</span>
        </div>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody id="tableBody">
                    <tr>
                        <td colspan="7" class="empty">Loading accounts...</td>
                    </tr>
                </tbody>

            </table>

        </div>

        <div class="footer">
            <span>SKWELA COLLEGE ENROLLMENT SYSTEM</span>
            <span>Registrar Account Management</span>
        </div>

    </section>

</main>

<div class="overlay" id="formOverlay" hidden>

    <div class="modal">

        <div class="modal-header">

            <div>
                <p class="eyebrow">NEW REGISTRAR ACCOUNT</p>
                <h2>Create Registrar Account</h2>
            </div>

            <button type="button" class="close-modal" data-close>
                ×
            </button>

        </div>

        <form id="registrarForm">

            <div class="form-grid">

                <div class="field">
                    <label for="fullName">Full Name *</label>

                    <input
                        type="text"
                        id="fullName"
                        maxlength="150"
                        placeholder="Enter registrar's full name"
                        required
                    >
                </div>

                <div class="field">
                    <label for="email">Email Address *</label>

                    <input
                        type="email"
                        id="email"
                        maxlength="150"
                        placeholder="registrar@college.edu"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Temporary Password *</label>

                    <input
                        type="password"
                        id="password"
                        minlength="10"
                        autocomplete="new-password"
                        placeholder="Enter a strong temporary password"
                        required
                    >

                    <small>
                        Use at least 10 characters. Never use the Admin password.
                    </small>
                </div>

                <div class="field">
                    <label for="confirmPassword">Confirm Password *</label>

                    <input
                        type="password"
                        id="confirmPassword"
                        minlength="10"
                        autocomplete="new-password"
                        placeholder="Re-enter the temporary password"
                        required
                    >
                </div>

            </div>

            <div class="message" id="formMessage" hidden></div>

            <div class="modal-actions">

                <button type="button" class="btn light" data-close>
                    Cancel
                </button>

                <button type="submit" class="btn primary" id="saveButton">
                    Create Account
                </button>

            </div>

        </form>

    </div>

</div>

<div class="toast" id="toast"></div>

<script>
const API = "../../backend/routes/registrars.php";

let registrars = [];
let filteredRegistrars = [];

const el = id => document.getElementById(id);

const escapeHTML = value =>
    String(value ?? "").replace(/[&<>"']/g, character => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[character]);

async function apiRequest(options = {}) {

    const response = await fetch(API, {
        credentials: "same-origin",
        ...options,
        headers: {
            "Accept": "application/json",
            ...(options.headers || {})
        }
    });

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("The server did not return valid JSON.");
    }

    if (!response.ok || result.success === false) {
        throw new Error(result.message || "Request failed.");
    }

    return result;
}

async function loadRegistrars() {

    el("tableBody").innerHTML = `
        <tr><td colspan="7" class="empty">Loading accounts...</td></tr>
    `;

    try {

        const result = await apiRequest();

        registrars = result.registrars || [];

        updateSummary();
        renderTable();

    } catch (error) {

        registrars = [];

        el("tableBody").innerHTML = `
            <tr>
                <td colspan="7" class="empty">
                    ${escapeHTML(error.message)}
                </td>
            </tr>
        `;

        el("recordCount").textContent = "Unable to load accounts";

        showToast(error.message);
    }
}

function updateSummary() {

    const active = registrars.filter(item =>
        item.account_status === "active"
    ).length;

    el("totalCount").textContent = registrars.length;
    el("activeCount").textContent = active;
    el("inactiveCount").textContent = registrars.length - active;
}

function renderTable() {

    const query = el("searchInput").value.trim().toLowerCase();

    filteredRegistrars = registrars.filter(account => {

        const text = (
            String(account.full_name || "") + " " +
            String(account.email || "") + " " +
            String(account.user_id || "")
        ).toLowerCase();

        return text.includes(query);
    });

    el("recordCount").textContent =
        `${filteredRegistrars.length} of ${registrars.length} accounts`;

    if (!filteredRegistrars.length) {

        el("tableBody").innerHTML = `
            <tr>
                <td colspan="7" class="empty">
                    No registrar accounts found.
                </td>
            </tr>
        `;

        return;
    }

    el("tableBody").innerHTML = filteredRegistrars.map(account => {

        const status = String(account.account_status || "").toLowerCase();

        return `
            <tr>

                <td>#${Number(account.user_id)}</td>

                <td><strong>${escapeHTML(account.full_name)}</strong></td>

                <td>${escapeHTML(account.email)}</td>

                <td>
                    <span class="badge ${escapeHTML(status)}">
                        ${escapeHTML(status)}
                    </span>
                </td>

                <td>${escapeHTML(account.last_login || "Never")}</td>

                <td>${escapeHTML(account.created_at || "—")}</td>

                <td>
                    <div class="actions">

                        <button
                            type="button"
                            class="action ${status === "active" ? "deactivate" : "activate"}"
                            data-user-id="${Number(account.user_id)}"
                            data-action="status"
                        >
                            ${status === "active" ? "Deactivate" : "Activate"}
                        </button>

                    </div>
                </td>

            </tr>
        `;

    }).join("");
}

function openForm() {

    el("registrarForm").reset();
    el("formMessage").hidden = true;
    el("formMessage").classList.remove("success");

    el("formOverlay").hidden = false;

    document.body.classList.add("modal-open");
}

function closeForm() {

    el("formOverlay").hidden = true;

    document.body.classList.remove("modal-open");
}

function showToast(message) {

    const toast = el("toast");

    toast.textContent = message;
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 2800);
}

function showFormMessage(message, success = false) {

    const box = el("formMessage");

    box.textContent = message;
    box.hidden = false;

    box.classList.toggle("success", success);
}

async function createRegistrar(event) {

    event.preventDefault();

    const fullName = el("fullName").value.trim();
    const email = el("email").value.trim().toLowerCase();
    const password = el("password").value;
    const confirmPassword = el("confirmPassword").value;

    if (password.length < 10) {
        showFormMessage("Password must contain at least 10 characters.");
        return;
    }

    if (password !== confirmPassword) {
        showFormMessage("Passwords do not match.");
        return;
    }

    const button = el("saveButton");

    button.disabled = true;
    button.textContent = "Creating account...";

    try {

        const result = await apiRequest({
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "add",
                full_name: fullName,
                email: email,
                password: password
            })
        });

        closeForm();

        showToast(
            `${result.message} Registrar ID: ${result.registrar_id}`
        );

        await loadRegistrars();

    } catch (error) {

        showFormMessage(error.message);

    } finally {

        button.disabled = false;
        button.textContent = "Create Account";

    }
}

async function updateRegistrarStatus(account) {

    const currentStatus = account.account_status;

    const newStatus = currentStatus === "active"
        ? "inactive"
        : "active";

    if (!window.confirm(
        `Change ${account.full_name}'s account status to ${newStatus}?`
    )) return;

    try {

        const result = await apiRequest({
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "update_status",
                user_id: Number(account.user_id),
                account_status: newStatus
            })
        });

        showToast(result.message);

        await loadRegistrars();

    } catch (error) {

        showToast(error.message);

    }
}

el("addButton").addEventListener("click", openForm);

el("registrarForm").addEventListener("submit", createRegistrar);

el("refreshButton").addEventListener("click", loadRegistrars);

el("searchInput").addEventListener("input", renderTable);

el("tableBody").addEventListener("click", event => {

    const button = event.target.closest("[data-action='status']");

    if (!button) return;

    const id = Number(button.dataset.userId);

    const account = registrars.find(item =>
        Number(item.user_id) === id
    );

    if (!account) {
        showToast("Account not found.");
        return;
    }

    updateRegistrarStatus(account);
});

document.querySelectorAll("[data-close]").forEach(button => {

    button.addEventListener("click", closeForm);

});

el("formOverlay").addEventListener("click", event => {

    if (event.target === el("formOverlay")) {
        closeForm();
    }

});

document.addEventListener("keydown", event => {

    if (event.key === "Escape") {
        closeForm();
    }

});

loadRegistrars();
</script>

</body>
</html>