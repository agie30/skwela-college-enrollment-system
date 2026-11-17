<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}
require_once __DIR__ . '/../includes/profile_bootstrap.php';
$activeAdminPage = 'academic_years';
$adminName = htmlspecialchars($sharedProfileName, ENT_QUOTES, 'UTF-8');
$adminInitial = htmlspecialchars($sharedProfileInitials, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Academic Years | Skwela College Enrollment System</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=20261117-final">
<link rel="stylesheet" href="../assets/css/profile-modal.css?v=20261117-final">
<link rel="stylesheet" href="../assets/css/sidebar-unified.css?v=20261117-final">

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

button, input, select {
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
    grid-template-columns: repeat(4, 1fr);
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

.search:focus, .filter:focus, .field input:focus {
    border-color: var(--blue);
}

.filter {
    height: 39px;
    min-width: 145px;
    border: 1px solid #dfe7f0;
    border-radius: 8px;
    padding: 0 10px;
    color: #536780;
    background: white;
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
    min-width: 850px;
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

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
}

.status.open {
    background: #e7f8ee;
    color: #21845d;
}

.status.closed {
    background: #fff0ee;
    color: #b45244;
}

.status.upcoming {
    background: #fff5df;
    color: #98702c;
}

.actions {
    display: flex;
    gap: 6px;
    white-space: nowrap;
}

.action {
    border: 0;
    border-radius: 6px;
    padding: 7px 9px;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
}

.view {
    background: #edf5ff;
    color: #2867aa;
}

.edit {
    background: #f3efff;
    color: #8262bd;
}

.change {
    background: #eaf8f0;
    color: #21845d;
}

.close {
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
    grid-template-columns: 1fr 1fr;
    gap: 17px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.full {
    grid-column: 1 / -1;
}

.field label {
    color: #40536d;
    font-size: 11px;
    font-weight: 700;
}

.field input, .field select {
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

.message {
    margin-top: 15px;
    padding: 12px;
    border-radius: 8px;
    color: #a33d3d;
    background: #fff1f1;
    font-size: 11px;
    line-height: 1.5;
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

.details {
    display: grid;
}

.details-row {
    display: grid;
    grid-template-columns: 140px 1fr;
    gap: 12px;
    padding: 13px 3px;
    border-bottom: 1px solid #edf1f6;
    font-size: 11px;
}

.details-row span {
    color: #8291a4;
}

.details-row strong {
    color: #2c405c;
    overflow-wrap: anywhere;
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

.error {
    color: #a33d3d;
    background: #fff1f1;
    padding: 15px;
    margin: 20px;
    border-radius: 8px;
    font-size: 11px;
}

body.modal-open {
    overflow: hidden;
}

@media (max-width: 980px) {
    .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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

    .panel-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .toolbar {
        padding: 13px;
    }

    .search, .filter {
        width: 100%;
        max-width: none;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .modal {
        padding: 18px;
    }
}
.app-shell { min-height:100vh; }
.main-area { margin-left:255px; min-width:0; }
.topbar { position:sticky; top:0; z-index:10; height:72px; padding:0 32px; }
.page-content { max-width:1600px; margin:0 auto; padding:0 24px 28px; }
.page-content .page { max-width:1450px; margin:0 auto; padding:30px 14px; }
@media(max-width:1100px) { .main-area { margin-left:220px; } .topbar { padding:0 24px; } }
@media(max-width:700px) { .main-area { margin-left:0; } .page-content { padding:0 12px 20px; } .page-content .page { padding:22px 2px; } .topbar { padding:0 16px; } }

/* Simple current-year panel: keep the existing Skwela visual language. */
.current-year-banner {
    display:flex; align-items:center; justify-content:space-between; gap:20px;
    padding:20px 22px; margin:0 0 18px; border:1px solid #dce8f7;
    border-radius:14px; background:linear-gradient(110deg,#ffffff 0%,#f2f7ff 100%);
    box-shadow:0 5px 18px rgba(16,42,86,.035);
}
.current-year-copy { display:flex; flex-direction:column; gap:6px; min-width:0; }
.current-year-label { color:#6e89ab; font-size:9px; font-weight:800; letter-spacing:1.25px; }
.current-year-name { color:#17365f; font:800 26px Manrope,Inter,sans-serif; line-height:1.15; }
.current-year-dates { color:#718096; font-size:12px; }
.current-year-side { text-align:right; max-width:300px; }
.current-year-side p { margin:8px 0 0; color:#718096; font-size:11px; line-height:1.5; }
.year-status-pill { display:inline-flex; align-items:center; padding:7px 11px; border-radius:30px; font-size:10px; font-weight:800; }
.year-status-pill.open { color:#147544; background:#e3f7ec; }
.year-status-pill.upcoming { color:#8a5b06; background:#fff4da; }
.year-status-pill.closed { color:#596579; background:#edf2f7; }
.academic-help { margin:0 22px 15px; padding:10px 12px; border:1px solid #e4ebf4; border-radius:9px; background:#f8fbff; color:#718096; font-size:10px; line-height:1.5; }
.academic-help strong { color:#31445e; }
.table-wrap table { min-width:760px; }
.table-wrap th:first-child, .table-wrap td:first-child { width:44px; color:#8493a7; font-weight:700; }
.table-wrap td strong { color:#203b60; }
 .field-hint { display:block; margin-top:7px; color:#8291a5; font-size:10px; line-height:1.45; }
@media(max-width:700px) {
    .current-year-banner { align-items:flex-start; flex-direction:column; padding:17px; }
    .current-year-side { text-align:left; max-width:none; }
    .current-year-name { font-size:23px; }
    .academic-help { margin-left:13px; margin-right:13px; }
}

/* Academic years grouped into simple, numbered folders. */
.year-folder-list {
    display: grid;
    gap: 11px;
    padding: 0 22px 20px;
}
.year-folder {
    border: 1px solid #e1eaf5;
    border-radius: 11px;
    background: #fff;
    overflow: hidden;
}
.year-folder > summary {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 58px;
    padding: 13px 15px;
    cursor: pointer;
    list-style: none;
    user-select: none;
    background: #fbfdff;
}
.year-folder > summary::-webkit-details-marker { display: none; }
.year-folder > summary:hover { background: #f4f8fd; }
.folder-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 28px;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #edf4fd;
    color: #245b9c;
    font-size: 11px;
    font-weight: 800;
}
.folder-icon { color: #3979bd; font-size: 17px; line-height: 1; }
.folder-title { color: #203b60; font-size: 12px; font-weight: 800; }
.folder-count {
    margin-left: auto;
    padding: 5px 9px;
    border-radius: 30px;
    background: #eef3f8;
    color: #63758c;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}
.folder-chevron { color: #55708e; font-size: 15px; transition: transform .18s ease; }
.year-folder[open] > summary { border-bottom: 1px solid #e8eef6; background: #f7faff; }
.year-folder[open] .folder-chevron { transform: rotate(180deg); }
.folder-records { padding: 10px 12px 12px; }
.year-record {
    display: grid;
    grid-template-columns: 42px minmax(150px,1.25fr) minmax(125px,1fr) minmax(125px,1fr) 100px minmax(190px,auto);
    align-items: center;
    gap: 12px;
    padding: 13px 10px;
    border-bottom: 1px solid #edf1f6;
    color: #526780;
    font-size: 11px;
}
.year-record:last-child { border-bottom: 0; }
.year-record:hover { background: #f9fbfe; }
.year-record-number { color: #8191a5; font-weight: 800; }
.year-record-name { color: #203b60; font-weight: 800; }
.year-record-date { color: #64758a; }
.year-folder-empty { padding: 18px 12px; color: #8a98aa; font-size: 11px; text-align: center; }
.year-folder .status { justify-self: start; }
@media(max-width:1050px) {
    .year-record { grid-template-columns: 32px minmax(125px,1fr) minmax(110px,1fr) minmax(110px,1fr) 85px; }
    .year-record .actions { grid-column: 2 / -1; padding-top: 3px; }
}
@media(max-width:700px) {
    .year-folder-list { padding: 0 13px 15px; }
    .year-folder > summary { gap: 8px; padding: 12px 10px; }
    .folder-title { font-size: 11px; }
    .year-record { grid-template-columns: 28px 1fr; gap: 7px 10px; padding: 13px 7px; }
    .year-record > *:not(.year-record-number):not(.year-record-name):not(.actions) { grid-column: 2; }
    .year-record .actions { grid-column: 2; flex-wrap: wrap; }
}
</style>
</head>

<body>
<div class="app-shell">
<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
<main class="main-area">
<header class="topbar">
    <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle navigation">☰</button>
    <div class="breadcrumb"><span>Workspace</span><b>/</b><strong>Academic Years</strong></div>
    <button type="button" class="shared-profile-trigger" onclick="openSharedProfile()" aria-label="Open administrator profile">
        <?php if ($sharedProfileImageUrl !== ''): ?>
            <img class="shared-profile-avatar" src="<?= htmlspecialchars($sharedProfileImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Administrator profile picture">
        <?php else: ?>
            <span class="shared-profile-avatar"><?= $adminInitial ?></span>
        <?php endif; ?>
        <span class="shared-profile-trigger-name"><?= $adminName ?></span>
    </button>
</header>
<div class="page-content">
<div class="page">

    <a href="dashboard.php" class="back-link">
        ← Back
    </a>

    <section class="heading">
        <div>
            <p class="eyebrow">ACADEMIC SETUP</p>
            <h1>Academic Years</h1>
            <p class="subtitle">
                Set the school year that students can enroll in. Keep one year open at a time.
            </p>
        </div>

        <button type="button" class="btn primary" id="addButton">
            + Add Academic Year
        </button>
    </section>

    <section class="current-year-banner" id="currentYearBanner" aria-live="polite">
        <div class="current-year-copy">
            <span class="current-year-label" id="currentYearLabel">CURRENT ACADEMIC YEAR</span>
            <strong class="current-year-name" id="currentYearName">Loading…</strong>
            <span class="current-year-dates" id="currentYearDates">Checking academic year records</span>
        </div>
        <div class="current-year-side">
            <span class="year-status-pill upcoming" id="currentYearStatus">Checking status</span>
            <p id="currentYearHint">The open academic year is used by the enrollment process.</p>
        </div>
    </section>

    <section class="cards">
        <div class="summary-card">
            <span>TOTAL ACADEMIC YEARS</span>
            <strong id="totalCount">—</strong>
        </div>

        <div class="summary-card">
            <span>OPEN ACADEMIC YEARS</span>
            <strong id="openCount">—</strong>
        </div>

        <div class="summary-card">
            <span>UPCOMING ACADEMIC YEARS</span>
            <strong id="upcomingCount">—</strong>
        </div>

        <div class="summary-card">
            <span>CLOSED ACADEMIC YEARS</span>
            <strong id="closedCount">—</strong>
        </div>
    </section>

    <section class="panel">

        <div class="panel-heading">
            <div>
                <p class="eyebrow">DATABASE RECORDS</p>
                <h2>School Year Records</h2>
            </div>
        </div>

        <div class="toolbar">

            <input
                class="search"
                type="search"
                id="searchInput"
                placeholder="Search academic year..."
            >

            <select class="filter" id="statusFilter">
                <option value="all">All Statuses</option>
                <option value="open">Open</option>
                <option value="upcoming">Upcoming</option>
                <option value="closed">Closed</option>
            </select>

            <button type="button" class="btn light" id="refreshButton">
                ↻ Refresh
            </button>

            <button type="button" class="btn light" id="exportButton">
                ↓ Export CSV
            </button>

        </div>

        <div class="academic-help"><strong>How to use:</strong> Open a folder to view its numbered school-year records. Use <strong>Open</strong> only when enrollment should be available. Only one academic year can be open at a time. Closed years remain saved for history.</div>

        <div class="table-meta">
            <span id="recordCount">Loading records...</span>
            <span>Newest school year first</span>
        </div>

        <div class="year-folder-list" id="tableBody" aria-live="polite">
            <div class="empty">Loading academic years...</div>
        </div>

        <div class="footer">
            <span>SKWELA COLLEGE ENROLLMENT SYSTEM</span>
            <span>Academic Years Management</span>
        </div>

    </section>

</div>
</div>
</main>
</div>

<!-- ADD / EDIT MODAL -->

<div class="overlay" id="formOverlay" hidden>

    <div class="modal">

        <div class="modal-header">

            <div>
                <p class="eyebrow">ACADEMIC YEAR SETTINGS</p>
                <h2 id="formTitle">Add Academic Year</h2>
            </div>

            <button type="button" class="close-modal" data-close>
                ×
            </button>

        </div>

        <form id="yearForm">

            <input type="hidden" id="academicYearId">

            <div class="form-grid">

                <div class="field">
                    <label for="startDate">Start Date *</label>
                    <input type="date" id="startDate" required>
                </div>

                <div class="field">
                    <label for="endDate">End Date *</label>
                    <input type="date" id="endDate" required>
                </div>

                <div class="field full">
                    <small class="field-hint">The academic year name is generated automatically from the selected dates (example: 2026–2027).</small>
                </div>

                <div class="field full">
                    <label for="yearStatus">Status *</label>

                    <select id="yearStatus" required>
                        <option value="upcoming">Upcoming</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                    <small class="field-hint">Only one academic year can be Open at a time. Use Upcoming for a future school year.</small>
                </div>

            </div>

            <div class="message" id="formMessage" hidden></div>

            <div class="modal-actions">

                <button type="button" class="btn light" data-close>
                    Cancel
                </button>

                <button type="submit" class="btn primary" id="saveButton">
                    Save Academic Year
                </button>

            </div>

        </form>

    </div>

</div>

<!-- DETAILS MODAL -->

<div class="overlay" id="detailsOverlay" hidden>

    <div class="modal">

        <div class="modal-header">

            <div>
                <p class="eyebrow">RECORD INFORMATION</p>
                <h2>Academic Year Details</h2>
            </div>

            <button type="button" class="close-modal" data-close>
                ×
            </button>

        </div>

        <div class="details" id="detailsContent"></div>

        <div class="modal-actions">
            <button type="button" class="btn light" data-close>
                Close
            </button>
        </div>

    </div>

</div>

<div class="toast" id="toast"></div>
<?php require __DIR__ . '/../includes/profile_modal.php'; ?>
<script src="../assets/js/profile-modal.js" defer></script>

<script>
const menuToggle = document.getElementById("menuToggle");
if (menuToggle) menuToggle.addEventListener("click", () => document.getElementById("sidebar").classList.toggle("open"));

const API = "../../backend/routes/academic_years.php";

let academicYears = [];
let filteredYears = [];
let editingId = null;

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
        throw new Error(
            result.message || `Request failed (${response.status}).`
        );
    }

    return result;
}

async function loadAcademicYears() {

    el("tableBody").innerHTML = `<div class="empty">Loading academic years...</div>`;

    try {

        const result = await apiRequest();

        academicYears = result.academic_years || [];

        updateSummary();
        renderTable();

    } catch (error) {

        el("tableBody").innerHTML = `<div class="empty">${escapeHTML(error.message)}</div>`;

        el("recordCount").textContent = "Unable to load records";

        showToast(error.message);
    }
}

function formatAcademicDate(value) {
    if (!value) return "—";
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
    if (Number.isNaN(date.getTime())) return String(value);
    return new Intl.DateTimeFormat("en-PH", {
        month: "short", day: "numeric", year: "numeric"
    }).format(date);
}

function renderCurrentAcademicYear() {
    const openYear = academicYears.find(year => String(year.status).toLowerCase() === "open");
    const nextYear = academicYears
        .filter(year => String(year.status).toLowerCase() === "upcoming")
        .slice()
        .sort((a, b) => String(a.start_date || "").localeCompare(String(b.start_date || "")))[0];
    const latestYear = academicYears.slice().sort((a, b) => String(b.start_date || "").localeCompare(String(a.start_date || "")))[0];
    const displayYear = openYear || nextYear || latestYear || null;

    const name = el("currentYearName");
    const dates = el("currentYearDates");
    const label = el("currentYearLabel");
    const status = el("currentYearStatus");
    const hint = el("currentYearHint");

    if (!displayYear) {
        label.textContent = "CURRENT ACADEMIC YEAR";
        name.textContent = "No academic year available";
        dates.textContent = "Add a school year to begin enrollment setup.";
        status.textContent = "Not set";
        status.className = "year-status-pill closed";
        hint.textContent = "Create an academic year before opening enrollment.";
        return;
    }

    const state = String(displayYear.status || "upcoming").toLowerCase();
    label.textContent = openYear ? "CURRENT ACADEMIC YEAR" : nextYear ? "NEXT ACADEMIC YEAR" : "MOST RECENT ACADEMIC YEAR";
    name.textContent = displayYear.year_name || "Academic Year";
    dates.textContent = `${formatAcademicDate(displayYear.start_date)} – ${formatAcademicDate(displayYear.end_date)}`;
    status.textContent = state === "open" ? "Open for Enrollment" : state === "upcoming" ? "Upcoming" : "Closed";
    status.className = `year-status-pill ${state}`;
    hint.textContent = state === "open"
        ? "This is the school year currently used by student enrollment."
        : state === "upcoming"
            ? "Set this year to Open when you are ready to accept enrollment applications."
            : "This academic year is closed. Create a new upcoming year or reopen it when appropriate.";
}

function updateSummary() {
    el("totalCount").textContent = academicYears.length;
    el("openCount").textContent = academicYears.filter(year => String(year.status).toLowerCase() === "open").length;
    el("upcomingCount").textContent = academicYears.filter(year => String(year.status).toLowerCase() === "upcoming").length;
    el("closedCount").textContent = academicYears.filter(year => String(year.status).toLowerCase() === "closed").length;
    renderCurrentAcademicYear();
}

function academicYearSort(a, b) {
    const byStart = String(b.start_date || '').localeCompare(String(a.start_date || ''));
    if (byStart !== 0) return byStart;
    return String(b.year_name || '').localeCompare(String(a.year_name || ''));
}

function normalizedYearStatus(year) {
    const status = String(year.status || '').trim().toLowerCase();
    return ['open', 'upcoming', 'closed'].includes(status) ? status : 'closed';
}

function applyFilters() {
    const query = el("searchInput").value.trim().toLowerCase();
    const selectedStatus = el("statusFilter").value;

    filteredYears = academicYears.filter(year => {
        const matchesSearch =
            String(year.year_name ?? "").toLowerCase().includes(query) ||
            String(year.start_date ?? "").toLowerCase().includes(query) ||
            String(year.end_date ?? "").toLowerCase().includes(query) ||
            String(year.status ?? "").toLowerCase().includes(query);
        const matchesStatus = selectedStatus === "all" || normalizedYearStatus(year) === selectedStatus;
        return matchesSearch && matchesStatus;
    }).slice().sort(academicYearSort);
}

function renderYearRecord(year, index) {
    const id = Number(year.academic_year_id);
    const status = normalizedYearStatus(year);
    const statusAction = status === "open" ? "closed" : status === "closed" ? "upcoming" : "open";
    const statusLabel = status === "open" ? "Close" : status === "closed" ? "Set Upcoming" : "Open";

    return `
        <div class="year-record">
            <span class="year-record-number">${index + 1}.</span>
            <strong class="year-record-name">${escapeHTML(year.year_name)}</strong>
            <span class="year-record-date">Start: ${escapeHTML(formatAcademicDate(year.start_date))}</span>
            <span class="year-record-date">End: ${escapeHTML(formatAcademicDate(year.end_date))}</span>
            <span class="status ${escapeHTML(status)}">${escapeHTML(status)}</span>
            <div class="actions">
                <button class="action view" type="button" data-action="view" data-id="${id}">View</button>
                <button class="action edit" type="button" data-action="edit" data-id="${id}">Edit</button>
                <button class="action ${status === 'open' ? 'close' : 'change'}" type="button" data-action="status" data-status="${statusAction}" data-id="${id}">${statusLabel}</button>
            </div>
        </div>
    `;
}

function renderTable() {
    applyFilters();
    el("recordCount").textContent = `${filteredYears.length} of ${academicYears.length} records`;

    const groups = [
        { key: 'open', title: 'Open Academic Years', number: 1, icon: '📂', description: 'School years currently available for enrollment.' },
        { key: 'upcoming', title: 'Upcoming Academic Years', number: 2, icon: '📁', description: 'Future school years prepared for the next enrollment period.' },
        { key: 'closed', title: 'Closed Academic Years', number: 3, icon: '📁', description: 'Completed or inactive school years kept for history.' }
    ];

    if (!filteredYears.length) {
        el("tableBody").innerHTML = `<div class="empty">No academic year records match your search or selected status.</div>`;
        return;
    }

    el("tableBody").innerHTML = groups.map(group => {
        const records = filteredYears.filter(year => normalizedYearStatus(year) === group.key);
        const content = records.length
            ? records.map((year, index) => renderYearRecord(year, index)).join('')
            : `<div class="year-folder-empty">No ${group.key} academic year records.</div>`;

        return `
            <details class="year-folder">
                <summary>
                    <span class="folder-number">${group.number}</span>
                    <span class="folder-icon" aria-hidden="true">${group.icon}</span>
                    <span class="folder-title">${group.title}</span>
                    <span class="folder-count">${records.length} ${records.length === 1 ? 'record' : 'records'}</span>
                    <span class="folder-chevron" aria-hidden="true">⌄</span>
                </summary>
                <div class="folder-records">
                    <div class="folder-description" style="padding:2px 10px 9px;color:#8796a9;font-size:10px;">${group.description}</div>
                    ${content}
                </div>
            </details>
        `;
    }).join('');
}

function openForm(year = null) {

    el("yearForm").reset();

    el("formMessage").hidden = true;

    editingId = year ? Number(year.academic_year_id) : null;

    el("formTitle").textContent =
        year ? "Edit Academic Year" : "Add Academic Year";

    el("saveButton").textContent =
        year ? "Save Changes" : "Save Academic Year";

    el("academicYearId").value = year?.academic_year_id ?? "";

    el("startDate").value = year?.start_date ?? "";
    el("endDate").value = year?.end_date ?? "";
    el("yearStatus").value = year?.status ?? "upcoming";

    el("formOverlay").hidden = false;

    document.body.classList.add("modal-open");
}

function closeOverlay(overlay) {

    overlay.hidden = true;

    if (el("formOverlay").hidden && el("detailsOverlay").hidden) {
        document.body.classList.remove("modal-open");
    }
}

function showFormError(message) {

    el("formMessage").textContent = message;
    el("formMessage").hidden = false;
}

async function saveAcademicYear(event) {

    event.preventDefault();

    const payload = {

        action: editingId ? "update" : "add",

        start_date: el("startDate").value,
        end_date: el("endDate").value,
        status: el("yearStatus").value

    };

    if (editingId) {
        payload.academic_year_id = editingId;
    }

    if (!payload.start_date || !payload.end_date) {
        showFormError("Please select both start and end dates.");
        return;
    }

    if (payload.end_date <= payload.start_date) {
        showFormError("End date must be later than start date.");
        return;
    }

    const button = el("saveButton");

    button.disabled = true;
    button.textContent = "Saving...";

    try {

        const result = await apiRequest({
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        closeOverlay(el("formOverlay"));

        showToast(result.message || "Academic year saved successfully.");

        await loadAcademicYears();

    } catch (error) {

        showFormError(error.message);

    } finally {

        button.disabled = false;

        button.textContent =
            editingId ? "Save Changes" : "Save Academic Year";

    }
}

function showDetails(year) {

    const details = [
        ["Academic Year ID", year.academic_year_id],
        ["Academic Year", year.year_name],
        ["Start Date", formatAcademicDate(year.start_date)],
        ["End Date", formatAcademicDate(year.end_date)],
        ["Status", year.status],
        ["Created At", year.created_at]
    ];

    el("detailsContent").innerHTML = details.map(([label, value]) => `
        <div class="details-row">
            <span>${escapeHTML(label)}</span>
            <strong>${escapeHTML(value || "—")}</strong>
        </div>
    `).join("");

    el("detailsOverlay").hidden = false;

    document.body.classList.add("modal-open");
}

async function changeStatus(id, status) {

    const year = academicYears.find(item =>
        Number(item.academic_year_id) === id
    );

    if (!year) {
        showToast("Academic year not found.");
        return;
    }

    const confirmed = window.confirm(
        `Change ${year.year_name} status from ${year.status} to ${status}?`
    );

    if (!confirmed) return;

    try {

        const result = await apiRequest({
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "update_status",
                academic_year_id: id,
                status: status
            })
        });

        showToast(result.message || "Academic year status updated.");

        await loadAcademicYears();

    } catch (error) {

        showToast(error.message);

    }
}

function exportCSV() {

    if (!filteredYears.length) {
        showToast("No records available to export.");
        return;
    }

    const columns = [
        "academic_year_id",
        "year_name",
        "start_date",
        "end_date",
        "status",
        "created_at"
    ];

    const quote = value =>
        `"${String(value ?? "").replace(/"/g, '""')}"`;

    const lines = [
        columns.map(quote).join(","),
        ...filteredYears.map(year =>
            columns.map(column => quote(year[column])).join(",")
        )
    ];

    const blob = new Blob(
        ["\uFEFF" + lines.join("\r\n")],
        { type: "text/csv;charset=utf-8;" }
    );

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download = "skwela-academic-years.csv";

    document.body.appendChild(link);

    link.click();
    link.remove();

    URL.revokeObjectURL(url);

    showToast("CSV export prepared.");
}

let toastTimer;

function showToast(message) {

    const toast = el("toast");

    toast.textContent = message;
    toast.classList.add("show");

    clearTimeout(toastTimer);

    toastTimer = setTimeout(() => {
        toast.classList.remove("show");
    }, 2800);
}

el("addButton").addEventListener("click", () => openForm());

el("yearForm").addEventListener("submit", saveAcademicYear);

el("refreshButton").addEventListener("click", loadAcademicYears);

el("searchInput").addEventListener("input", renderTable);

el("statusFilter").addEventListener("change", renderTable);

el("exportButton").addEventListener("click", exportCSV);

document.querySelectorAll("[data-close]").forEach(button => {
    button.addEventListener("click", () => {
        closeOverlay(button.closest(".overlay"));
    });
});

document.querySelectorAll(".overlay").forEach(overlay => {

    overlay.addEventListener("click", event => {

        if (event.target === overlay) {
            closeOverlay(overlay);
        }

    });

});

el("tableBody").addEventListener("click", event => {

    const button = event.target.closest("[data-action]");

    if (!button) return;

    const id = Number(button.dataset.id);

    const year = academicYears.find(item =>
        Number(item.academic_year_id) === id
    );

    if (!year) {
        showToast("Academic year record was not found.");
        return;
    }

    if (button.dataset.action === "view") {
        showDetails(year);
    }

    if (button.dataset.action === "edit") {
        openForm(year);
    }

    if (button.dataset.action === "status") {
        changeStatus(id, button.dataset.status);
    }

});

document.addEventListener("keydown", event => {

    if (event.key === "Escape") {

        closeOverlay(el("formOverlay"));
        closeOverlay(el("detailsOverlay"));

    }

});

loadAcademicYears();
</script>

</body>
</html>