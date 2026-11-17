<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "student") {
    header("Location: ../../index.php");
    exit();
}

require_once __DIR__ . '/../includes/profile_bootstrap.php';
$studentName = $sharedProfileName;
$studentEmail = $sharedProfileEmail;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard | Skwela College Portal</title>

<link rel="stylesheet" href="../assets/css/student-dashboard.css">
<link rel="stylesheet" href="../assets/css/profile-modal.css">
<link rel="stylesheet" href="../assets/css/sidebar-unified.css">
</head>

<body>

<div class="layout student-layout">

    <aside class="sidebar" id="sidebar">

        <div class="brand">
            <div class="brand-logo" aria-hidden="true">S</div>
            <div class="brand-copy">
                <strong>SKWELA</strong>
                <span>COLLEGE PORTAL</span>
            </div>
        </div>

        <div class="menu-label">
            <span class="workspace-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
            STUDENT WORKSPACE
        </div>

        <button class="nav-btn active" data-page="overview"><span>▦</span> Dashboard</button>
        <button class="nav-btn" data-page="enrollment"><span>▤</span> Enrollment &amp; Schedule</button>
        <button class="nav-btn" data-page="history"><span>◷</span> Enrollment History</button>
        <button class="nav-btn" data-page="documents"><span>▧</span> Documents</button>


        <div class="sidebar-bottom">
            <div class="student-account-info">
                <strong><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8') ?></small>
                <small>Student Account</small>
            </div>
            <button class="logout-btn" id="logoutBtn" type="button"><span aria-hidden="true">↪</span> Sign out</button>
        </div>

    </aside>

    <main class="main">

        <header class="topbar">
            <strong>Skwela College Portal</strong>
            <button type="button" class="shared-profile-trigger" onclick="openSharedProfile()" aria-label="Open student profile">
                <?php if ($sharedProfileImageUrl !== ''): ?>
                    <img class="shared-profile-avatar" src="<?= htmlspecialchars($sharedProfileImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Student profile picture">
                <?php else: ?>
                    <span class="shared-profile-avatar"><?= htmlspecialchars($sharedProfileInitials, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="shared-profile-trigger-name">Welcome, <?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </header>

        <div class="content">

            <div id="globalMessage" class="message"></div>

            <section class="page active" id="overview">

                <div class="page-heading">
                    <div>
                        <h1>Student Dashboard</h1>
                        <p>Manage your college enrollment and student information.</p>
                    </div>

                    <button class="secondary-btn" onclick="refreshData()">↻ Refresh Data</button>
                </div>

                <div class="cards">

                    <div class="card">
                        <p>Total Applications</p>
                        <h2 id="totalApplications">0</h2>
                    </div>

                    <div class="card">
                        <p>Approved Applications</p>
                        <h2 id="approvedApplications">0</h2>
                    </div>

                    <div class="card">
                        <p>Pending Applications</p>
                        <h2 id="pendingApplications">0</h2>
                    </div>

                </div>

                <div class="panel">
                    <h2>Welcome to Skwela College Portal</h2>
                    <p class="panel-description">
                        Your student enrollment workspace.
                    </p>

                    <p style="font-size:13px;line-height:1.8;color:#64748b">
                        Use Enrollment & Schedule to compare available class sections and timetables,
                        submit your preferred section, and view only your saved selection.
                        You can also track application status and upload enrollment documents.
                    </p>

                    <br>

                    <button class="primary-btn" onclick="showPage('enrollment')">
                        Start Enrollment →
                    </button>
                </div>

                <div class="panel">
                    <h2>Recent Enrollment Applications</h2>
                    <p class="panel-description">Your latest submitted applications.</p>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>APPLICATION ID</th>
                                    <th>COURSE</th>
                                    <th>SECTION</th>
                                    <th>DATE SUBMITTED</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody id="recentTable">
                                <tr><td colspan="5" class="empty">Loading applications...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

            <section class="page" id="enrollment">

                <div class="page-heading">
                    <div>
                        <h1>Enrollment &amp; Class Schedule</h1>
                        <p>Compare available timetables before selecting a section. After submission, only your selected section schedule remains visible here.</p>
                    </div>
                    <button class="secondary-btn" onclick="loadSections()">↻ Refresh</button>
                </div>

                <div class="panel">

                    <h2>Available Class Sections</h2>
                    <p class="panel-description">
                        Review the available course sections before applying.
                    </p>

                    <div id="sectionMessage" class="message"></div>

                    <div class="program-selection-help">
                        Select your program to view the available sections and their actual weekly class schedules.
                        Compare the days, times, subjects, and rooms before choosing your preferred section.
                    </div>

                    <div id="sectionTable" class="program-list" aria-live="polite">
                        <div class="program-loading">Loading available programs...</div>
                    </div>

                </div>

            </section>

            <section class="page" id="history">

                <div class="page-heading">
                    <div>
                        <h1>Enrollment History</h1>
                        <p>Track your submitted enrollment applications.</p>
                    </div>

                    <button class="secondary-btn" onclick="loadHistory()">↻ Refresh</button>
                </div>

                <div class="panel">
                    <h2>Applications</h2>
                    <p class="panel-description">Your enrollment records and current status.</p>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>APPLICATION ID</th>
                                    <th>COURSE</th>
                                    <th>SECTION</th>
                                    <th>ACADEMIC YEAR</th>
                                    <th>SEMESTER</th>
                                    <th>DATE SUBMITTED</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody id="historyTable">
                                <tr><td colspan="7" class="empty">Loading enrollment history...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

            <section class="page" id="documents">

                <div class="page-heading">
                    <div>
                        <h1>Documents</h1>
                        <p>Upload and monitor your enrollment documents.</p>
                    </div>

                    <button class="secondary-btn" onclick="loadDocuments()">↻ Refresh</button>
                </div>

                <div class="panel">

                    <h2>Upload Enrollment Document</h2>
                    <p class="panel-description">
                        Upload PDF, JPG, or PNG files. Maximum file size: 5 MB.
                    </p>

                    <div id="documentMessage" class="message"></div>

                    <form id="documentForm">

                        <div class="form-grid">

                            <div class="form-group">
                                <label>Enrollment Application *</label>
                                <select class="control" id="documentEnrollment" required>
                                    <option value="">Select an enrollment application</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Document Name *</label>
                                <select class="control" id="documentName" required>
                                    <option value="">Select document type</option>
                                    <option value="Birth Certificate">Birth Certificate</option>
                                    <option value="Form 138 / Report Card">Form 138 / Report Card</option>
                                    <option value="Good Moral Certificate">Good Moral Certificate</option>
                                    <option value="Transcript of Records">Transcript of Records</option>
                                    <option value="Certificate of Transfer">Certificate of Transfer</option>
                                    <option value="Other Supporting Document">Other Supporting Document</option>
                                </select>
                            </div>

                            <div class="form-group full">
                                <label>Select File *</label>
                                <input
                                    class="control"
                                    type="file"
                                    id="documentFile"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    required
                                >
                            </div>

                        </div>

                        <div class="form-actions">
                            <button class="primary-btn" id="uploadBtn" type="submit">
                                Upload Document
                            </button>
                        </div>

                    </form>

                </div>

                <div class="panel">
                    <h2>Document Requirements</h2>
                    <p class="panel-description">Check which documents are still needed for your selected enrollment application.</p>

                    <div id="documentChecklistSummary" class="document-summary">
                        Select an enrollment application to view its document requirements.
                    </div>

                    <div id="documentRequirements" class="document-requirements">
                        <p class="empty">Select an enrollment application to view requirements.</p>
                    </div>

                    <p class="panel-description" style="margin-top:15px;margin-bottom:0;">
                        Transcript of Records and Certificate of Transfer are conditional requirements. Confirm with the Registrar whether they apply to your enrollment.
                    </p>
                </div>

                <div class="panel">
                    <h2>Submitted Documents</h2>
                    <p class="panel-description">Check your document verification status.</p>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>DOCUMENT ID</th>
                                    <th>DOCUMENT NAME</th>
                                    <th>ENROLLMENT ID</th>
                                    <th>DATE UPLOADED</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody id="documentTable">
                                <tr><td colspan="5" class="empty">Loading documents...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

            <section class="page" id="profile">

                <div class="page-heading">
                    <div>
                        <h1>Profile</h1>
                        <p>Your student account information.</p>
                    </div>
                </div>

                <div class="panel">
                    <h2>Account Information</h2>
                    <p class="panel-description">Your registered login information.</p>

                    <div class="profile-grid">
                        <div class="profile-item">
                            <small>Full Name</small>
                            <strong><?= htmlspecialchars($studentName) ?></strong>
                        </div>

                        <div class="profile-item">
                            <small>Email Address</small>
                            <strong><?= htmlspecialchars($studentEmail) ?></strong>
                        </div>

                        <div class="profile-item">
                            <small>Account Type</small>
                            <strong>Student</strong>
                        </div>

                        <div class="profile-item">
                            <small>Account Status</small>
                            <strong>Active</strong>
                        </div>
                    </div>
                </div>

            </section>

        </div>

    </main>

</div>

<?php require __DIR__ . '/../includes/profile_modal.php'; ?>

<div class="toast" id="toast"></div>

<script>
const ENROLLMENT_API = "../../backend/routes/enrollments.php";
const DOCUMENT_API = "../../backend/routes/documents.php";

let enrollmentHistory = [];
let availableSections = [];
let programScheduleCache = {};
let programGroups = new Map();
let submittedDocuments = [];

function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    }[char]));
}

