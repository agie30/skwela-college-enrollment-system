
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: ../../index.php");
    exit();
}
require_once __DIR__ . '/../includes/profile_bootstrap.php';
$activeAdminPage = 'student_accounts';
$adminName = htmlspecialchars($sharedProfileName, ENT_QUOTES, 'UTF-8');
$adminInitial = htmlspecialchars($sharedProfileInitials, ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Account Management | Skwela College Enrollment System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=20261117-final">
<link rel="stylesheet" href="../assets/css/profile-modal.css?v=20261117-final">
<link rel="stylesheet" href="../assets/css/sidebar-unified.css?v=20261117-final">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f1f5f9;
    color: #1e293b;
}

button, input, select, textarea {
    font: inherit;
}

button {
    cursor: pointer;
}

.container {
    max-width: 1450px;
    margin: 35px auto;
    padding: 0 25px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.header h1 {
    color: #102a56;
    font-size: 26px;
}

.header p {
    color: #64748b;
    font-size: 14px;
    margin-top: 8px;
}

.back-btn {
    display: inline-block;
    background: #e2e8f0;
    color: #334155;
    text-decoration: none;
    padding: 11px 16px;
    border-radius: 8px;
    font-size: 13px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 17px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border: 1px solid #edf0f5;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15,23,42,.04);
}

.stat-card p {
    color: #64748b;
    font-size: 13px;
}

.stat-card h2 {
    margin-top: 12px;
    font-size: 30px;
    color: #102a56;
}

.panel {
    background: white;
    border: 1px solid #edf0f5;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15,23,42,.04);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.panel-header h2 {
    font-size: 18px;
    color: #102a56;
}

.panel-header p {
    font-size: 12px;
    color: #64748b;
    margin-top: 6px;
}

.primary-btn {
    background: #174ea6;
    color: white;
    padding: 11px 17px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
}

.primary-btn:hover {
    background: #123b80;
}

.primary-btn:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
}

.search-input,
.filter-select {
    border: 1px solid #dbe2ea;
    background: white;
    padding: 11px 13px;
    border-radius: 8px;
    outline: none;
    color: #334155;
    font-size: 13px;
}

.search-input {
    flex: 1;
    min-width: 230px;
}

.filter-select:focus,
.search-input:focus,
.form-control:focus {
    border-color: #174ea6;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    text-align: left;
    padding: 14px 12px;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
}

td {
    padding: 15px 12px;
    border-bottom: 1px solid #edf0f5;
    color: #334155;
    font-size: 13px;
    vertical-align: middle;
}

tbody tr:hover {
    background: #fafcff;
}

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
}

.status.active {
    color: #166534;
    background: #dcfce7;
}

.status.inactive,
.status.suspended {
    color: #991b1b;
    background: #fee2e2;
}

.action-btn {
    border: none;
    border-radius: 6px;
    padding: 8px 11px;
    font-size: 12px;
    font-weight: 600;
}

.activate-btn {
    color: #166534;
    background: #dcfce7;
}

.deactivate-btn {
    color: #991b1b;
    background: #fee2e2;
}

.empty-state {
    text-align: center;
    padding: 35px 15px;
    color: #94a3b8;
}

.notice {
    padding: 13px 15px;
    border-radius: 8px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
    font-size: 12px;
    line-height: 1.6;
    margin-bottom: 20px;
}

.message {
    display: none;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
}

.message.success {
    display: block;
    background: #dcfce7;
    color: #166534;
}