function getValue(object, keys, fallback = "—") {
    for (const key of keys) {
        if (object && object[key] !== undefined && object[key] !== null && object[key] !== "") {
            return object[key];
        }
    }

    return fallback;
}

function showPage(pageId) {
    document.querySelectorAll(".page").forEach(page => {
        page.classList.remove("active");
    });

    document.querySelectorAll(".nav-btn[data-page]").forEach(button => {
        button.classList.toggle("active", button.dataset.page === pageId);
    });

    document.getElementById(pageId).classList.add("active");

    if (pageId === "enrollment") loadSections();
    if (pageId === "history") loadHistory();

    if (pageId === "documents") {
        loadDocumentEnrollmentOptions();
        loadDocuments();
    }
}

document.querySelectorAll(".nav-btn[data-page]").forEach(button => {
    button.addEventListener("click", () => showPage(button.dataset.page));
});

function showMessage(elementId, message, type = "error") {
    const element = document.getElementById(elementId);

    element.textContent = message;
    element.className = "message " + type;
}

function showToast(message) {
    const toast = document.getElementById("toast");

    toast.textContent = message;
    toast.className = "toast show";

    setTimeout(() => toast.className = "toast", 4000);
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options
    });

    const text = await response.text();

    let data;

    try {
        data = JSON.parse(text);
    } catch (error) {
        throw new Error("Invalid server response. Please check the backend route.");
    }

    if (!response.ok || data.success === false) {
        throw new Error(data.message || "Request failed.");
    }

    return data;
}

function getHistoryArray(data) {
    return Array.isArray(data.enrollments) ? data.enrollments :
        Array.isArray(data.history) ? data.history : [];
}

function statusHTML(status) {
    const safeStatus = String(status || "pending").toLowerCase();

    return `<span class="status ${escapeHTML(safeStatus)}">${escapeHTML(safeStatus.replace("_", " "))}</span>`;
}

async function loadHistory() {
    const tbody = document.getElementById("historyTable");

    tbody.innerHTML = `<tr><td colspan="7" class="empty">Loading...</td></tr>`;

    try {
        const data = await apiRequest(ENROLLMENT_API + "?action=history");

        enrollmentHistory = getHistoryArray(data);

        renderHistory();
        renderRecent();
        updateStats();
        loadDocumentEnrollmentOptions();

    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="6" class="empty">${escapeHTML(error.message)}</td></tr>`;
    }
}

function renderHistory() {
    const tbody = document.getElementById("historyTable");

    if (!enrollmentHistory.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="empty">No enrollment applications yet.</td></tr>`;
        return;
    }

    tbody.innerHTML = enrollmentHistory.map(item => `
        <tr>
            <td>#${escapeHTML(getValue(item, ["enrollment_id", "id"]))}</td>
            <td>${escapeHTML(getValue(item, ["course_code", "course_name"]))}</td>
            <td>${escapeHTML(getValue(item, ["section_name"]))}</td>
            <td>${escapeHTML(getValue(item, ["year_name", "academic_year", "academic_year_name"]))}</td>
            <td>${escapeHTML(getValue(item, ["semester_name"]))}</td>
            <td>${escapeHTML(getValue(item, ["created_at", "date_submitted", "submitted_at"]))}</td>
            <td>${statusHTML(item.status)}</td>
        </tr>
    `).join("");
}