.message.error {
    display: block;
    background: #fee2e2;
    color: #991b1b;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 20;
    background: rgba(15,23,42,.58);
    padding: 20px;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    width: 100%;
    max-width: 650px;
    max-height: 92vh;
    overflow-y: auto;
    padding: 27px;
    background: white;
    border-radius: 13px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.modal-header h2 {
    font-size: 20px;
    color: #102a56;
}

.close-btn {
    background: #f1f5f9;
    color: #334155;
    border: none;
    border-radius: 7px;
    padding: 8px 11px;
}

.modal-subtitle {
    color: #64748b;
    font-size: 12px;
    margin-bottom: 23px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 12px;
    color: #334155;
    font-weight: 700;
}

.form-control {
    width: 100%;
    border: 1px solid #dbe2ea;
    border-radius: 8px;
    outline: none;
    padding: 11px 12px;
    color: #1e293b;
    font-size: 13px;
}

textarea.form-control {
    min-height: 75px;
    resize: vertical;
}

.section-label {
    grid-column: 1 / -1;
    font-size: 12px;
    font-weight: 700;
    color: #174ea6;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 9px;
    margin-top: 5px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 15px;
}

.cancel-btn {
    padding: 11px 16px;
    border: none;
    border-radius: 8px;
    background: #e2e8f0;
    color: #334155;
}

.student-number-result {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    color: #64748b;
    font-size: 12px;
}

.toast {
    display: none;
    position: fixed;
    right: 25px;
    bottom: 25px;
    z-index: 50;
    background: #102a56;
    color: white;
    border-radius: 8px;
    padding: 14px 18px;
    max-width: 350px;
    font-size: 13px;
}

.toast.show {
    display: block;
}

.toast.error {
    background: #991b1b;
}

@media(max-width: 700px) {
    .container {
        padding: 0 14px;
        margin: 20px auto;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .panel {
        padding: 16px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full,
    .section-label {
        grid-column: auto;
    }

    .modal-content {
        padding: 20px;
    }
}
.main-area { margin-left:255px; min-width:0; }
.page-content { max-width:1600px; margin:0 auto; padding:30px 34px 28px; }
.page-content .container { max-width:1450px; margin:0 auto; padding:0; }
.topbar { height:72px; padding:0 32px; }
@media(max-width:1100px) { .main-area { margin-left:220px; } .page-content { padding-left:24px; padding-right:24px; } }
@media(max-width:700px) { .main-area { margin-left:0; } .page-content { padding:22px 14px; } }

.account-folder-list { display:grid; gap:10px; padding:16px 0 4px; }
.account-folder { border:1px solid #e3ebf4; border-radius:11px; background:#fff; overflow:hidden; }
.account-folder > summary { display:flex; align-items:center; gap:11px; min-height:54px; padding:12px 15px; cursor:pointer; list-style:none; color:#1b365e; }
.account-folder > summary::-webkit-details-marker { display:none; }
.account-folder > summary .folder-number { display:grid; place-items:center; width:25px; height:25px; border-radius:8px; background:#edf4ff; color:#2467b7; font-size:10px; font-weight:800; }
.account-folder > summary strong { color:#203b60; font-size:12px; }
.account-folder > summary small { margin-left:auto; color:#7b8da4; font-size:10px; }
.account-folder > summary .folder-chevron { color:#2467b7; font-size:17px; transition:transform .18s ease; }
.account-folder[open] > summary { background:#f8fbff; border-bottom:1px solid #e8eef6; }
.account-folder[open] > summary .folder-chevron { transform:rotate(180deg); }
.account-folder-content { padding:12px; }
.account-folder-description { margin:0 0 8px; color:#8796a9; font-size:10px; }
.account-folder-nested { margin:8px 0 0 18px; border-color:#e7edf5; }
.account-folder-nested > summary { min-height:48px; padding:9px 12px; }
.account-folder-table-wrap { overflow-x:auto; border:1px solid #e8eef6; border-radius:9px; }
.account-folder-table { width:100%; min-width:820px; border-collapse:collapse; font-size:11px; }
.account-folder-table th { padding:12px 13px; background:#f5f8fc; color:#60748d; font-size:9px; text-align:left; letter-spacing:.35px; white-space:nowrap; }
.account-folder-table td { padding:13px; border-top:1px solid #edf1f6; color:#51647d; vertical-align:middle; }
.account-folder-table tbody tr:hover { background:#f8fbff; }
.account-folder-empty { padding:25px 14px; color:#8795a8; font-size:11px; text-align:center; }
@media(max-width:700px) { .account-folder > summary { padding:10px; gap:8px; } .account-folder-content { padding:8px; } }
</style>
</head>

<body>
<div class="app-shell">
<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
<main class="main-area">
    <header class="topbar">
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle navigation">☰</button>
        <div class="breadcrumb"><span>Workspace</span><b>/</b><strong>Student Accounts</strong></div>
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
<div class="container">

    <div class="header">
        <div>
            <h1>Student Account Management</h1>
            <p>Create and manage student accounts for Skwela College Enrollment System.</p>
        </div>

        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <div class="stats">

        <div class="stat-card">
            <p>Total Student Accounts</p>
            <h2 id="totalStudents">0</h2>
        </div>

        <div class="stat-card">
            <p>Active Students</p>
            <h2 id="activeStudents">0</h2>
        </div>

        <div class="stat-card">
            <p>Inactive / Suspended</p>
            <h2 id="inactiveStudents">0</h2>
        </div>

    </div>

    <div class="panel">

        <div class="panel-header">
            <div>
                <h2>Student Accounts</h2>
                <p>Manage student login credentials and profile information.</p>
            </div>

            <button class="primary-btn" onclick="openModal()">
                + Create Student Account
            </button>
        </div>

        <div class="notice">
            <strong>Enrollment information:</strong>
            Student accounts are created here. Students select their course,
            academic year, semester, and available section during enrollment.
        </div>

        <div id="message" class="message"></div>

        <div class="toolbar">

            <input
                type="text"
                class="search-input"
                id="searchInput"
                placeholder="Search student number, name, or email..."
                oninput="renderStudents()"
            >

            <select
                class="filter-select"
                id="statusFilter"
                onchange="renderStudents()"
            >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
            </select>

        </div>

        <div class="account-folder-list" id="studentTable" aria-live="polite">
            <div class="account-folder-empty">Loading student accounts...</div>
        </div>

    </div>

</div>
    </div>
</main>
</div>

<div class="modal" id="studentModal">

    <div class="modal-content">

        <div class="modal-header">
            <h2>Create Student Account</h2>
            <button class="close-btn" type="button" onclick="closeModal()">✕</button>
        </div>

        <p class="modal-subtitle">
            Enter the student's personal information and login credentials.
        </p>

        <form id="studentForm">

            <div class="form-grid">

                <div class="section-label">STUDENT INFORMATION</div>

                <div class="form-group">
                    <label>First Name *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="firstName"
                        maxlength="100"
                        required
                        placeholder="First name"
                    >
                </div>

                <div class="form-group">
                    <label>Middle Name</label>
                    <input
                        type="text"
                        class="form-control"
                        id="middleName"
                        maxlength="100"
                        placeholder="Middle name"
                    >
                </div>

                <div class="form-group">
                    <label>Last Name *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="lastName"
                        maxlength="100"
                        required
                        placeholder="Last name"
                    >
                </div>

                <div class="form-group">
                    <label>Birth Date</label>
                    <input
                        type="date"
                        class="form-control"
                        id="birthDate"
                    >
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select class="form-control" id="gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input
                        type="tel"
                        class="form-control"
                        id="phone"
                        maxlength="30"
                        placeholder="Phone number"
                    >
                </div>

                <div class="form-group full">
                    <label>Address</label>
                    <textarea
                        class="form-control"
                        id="address"
                        placeholder="Complete address"
                    ></textarea>
                </div>

                <div class="section-label">EMERGENCY CONTACT</div>

                <div class="form-group">
                    <label>Emergency Contact Name</label>
                    <input
                        type="text"
                        class="form-control"
                        id="emergencyName"
                        maxlength="150"
                        placeholder="Contact person's name"
                    >
                </div>

                <div class="form-group">
                    <label>Emergency Contact Number</label>
                    <input
                        type="tel"
                        class="form-control"
                        id="emergencyNumber"
                        maxlength="30"
                        placeholder="Contact number"
                    >
                </div>

                <div class="section-label">STUDENT LOGIN CREDENTIALS</div>

                <div class="form-group full">
                    <label>Email Address *</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        maxlength="150"
                        required
                        placeholder="student@example.com"
                    >
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        minlength="10"
                        required
                        placeholder="At least 10 characters"
                    >
                </div>

                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input
                        type="password"
                        class="form-control"
                        id="confirmPassword"
                        minlength="10"
                        required
                        placeholder="Re-enter password"
                    >
                </div>

            </div>

            <div class="student-number-result">
                The system will automatically generate the Student Number after account creation.
            </div>

            <div class="modal-actions">
                <button
                    type="button"
                    class="cancel-btn"
                    onclick="closeModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="primary-btn"
                    id="saveBtn"
                >
                    Create Student Account
                </button>
            </div>

        </form>

    </div>

</div>

<div class="toast" id="toast"></div>
<?php require __DIR__ . '/../includes/profile_modal.php'; ?>
<script src="../assets/js/profile-modal.js" defer></script>

<script>
const API_URL = "../../backend/routes/student_accounts.php";

const menuToggle = document.getElementById("menuToggle");
if (menuToggle) menuToggle.addEventListener("click", () => document.getElementById("sidebar").classList.toggle("open"));

let students = [];

function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    }[char]));
}

function showMessage(message, type = "error") {
    const box = document.getElementById("message");

    box.textContent = message;
    box.className = "message " + type;
}

function showToast(message, type = "success") {
    const toast = document.getElementById("toast");

    toast.textContent = message;
    toast.className = "toast show" + (type === "error" ? " error" : "");

    setTimeout(() => {
        toast.className = "toast";
    }, 4000);
}

function openModal() {
    document.getElementById("studentForm").reset();
    document.getElementById("message").className = "message";
    document.getElementById("studentModal").classList.add("show");
}

function closeModal() {
    document.getElementById("studentModal").classList.remove("show");
}

async function requestJSON(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options,
        headers: {
            ...(options.headers || {})
        }
    });

    const text = await response.text();

    let data;

    try {
        data = JSON.parse(text);
    } catch (error) {
        throw new Error("The server returned an invalid response. Check the backend PHP file.");
    }

    if (!response.ok || data.success === false) {
        throw new Error(data.message || "The request could not be completed.");
    }

    return data;
}

async function loadStudents() {
    const tbody = document.getElementById("studentTable");

    tbody.innerHTML = '<div class="account-folder-empty">Loading student accounts...</div>';

    try {
        const data = await requestJSON(API_URL);

        students = Array.isArray(data.students) ? data.students : [];

        updateStatistics();
        renderStudents();

    } catch (error) {
        tbody.innerHTML = `<div class="account-folder-empty">${escapeHTML(error.message)}</div>`;

        showMessage(error.message);
    }
}

function updateStatistics() {
    const active = students.filter(student =>
        student.account_status === "active"
    ).length;

    document.getElementById("totalStudents").textContent =
        students.length;

    document.getElementById("activeStudents").textContent =
        active;

    document.getElementById("inactiveStudents").textContent =
        students.length - active;
}

function renderStudents() {
    const container = document.getElementById("studentTable");
    const search = document.getElementById("searchInput").value.trim().toLowerCase();
    const statusFilter = document.getElementById("statusFilter").value;

    const filtered = students.filter(student => {
        const fullName = [student.first_name, student.middle_name, student.last_name].filter(Boolean).join(" ");
        const searchText = [
            student.student_number, fullName, student.full_name, student.email,
            student.course_code, student.course_name, student.section_name,
            student.year_name, student.semester_name, student.enrollment_status
        ].join(" ").toLowerCase();
        const matchesSearch = searchText.includes(search);
        const matchesStatus = !statusFilter || String(student.account_status || '').toLowerCase() === statusFilter;
        return matchesSearch && matchesStatus;
    }).sort((a, b) => {
        const lastA = String(a.last_name || '').trim();
        const lastB = String(b.last_name || '').trim();
        const firstA = String(a.first_name || '').trim();
        const firstB = String(b.first_name || '').trim();
        return lastA.localeCompare(lastB, 'en', { sensitivity: 'base' }) ||
            firstA.localeCompare(firstB, 'en', { sensitivity: 'base' }) ||
            String(a.middle_name || '').localeCompare(String(b.middle_name || ''), 'en', { sensitivity: 'base' });
    });

    const courseGroups = new Map();
    filtered.forEach(student => {
        const courseCode = student.course_code || 'Not Yet Enrolled';
        const courseKey = courseCode === 'Not Yet Enrolled'
            ? 'not-enrolled'
            : `${courseCode}|${student.year_name || ''}|${student.semester_name || ''}`;
        if (!courseGroups.has(courseKey)) {
            courseGroups.set(courseKey, {
                code: courseCode,
                name: student.course_name || '',
                yearName: student.year_name || '',
                semesterName: student.semester_name || '',
                sections: new Map()
            });
        }
        const course = courseGroups.get(courseKey);
        const sectionName = student.section_name || 'No Section Assigned';
        const sectionKey = student.section_id ? String(student.section_id) : sectionName;
        if (!course.sections.has(sectionKey)) {
            course.sections.set(sectionKey, { name: sectionName, students: [] });
        }
        course.sections.get(sectionKey).students.push(student);
    });

    const courses = [...courseGroups.values()].sort((a, b) => {
        if (a.code === 'Not Yet Enrolled') return 1;
        if (b.code === 'Not Yet Enrolled') return -1;
        return String(a.code).localeCompare(String(b.code), 'en', { numeric: true, sensitivity: 'base' }) ||
            String(a.yearName).localeCompare(String(b.yearName)) ||
            String(a.semesterName).localeCompare(String(b.semesterName));
    });

    const heading = `<thead><tr><th>#</th><th>STUDENT NUMBER</th><th>STUDENT NAME (LAST, FIRST)</th><th>EMAIL ADDRESS</th><th>GENDER</th><th>ACCOUNT STATUS</th><th>DATE CREATED</th><th>ACTION</th></tr></thead>`;
    container.innerHTML = courses.length ? courses.map((course, courseIndex) => {
        const sectionList = [...course.sections.values()].sort((a, b) =>
            String(a.name).localeCompare(String(b.name), 'en', { numeric: true, sensitivity: 'base' })
        );
        const sectionMarkup = sectionList.map((section, sectionIndex) => {
            const rows = section.students.slice().sort((a, b) =>
                String(a.last_name || '').localeCompare(String(b.last_name || ''), 'en', { sensitivity: 'base' }) ||
                String(a.first_name || '').localeCompare(String(b.first_name || ''), 'en', { sensitivity: 'base' }) ||
                String(a.middle_name || '').localeCompare(String(b.middle_name || ''), 'en', { sensitivity: 'base' })
            );
            const body = rows.map((student, index) => {
                const status = String(student.account_status || 'inactive').toLowerCase();
                const fullName = `${student.last_name || ''}, ${[student.first_name, student.middle_name].filter(Boolean).join(' ')}`.replace(/^,\s*/, '').trim();
                const nextStatus = status === 'active' ? 'inactive' : 'active';
                return `<tr>
                    <td>${index + 1}</td>
                    <td><strong>${escapeHTML(student.student_number)}</strong></td>
                    <td>${escapeHTML(fullName || student.full_name)}</td>
                    <td>${escapeHTML(student.email)}</td>
                    <td>${escapeHTML(student.gender || '—')}</td>
                    <td><span class="status ${escapeHTML(status)}">${escapeHTML(status)}</span></td>
                    <td>${escapeHTML(student.created_at || '—')}</td>
                    <td><button class="action-btn ${nextStatus === 'active' ? 'activate-btn' : 'deactivate-btn'}" data-user-id="${Number(student.user_id)}" data-next-status="${nextStatus}">${nextStatus === 'active' ? 'Activate' : 'Deactivate'}</button></td>
                </tr>`;
            }).join('');
            const table = `<div class="account-folder-table-wrap"><table class="account-folder-table">${heading}<tbody>${body}</tbody></table></div>`;
            return `<details class="account-folder account-folder-nested">
                <summary><span class="folder-number">${sectionIndex + 1}</span><strong>${section.name.startsWith('Section') || section.name.includes('No Section') ? escapeHTML(section.name) : `Section ${escapeHTML(section.name)}`}</strong><small>${rows.length} student(s)</small><span class="folder-chevron">⌄</span></summary>
                <div class="account-folder-content">${table}</div>
            </details>`;
        }).join('');
        const courseTitle = course.code === 'Not Yet Enrolled'
            ? 'Not Yet Enrolled'
            : `${course.code} — ${course.name}`;
        const detail = course.code === 'Not Yet Enrolled'
            ? 'Student accounts that have not submitted an enrollment application.'
            : `${course.yearName || 'School year not specified'} · ${course.semesterName || 'Semester not specified'}`;
        const studentCount = sectionList.reduce((sum, section) => sum + section.students.length, 0);
        return `<details class="account-folder">
            <summary><span class="folder-number">${courseIndex + 1}</span><strong>${escapeHTML(courseTitle)}</strong><small>${studentCount} student(s)</small><span class="folder-chevron">⌄</span></summary>
            <div class="account-folder-content"><div class="account-folder-description">${escapeHTML(detail)}</div>${sectionMarkup}</div>
        </details>`;
    }).join('') : '<div class="account-folder-empty">No student accounts match your search or filter.</div>';
}

document.getElementById("studentForm").addEventListener("submit", async function(event) {

    event.preventDefault();

    const first_name = document.getElementById("firstName").value.trim();
    const middle_name = document.getElementById("middleName").value.trim();
    const last_name = document.getElementById("lastName").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (password.length < 10) {
        showMessage("Password must contain at least 10 characters.");
        return;
    }

    if (password !== confirmPassword) {
        showMessage("Passwords do not match.");
        return;
    }

    const saveBtn = document.getElementById("saveBtn");

    saveBtn.disabled = true;
    saveBtn.textContent = "Creating Account...";

    const payload = {
        action: "add",
        first_name,
        middle_name,
        last_name,
        email,
        password,
        birth_date: document.getElementById("birthDate").value,
        gender: document.getElementById("gender").value,
        phone: document.getElementById("phone").value.trim(),
        address: document.getElementById("address").value.trim(),
        emergency_contact_name: document.getElementById("emergencyName").value.trim(),
        emergency_contact_number: document.getElementById("emergencyNumber").value.trim()
    };

    try {
        const data = await requestJSON(API_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        closeModal();

        showToast(
            "Student account created. Student Number: " +
            data.student_number
        );

        await loadStudents();

    } catch (error) {
        showMessage(error.message);
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = "Create Student Account";
    }
});

document.getElementById("studentTable").addEventListener("click", async function(event) {

    const button = event.target.closest("button[data-user-id]");

    if (!button) return;

    const userId = Number(button.dataset.userId);
    const nextStatus = button.dataset.nextStatus;

    const action = nextStatus === "active"
        ? "activate"
        : "deactivate";

    if (!confirm(`Are you sure you want to ${action} this student account?`)) {
        return;
    }

    button.disabled = true;

    try {
        const data = await requestJSON(API_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "update_status",
                user_id: userId,
                account_status: nextStatus
            })
        });

        showToast(data.message || "Student account updated.");
        await loadStudents();

    } catch (error) {
        showToast(error.message, "error");
        button.disabled = false;
    }
});

document.getElementById("studentModal").addEventListener("click", function(event) {
    if (event.target === this) {
        closeModal();
    }
});

loadStudents();
</script>

</body>
</html>