function renderRecent() {
    const tbody = document.getElementById("recentTable");

    if (!enrollmentHistory.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="empty">No applications submitted.</td></tr>`;
        return;
    }

    tbody.innerHTML = enrollmentHistory.slice(0, 5).map(item => `
        <tr>
            <td>#${escapeHTML(getValue(item, ["enrollment_id", "id"]))}</td>
            <td>${escapeHTML(getValue(item, ["course_code", "course_name"]))}</td>
            <td>${escapeHTML(getValue(item, ["section_name"]))}</td>
            <td>${escapeHTML(getValue(item, ["created_at", "date_submitted", "submitted_at"]))}</td>
            <td>${statusHTML(item.status)}</td>
        </tr>
    `).join("");
}

function updateStats() {
    document.getElementById("totalApplications").textContent = enrollmentHistory.length;

    document.getElementById("approvedApplications").textContent =
        enrollmentHistory.filter(item => item.status === "approved").length;

    document.getElementById("pendingApplications").textContent =
        enrollmentHistory.filter(item =>
            ["pending", "under_review"].includes(item.status)
        ).length;
}

async function loadSections() {
    const container = document.getElementById("sectionTable");
    const message = document.getElementById("sectionMessage");

    container.innerHTML = `<div class="program-loading">Loading available programs...</div>`;
    message.className = "message";
    message.textContent = "";
    programScheduleCache = {};

    try {
        const data = await apiRequest(ENROLLMENT_API + "?action=available_sections");

        availableSections = Array.isArray(data.sections) ? data.sections : [];

        renderSections();

    } catch (error) {
        container.innerHTML = `<div class="program-empty">${escapeHTML(error.message)}</div>`;
    }
}

function getApplicationForTerm(section) {
    return enrollmentHistory.find(item =>
        Number(item.academic_year_id) === Number(section.academic_year_id) &&
        Number(item.semester_id) === Number(section.semester_id)
    ) || null;
}

function hasApplicationForTerm(section) {
    return Boolean(getApplicationForTerm(section));
}

function findCurrentTermApplication() {
    for (const section of availableSections) {
        const application = getApplicationForTerm(section);
        if (application) return application;
    }
    return null;
}

function renderSections() {
    const container = document.getElementById("sectionTable");
    programGroups = new Map();
    programScheduleCache = {};
    sectionScheduleData.clear();

    if (!availableSections.length) {
        container.innerHTML = `<div class="program-empty">No active programs or available sections were found for the current enrollment period.</div>`;
        return;
    }

    availableSections.forEach(section => {
        const courseId = Number(section.course_id);
        const academicYearId = Number(section.academic_year_id);
        const semesterId = Number(section.semester_id);
        const groupKey = `${courseId}-${academicYearId}-${semesterId}`;

        if (!programGroups.has(groupKey)) {
            programGroups.set(groupKey, {
                groupKey,
                courseId,
                academicYearId,
                semesterId,
                courseCode: getValue(section, ["course_code"], "Program"),
                courseName: getValue(section, ["course_name"], ""),
                yearName: getValue(section, ["year_name"], ""),
                semesterName: getValue(section, ["semester_name"], ""),
                sections: []
            });
        }
        programGroups.get(groupKey).sections.push(section);
    });

    // Before a student submits an application, show every available program.
    // After submission, show only the selected program and selected section.
    const selectedApplication = findCurrentTermApplication();
    let programsToRender = Array.from(programGroups.values());

    if (selectedApplication) {
        programsToRender = programsToRender.filter(program =>
            program.courseId === Number(selectedApplication.course_id) &&
            program.academicYearId === Number(selectedApplication.academic_year_id) &&
            program.semesterId === Number(selectedApplication.semester_id)
        );
    }

    if (!programsToRender.length) {
        container.innerHTML = `<div class="program-empty">Your enrollment selection was found, but its section is not in the current available-section list. Please contact the Registrar to review your application.</div>`;
        return;
    }

    container.innerHTML = programsToRender.map(program => {
        const application = selectedApplication &&
            program.courseId === Number(selectedApplication.course_id) &&
            program.academicYearId === Number(selectedApplication.academic_year_id) &&
            program.semesterId === Number(selectedApplication.semester_id)
            ? selectedApplication
            : null;
        const selectedProgram = Boolean(application);
        const selectedSectionName = application
            ? getValue(application, ["section_name"], "")
            : "";
        const summary = selectedProgram
            ? `Your selection: Section ${escapeHTML(selectedSectionName)} · Only your selected timetable is shown`
            : `${program.sections.length} available section(s) · Click to compare schedules`;
        const actionMarkup = selectedProgram
            ? `<span class="program-toggle-action program-selected-label">Selected · View Schedule <span aria-hidden="true">⌄</span></span>`
            : `<span class="program-toggle-action">Open Course Folder <span aria-hidden="true">⌄</span></span>`;

        return `
            <article class="program-card ${selectedProgram ? "is-selected" : ""}">
                <button
                    type="button"
                    class="program-toggle ${selectedProgram ? "is-selected expanded" : ""}"
                    id="programToggle-${program.groupKey}"
                    aria-expanded="${selectedProgram ? "true" : "false"}"
                    aria-controls="programSchedule-${program.groupKey}"
                    onclick="toggleProgramSchedules('${program.groupKey}')"
                >
                    <span class="program-code-badge">${escapeHTML(program.courseCode)}</span>
                    <span class="program-toggle-copy">
                        <strong>${escapeHTML(program.courseName)}</strong>
                        <small>${escapeHTML(program.yearName)} · ${escapeHTML(program.semesterName)} · ${summary}</small>
                    </span>
                    ${actionMarkup}
                </button>
                <div class="program-schedule-panel" id="programSchedule-${program.groupKey}" ${selectedProgram ? "" : "hidden"}></div>
            </article>
        `;
    }).join("");

    if (selectedApplication) {
        const selectedProgram = programsToRender[0];
        if (selectedProgram) {
            loadSelectedSectionSchedule(selectedProgram, selectedApplication);
        }
    }
}

async function loadSelectedSectionSchedule(program, application) {
    const groupKey = program.groupKey;
    const panel = document.getElementById(`programSchedule-${groupKey}`);
    const toggle = document.getElementById(`programToggle-${groupKey}`);
    if (!panel || !toggle) return;

    panel.hidden = false;
    toggle.setAttribute("aria-expanded", "true");
    toggle.classList.add("expanded");
    panel.innerHTML = `<div class="program-loading">Loading your selected section timetable...</div>`;

    try {
        const query = new URLSearchParams({
            action: "program_schedule_options",
            course_id: String(program.courseId),
            academic_year_id: String(program.academicYearId),
            semester_id: String(program.semesterId)
        });
        const data = await apiRequest(ENROLLMENT_API + "?" + query.toString());
        const sections = Array.isArray(data.sections) ? data.sections : [];
        const selectedSectionId = Number(application.section_id);
        const selectedSections = sections.filter(section =>
            Number(section.section_id) === selectedSectionId
        );

        if (!selectedSections.length) {
            panel.innerHTML = `<div class="program-empty">Your selected section could not be loaded. Please contact the Registrar to review your timetable.</div>`;
            return;
        }

        // Cache only the selected section so reopening the panel never reveals alternatives.
        programScheduleCache[groupKey] = selectedSections;
        panel.innerHTML = renderProgramScheduleOptions(selectedSections);
    } catch (error) {
        panel.innerHTML = `<div class="program-empty">${escapeHTML(error.message)}</div>`;
    }
}

async function toggleProgramSchedules(groupKey) {
    const program = programGroups.get(groupKey);
    if (!program) return;

    const panel = document.getElementById(`programSchedule-${groupKey}`);
    const toggle = document.getElementById(`programToggle-${groupKey}`);
    if (!panel || !toggle || toggle.disabled) return;

    const isOpen = !panel.hidden;
    panel.hidden = isOpen;
    toggle.setAttribute("aria-expanded", String(!isOpen));
    toggle.classList.toggle("expanded", !isOpen);
    if (isOpen) return;

    if (programScheduleCache[groupKey]) {
        panel.innerHTML = renderProgramScheduleOptions(programScheduleCache[groupKey]);
        return;
    }

    panel.innerHTML = `<div class="program-loading">Loading section schedules...</div>`;

    try {
        const query = new URLSearchParams({
            action: "program_schedule_options",
            course_id: String(program.courseId),
            academic_year_id: String(program.academicYearId),
            semester_id: String(program.semesterId)
        });
        const data = await apiRequest(ENROLLMENT_API + "?" + query.toString());
        const sections = Array.isArray(data.sections) ? data.sections : [];
        programScheduleCache[groupKey] = sections;
        panel.innerHTML = renderProgramScheduleOptions(sections);
    } catch (error) {
        panel.innerHTML = `<div class="program-empty">${escapeHTML(error.message)}</div>`;
    }
}

function formatScheduleTime(value) {
    const match = String(value || "").match(/^(\d{1,2}):(\d{2})/);
    if (!match) return escapeHTML(value || "—");

    const hour24 = Number(match[1]);
    const minute = match[2];
    const suffix = hour24 >= 12 ? "PM" : "AM";
    const hour12 = (hour24 % 12) || 12;
    return `${hour12}:${minute} ${suffix}`;
}

const SECTION_WEEKDAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
const sectionScheduleData = new Map();

function expandScheduleDays(rawValue) {
    const raw = String(rawValue ?? "").trim();
    if (!raw) return [];

    const aliases = {
        monday: "Monday", mon: "Monday", m: "Monday",
        tuesday: "Tuesday", tue: "Tuesday", tues: "Tuesday", t: "Tuesday",
        wednesday: "Wednesday", wed: "Wednesday", w: "Wednesday",
        thursday: "Thursday", thu: "Thursday", thur: "Thursday", thurs: "Thursday", th: "Thursday",
        friday: "Friday", fri: "Friday", f: "Friday"
    };

    const parts = raw.split(/[,/&]+/).map(part => part.trim()).filter(Boolean);
    const expanded = [];
    parts.forEach(part => {
        const alias = aliases[part.toLowerCase()];
        if (alias) {
            expanded.push(alias);
            return;
        }

        const compact = part.toUpperCase().replace(/[^A-Z]/g, "");
        const tokens = compact.match(/TH|M|T|W|F/g) || [];
        if (tokens.join("") === compact && tokens.length) {
            tokens.forEach(token => {
                const day = token === "M" ? "Monday"
                    : token === "T" ? "Tuesday"
                    : token === "W" ? "Wednesday"
                    : token === "TH" ? "Thursday"
                    : token === "F" ? "Friday" : null;
                if (day) expanded.push(day);
            });
        }
    });

    return [...new Set(expanded)];
}

function renderSectionDayTable(sectionId, dayName, totalUnitsLabel) {
    const stored = sectionScheduleData.get(Number(sectionId));
    if (!stored) return `<div class="no-section-schedule">Schedule details are unavailable.</div>`;

    const dayRows = stored.schedules.filter(item =>
        expandScheduleDays(item.day_of_week ?? item.days).includes(dayName)
    ).sort((a, b) => String(a.start_time || "").localeCompare(String(b.start_time || "")));

    if (!dayRows.length) {
        return `<div class="no-section-schedule section-no-classes">No classes scheduled for ${escapeHTML(dayName)}.</div>`;
    }

    return `
        <div class="schedule-subject-stack" aria-label="${escapeHTML(dayName)} subjects">
            ${dayRows.map((item, index) => `
                <article class="schedule-subject-card">
                    <div class="schedule-subject-card-top">
                        <div class="schedule-subject-card-title">
                            <strong class="schedule-subject-card-code">${escapeHTML(item.subject_code || "—")}</strong>
                            <span class="schedule-subject-card-units">${escapeHTML(String(item.units ?? "—"))} unit${Number(item.units) === 1 ? "" : "s"}</span>
                        </div>
                        <span class="schedule-subject-card-number">Subject ${index + 1}</span>
                    </div>
                    <div class="schedule-subject-card-description">${escapeHTML(item.subject_name || "—")}</div>
                    <div class="schedule-subject-card-meta">
                        <div class="schedule-subject-card-meta-item">
                            <span class="schedule-subject-card-meta-label">TIME</span>
                            <strong>${formatScheduleTime(item.start_time)} – ${formatScheduleTime(item.end_time)}</strong>
                        </div>
                        <div class="schedule-subject-card-meta-item">
                            <span class="schedule-subject-card-meta-label">ROOM</span>
                            <strong>${escapeHTML(item.room || "Room not assigned")}</strong>
                        </div>
                    </div>
                </article>
            `).join("")}
            <div class="schedule-subject-total">
                <span>Total Semester Units</span>
                <strong>${totalUnitsLabel}</strong>
            </div>
        </div>
    `;
}

function selectSectionScheduleDay(sectionId, dayName) {
    const stored = sectionScheduleData.get(Number(sectionId));
    if (!stored || !SECTION_WEEKDAYS.includes(dayName)) return;

    const panel = document.getElementById(`sectionDayPanel-${sectionId}`);
    if (!panel) return;

    panel.innerHTML = renderSectionDayTable(sectionId, dayName, stored.totalUnitsLabel);
    const tabs = document.querySelectorAll(`[data-section-day-tabs="${sectionId}"] .schedule-day-tab`);
    tabs.forEach(tab => {
        const active = tab.dataset.day === dayName;
        tab.classList.toggle("active", active);
        tab.setAttribute("aria-selected", String(active));
    });
}

function renderSectionWeekdaySchedule(section, schedules, totalUnitsLabel) {
    const sectionId = Number(section.section_id);
    sectionScheduleData.set(sectionId, { schedules, totalUnitsLabel });

    const dayCounts = new Map(SECTION_WEEKDAYS.map(day => [day, 0]));
    schedules.forEach(item => {
        expandScheduleDays(item.day_of_week ?? item.days).forEach(day => {
            if (dayCounts.has(day)) dayCounts.set(day, dayCounts.get(day) + 1);
        });
    });

    const firstDay = SECTION_WEEKDAYS.find(day => dayCounts.get(day) > 0) || "Monday";

    return `
        <div class="section-weekday-filter" data-section-day-tabs="${sectionId}" role="tablist" aria-label="Choose a weekday">
            ${SECTION_WEEKDAYS.map(day => {
                const count = dayCounts.get(day);
                return `
                    <button
                        type="button"
                        class="schedule-day-tab ${day === firstDay ? "active" : ""}"
                        data-day="${day}"
                        role="tab"
                        aria-selected="${day === firstDay ? "true" : "false"}"
                        ${count === 0 ? "disabled" : `onclick="selectSectionScheduleDay(${sectionId}, '${day}')"`}
                    >
                        <span>${day}</span>
                        <small>${count ? `${count} class${count === 1 ? "" : "es"}` : "No class"}</small>
                    </button>
                `;
            }).join("")}
        </div>
        <div class="section-weekday-content" id="sectionDayPanel-${sectionId}" role="tabpanel">
            ${renderSectionDayTable(sectionId, firstDay, totalUnitsLabel)}
        </div>
    `;
}

function renderProgramScheduleOptions(sections) {
    // If the student already has an application for this term, keep only that section visible.
    if (sections.length) {
        const application = getApplicationForTerm(sections[0]);
        if (application && application.section_id !== undefined && application.section_id !== null) {
            sections = sections.filter(section =>
                Number(section.section_id) === Number(application.section_id)
            );
        }
    }

    if (!sections.length) {
        return `<div class="program-empty">There are no active sections for this program in the current enrollment period.</div>`;
    }

    return `
        <div class="section-choice-list">
            ${sections.map(section => {
                const schedules = Array.isArray(section.schedules) ? section.schedules : [];
                const capacity = Number(section.capacity) || 0;
                const remaining = Number(section.remaining_slots) || 0;
                const application = getApplicationForTerm(section);
                const alreadyApplied = Boolean(application);
                const isSelectedSection = Boolean(
                    application && Number(application.section_id) === Number(section.section_id)
                );
                const otherProgramLocked = Boolean(
                    application && Number(application.course_id) !== Number(section.course_id)
                );
                const canApply = schedules.length > 0 && remaining > 0 && !alreadyApplied;

                let actionLabel = "Choose This Schedule";
                if (otherProgramLocked) actionLabel = `Locked — ${getValue(application, ["course_code"], "other program")} selected`;
                else if (isSelectedSection) actionLabel = "Your Selected Section";
                else if (alreadyApplied) actionLabel = "Application Already Submitted";
                else if (remaining <= 0) actionLabel = "Section Full";
                else if (schedules.length === 0) actionLabel = "Schedule Not Posted";

                const subjectUnits = new Map();
                schedules.forEach(item => {
                    const subjectKey = String(item.subject_id ?? item.subject_code ?? item.subject_name ?? "");
                    if (subjectKey && !subjectUnits.has(subjectKey)) {
                        subjectUnits.set(subjectKey, Number(item.units) || 0);
                    }
                });
                const sectionTotalUnits = Array.from(subjectUnits.values()).reduce(
                    (total, units) => total + units,
                    0
                );
                const totalUnitsLabel = Number.isInteger(sectionTotalUnits)
                    ? sectionTotalUnits.toFixed(2)
                    : sectionTotalUnits.toFixed(2);

                const scheduleMarkup = schedules.length
                    ? renderSectionWeekdaySchedule(section, schedules, totalUnitsLabel)
                    : `<div class="no-section-schedule">The Registrar has not posted a class schedule for this section yet. You cannot choose this section until its schedule is available.</div>`;

                return `
                    <details class="section-choice-card section-choice-details" ${isSelectedSection ? "open" : ""}>
                        <summary class="section-choice-summary">
                            <div class="section-choice-header">
                                <div>
                                    <strong class="section-choice-name">Section ${escapeHTML(section.section_name)}</strong>
                                    <small>Year ${escapeHTML(section.year_level)} · ${escapeHTML(section.year_name)} · ${escapeHTML(section.semester_name)}</small>
                                </div>
                                <span class="section-capacity ${remaining <= 0 ? "full" : "available"}">
                                    ${remaining} of ${capacity} slot(s) available
                                </span>
                            </div>
                        </summary>
                        <div class="section-choice-content">
                            <div class="section-schedule-heading">Weekly Class Schedule</div>
                            <div class="schedule-preview-list">${scheduleMarkup}</div>
                            <div class="section-choice-footer">
                                ${alreadyApplied ? `<span class="section-choice-note">${isSelectedSection ? "This is your selected section." : `You already selected ${escapeHTML(getValue(application, ["course_code"], "a program"))} for this academic term. Other programs and sections are locked. Contact the Registrar if you need a correction.`}</span>` : `<span class="section-choice-note">Review the timetable before choosing this section.</span>`}
                                <button
                                    type="button"
                                    class="primary-btn section-choose-btn"
                                    ${canApply ? "" : "disabled"}
                                    onclick="submitEnrollment(${Number(section.section_id)})"
                                >${actionLabel}</button>
                            </div>
                            <div class="section-choice-close-row">
                                <button type="button" class="section-close-btn" onclick="this.closest('details.section-choice-details').open = false">
                                    <span aria-hidden="true">⌃</span> Close Section
                                </button>
                            </div>
                        </div>
                    </details>
                `;
            }).join("")}
        </div>
    `;
}

async function submitEnrollment(sectionId) {
    const section = availableSections.find(item =>
        Number(item.section_id) === Number(sectionId)
    );

    if (!section) return;

    const sectionName = getValue(section, ["section_name"], "selected section");

    if (!confirm("Submit an enrollment application for section " + sectionName + "?")) {
        return;
    }

    try {
        const data = await apiRequest(ENROLLMENT_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "submit",
                section_id: sectionId
            })
        });

        showMessage("globalMessage", data.message || "Enrollment application submitted successfully.", "success");

        await loadHistory();
        await loadSections();

        showToast("Enrollment application submitted.");

    } catch (error) {
        showMessage("sectionMessage", error.message);
    }
}

async function loadDocumentEnrollmentOptions() {
    const select = document.getElementById("documentEnrollment");

    try {
        const data = await apiRequest(ENROLLMENT_API + "?action=history");

        enrollmentHistory = getHistoryArray(data);

        const previousValue = select.value;

        select.innerHTML = `<option value="">Select an enrollment application</option>`;

        enrollmentHistory.forEach(item => {
            const option = document.createElement("option");

            option.value = item.enrollment_id;
            option.textContent = "Application #" + item.enrollment_id + " - " +
                getValue(item, ["course_code", "course_name"]) +
                " (" + getValue(item, ["status"]) + ")";

            select.appendChild(option);
        });

        if (previousValue && enrollmentHistory.some(item =>
            String(item.enrollment_id) === String(previousValue)
        )) {
            select.value = previousValue;
        } else {
            const latestEnrollment = [...enrollmentHistory].sort((a, b) =>
                Number(b.enrollment_id) - Number(a.enrollment_id)
            )[0];

            if (latestEnrollment) {
                select.value = latestEnrollment.enrollment_id;
            }
        }

        renderDocumentRequirements();

    } catch (error) {
        select.innerHTML = `<option value="">Unable to load applications</option>`;
        renderDocumentRequirements();
    }
}

function normalizeDocumentName(name) {
    return String(name || "")
        .trim()
        .toLowerCase()
        .replace(/\s+/g, " ");
}

function getRequiredDocumentList() {
    return [
        { name: "Birth Certificate", type: "required" },
        { name: "Form 138 / Report Card", type: "required" },
        { name: "Good Moral Certificate", type: "required" },
        { name: "Transcript of Records", type: "conditional" },
        { name: "Certificate of Transfer", type: "conditional" },
        { name: "Other Supporting Document", type: "optional" }
    ];
}

function renderDocumentRequirements() {
    const container = document.getElementById("documentRequirements");
    const summary = document.getElementById("documentChecklistSummary");
    const enrollmentSelect = document.getElementById("documentEnrollment");

    if (!container || !summary || !enrollmentSelect) return;

    const enrollmentId = Number(enrollmentSelect.value);

    if (!enrollmentId) {
        summary.textContent = "Select an enrollment application to view its document requirements.";
        container.innerHTML = `<p class="empty">No enrollment application selected.</p>`;
        return;
    }

    const requirements = getRequiredDocumentList();

    const applicationDocuments = submittedDocuments.filter(document =>
        Number(document.enrollment_id) === enrollmentId
    );

    let verifiedCount = 0;
    let needsAttention = 0;

    container.innerHTML = requirements.map(requirement => {
        const matchingDocuments = applicationDocuments
            .filter(document =>
                normalizeDocumentName(document.document_name) ===
                normalizeDocumentName(requirement.name)
            )
            .sort((a, b) =>
                String(b.uploaded_at || "").localeCompare(String(a.uploaded_at || ""))
            );

        const latestDocument = matchingDocuments[0];

        let statusText;
        let statusClass;

        if (latestDocument) {
            const status = String(
                latestDocument.verification_status || "pending"
            ).toLowerCase();

            if (["verified", "approved"].includes(status)) {
                statusText = "Verified";
                statusClass = "verified";

                if (requirement.type === "required") {
                    verifiedCount++;
                }

            } else if (["rejected", "revision_required", "resubmit_required"].includes(status)) {
                statusText = "Resubmit Required";
                statusClass = "resubmit";

                if (requirement.type === "required") {
                    needsAttention++;
                }

            } else {
                statusText = "Submitted - Pending Verification";
                statusClass = "pending";
            }

        } else if (requirement.type === "required") {
            statusText = "Missing";
            statusClass = "missing";
            needsAttention++;

        } else if (requirement.type === "conditional") {
            statusText = "Conditional";
            statusClass = "conditional";

        } else {
            statusText = "Optional";
            statusClass = "optional";
        }

        const description = requirement.type === "required"
            ? "Required document"
            : requirement.type === "conditional"
                ? "Submit only when applicable or requested by the Registrar"
                : "Additional supporting document";

        return `
            <div class="document-requirement">
                <div>
                    <strong>${escapeHTML(requirement.name)}</strong>
                    <small>${escapeHTML(description)}</small>
                </div>

                <span class="document-state ${statusClass}">
                    ${escapeHTML(statusText)}
                </span>
            </div>
        `;
    }).join("");

    const requiredCount = requirements.filter(item =>
        item.type === "required"
    ).length;

    summary.textContent =
        `${verifiedCount} of ${requiredCount} required documents verified. ` +
        `${needsAttention} document(s) need submission or correction.`;
}

async function loadDocuments() {
    const tbody = document.getElementById("documentTable");

    tbody.innerHTML = `<tr><td colspan="5" class="empty">Loading documents...</td></tr>`;

    try {
        const data = await apiRequest(DOCUMENT_API);

        submittedDocuments = Array.isArray(data.documents)
            ? data.documents
            : [];

        if (!submittedDocuments.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="empty">No documents uploaded yet.</td></tr>`;
        } else {
            tbody.innerHTML = submittedDocuments.map(document => `
                <tr>
                    <td>#${escapeHTML(getValue(document, ["document_id"]))}</td>
                    <td>${escapeHTML(getValue(document, ["document_name"]))}</td>
                    <td>#${escapeHTML(getValue(document, ["enrollment_id"]))}</td>
                    <td>${escapeHTML(getValue(document, ["uploaded_at", "created_at", "date_uploaded"]))}</td>
                    <td>${statusHTML(document.verification_status)}</td>
                </tr>
            `).join("");
        }

        renderDocumentRequirements();

    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="5" class="empty">${escapeHTML(error.message)}</td></tr>`;
        document.getElementById("documentChecklistSummary").textContent = error.message;
    }
}

document.getElementById("documentEnrollment").addEventListener(
    "change",
    renderDocumentRequirements
);

document.getElementById("documentForm").addEventListener("submit", async function(event) {
    event.preventDefault();

    const enrollmentId = document.getElementById("documentEnrollment").value;
    const documentName = document.getElementById("documentName").value;
    const file = document.getElementById("documentFile").files[0];

    if (!enrollmentId || !documentName || !file) {
        showMessage("documentMessage", "Please complete all required fields.");
        return;
    }

    const allowedTypes = ["application/pdf", "image/jpeg", "image/png"];

    if (!allowedTypes.includes(file.type)) {
        showMessage("documentMessage", "Only PDF, JPG, and PNG files are allowed.");
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        showMessage("documentMessage", "File must not exceed 5 MB.");
        return;
    }

    const formData = new FormData();

    formData.append("enrollment_id", enrollmentId);
    formData.append("document_name", documentName);
    formData.append("document", file);

    const button = document.getElementById("uploadBtn");

    button.disabled = true;
    button.textContent = "Uploading...";

    try {
        const data = await apiRequest(DOCUMENT_API, {
            method: "POST",
            body: formData
        });

        showMessage("documentMessage", data.message || "Document uploaded successfully.", "success");

        document.getElementById("documentForm").reset();
        document.getElementById("documentEnrollment").value = enrollmentId;

        await loadDocuments();

        showToast("Document uploaded successfully.");

    } catch (error) {
        showMessage("documentMessage", error.message);
    } finally {
        button.disabled = false;
        button.textContent = "Upload Document";
    }
});

async function refreshData() {
    await loadHistory();
    await loadSections();
    showToast("Dashboard data refreshed.");
}

document.getElementById("logoutBtn").addEventListener("click", async function() {
    if (!confirm("Are you sure you want to sign out?")) return;

    try {
        await fetch("../../backend/routes/auth.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "logout"
            })
        });
    } catch (error) {
    }

    window.location.href = "../../index.php";
});

(async function initializeEnrollmentOptions() {
    await loadHistory();
    await loadSections();
})();
</script>

<script src="../assets/js/profile-modal.js"></script>

</body>
</html>