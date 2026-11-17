<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['registrar', 'admin'], true)
) {
    header('Location: ../../index.php');
    exit();
}

$registrarName = $_SESSION['full_name'] ?? 'Registrar';
$registrarEmail = $_SESSION['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Records | Skwela College Portal</title>

<link rel="stylesheet" href="../assets/css/registrar-students.css">
</head>

<body>

<div class="app-shell">

    <aside class="sidebar">

        <div class="brand">
            SKWELA
            <span>COLLEGE PORTAL</span>
        </div>

        <div class="menu-label">REGISTRAR WORKSPACE</div>

        <a class="nav-link" href="dashboard.php">▦ Dashboard</a>
        <a class="nav-link active" href="students.php">▣ Student Records</a>
        <a class="nav-link" href="dashboard.php">▤ Enrollment Applications</a>

        <div class="sidebar-bottom">
            <strong>
                <?= htmlspecialchars($registrarName, ENT_QUOTES, 'UTF-8') ?>
            </strong>

            <small>
                <?= htmlspecialchars($registrarEmail, ENT_QUOTES, 'UTF-8') ?>
            </small>

            <small>Registrar Account</small>

            <a class="logout-link" href="dashboard.php?logout=1">
                ↪ Sign out
            </a>
        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">
            <strong>Skwela College Portal</strong>

            <span>
                Welcome,
                <?= htmlspecialchars($registrarName, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </header>

        <section class="page-wrap">

            <div id="pageMessage" class="message" role="status"></div>

            <!-- STUDENT LIST -->

            <section id="studentListView">

                <div class="page-heading">

                    <div>
                        <h1>Student Records</h1>
                        <p>
                            Select a student to view their profile,
                            enrollment applications, and uploaded documents.
                        </p>
                    </div>

                    <button
                        class="secondary-btn"
                        id="refreshStudentsBtn"
                        type="button"
                    >
                        ↻ Refresh
                    </button>

                </div>

                <div class="summary-grid">

                    <div class="summary-card">
                        <span>Total Students</span>
                        <strong id="totalStudents">0</strong>
                    </div>

                    <div class="summary-card">
                        <span>Total Applications</span>
                        <strong id="totalStudentApplications">0</strong>
                    </div>

                    <div class="summary-card">
                        <span>Documents Pending</span>
                        <strong id="totalPendingStudentDocuments">0</strong>
                    </div>

                </div>

                <div class="panel">

                    <div class="panel-heading">
                        <div>
                            <h2>Registered Students</h2>
                            <p>
                                Each student appears once. Open a record
                                to see all their information.
                            </p>
                        </div>
                    </div>

                    <div class="toolbar">

                        <input
                            id="studentSearch"
                            class="search-input"
                            type="search"
                            placeholder="Search student name, number, or email..."
                        >

                        <select id="studentFilter" class="filter-select">
                            <option value="">All Students</option>
                            <option value="has_pending_documents">
                                With Pending Documents
                            </option>
                            <option value="has_applications">
                                With Enrollment Applications
                            </option>
                            <option value="no_applications">
                                No Enrollment Applications
                            </option>
                        </select>

                    </div>

                    <div id="studentSectionFolders" class="sr-folder-list">
                        <div class="sr-folder-empty">Loading student records...</div>
                    </div>

                </div>

            </section>

            <!-- INDIVIDUAL STUDENT DETAILS -->

            <section id="studentDetailView" class="hidden">

                <div class="page-heading detail-heading">

                    <div>

                        <button
                            id="backToStudentsBtn"
                            class="back-btn"
                            type="button"
                        >
                            ← Back to Student List
                        </button>

                        <h1 id="detailStudentName">Student Details</h1>

                        <p id="detailStudentNumber">
                            Student record and enrollment information.
                        </p>

                    </div>

                    <button
                        id="refreshStudentDetailBtn"
                        class="secondary-btn"
                        type="button"
                    >
                        ↻ Refresh Record
                    </button>

                </div>

                <!-- STUDENT OVERVIEW -->

                <div class="student-overview">

                    <div class="overview-card">
                        <span>Student Number</span>
                        <strong id="profileStudentNumber">—</strong>
                    </div>

                    <div class="overview-card">
                        <span>Email Address</span>
                        <strong id="profileStudentEmail">—</strong>
                    </div>

                    <div class="overview-card">
                        <span>Total Applications</span>
                        <strong id="profileApplicationCount">0</strong>
                    </div>

                    <div class="overview-card">
                        <span>Uploaded Documents</span>
                        <strong id="profileDocumentCount">0</strong>
                    </div>

                </div>

                <!-- STUDENT INFORMATION -->

                <div class="panel">

                    <div class="panel-heading">
                        <div>
                            <h2>Student Information</h2>
                            <p>
                                Basic information connected to this student account.
                            </p>
                        </div>
                    </div>

                    <div class="information-grid">

                        <div class="information-item">
                            <span>Full Name</span>
                            <strong id="profileFullName">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Student Number</span>
                            <strong id="profileNumber">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Email</span>
                            <strong id="profileEmail">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Student Account ID</span>
                            <strong id="profileUserId">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Birth Date</span>
                            <strong id="profileBirthDate">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Gender</span>
                            <strong id="profileGender">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Phone Number</span>
                            <strong id="profilePhone">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Address</span>
                            <strong id="profileAddress">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Emergency Contact Name</span>
                            <strong id="profileEmergencyName">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Emergency Contact Number</span>
                            <strong id="profileEmergencyNumber">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Account Status</span>
                            <strong id="profileAccountStatus">—</strong>
                        </div>

                        <div class="information-item">
                            <span>Account Created</span>
                            <strong id="profileCreatedAt">—</strong>
                        </div>

                    </div>

                </div>

                <!-- ENROLLMENT APPLICATIONS -->

                <div class="panel">

                    <div class="panel-heading">
                        <div>
                            <h2>Enrollment Applications</h2>
                            <p>
                                All enrollment applications submitted by this student.
                            </p>
                        </div>
                    </div>

                    <div class="table-wrap">

                        <table>

                            <thead>
                                <tr>
                                    <th>APPLICATION ID</th>
                                    <th>COURSE</th>
                                    <th>SECTION</th>
                                    <th>YEAR LEVEL</th>
                                    <th>ACADEMIC YEAR</th>
                                    <th>SEMESTER</th>
                                    <th>STUDENT TYPE</th>
                                    <th>SUBMITTED</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>

                            <tbody id="studentApplicationsTable">
                                <tr>
                                    <td colspan="9" class="empty-state">
                                        Loading applications...
                                    </td>
                                </tr>
                            </tbody>

                        </table>

                    </div>

                </div>

                <!-- STUDENT DOCUMENTS -->

                <div class="panel">

                    <div class="panel-heading">
                        <div>
                            <h2>Uploaded Documents</h2>
                            <p>
                                Only documents belonging to this student
                                are displayed here.
                            </p>
                        </div>
                    </div>

                    <div class="document-stats" id="studentDocumentStats"></div>

                    <div class="table-wrap">

                        <table>

                            <thead>
                                <tr>
                                    <th>DOCUMENT ID</th>
                                    <th>DOCUMENT NAME</th>
                                    <th>APPLICATION</th>
                                    <th>DATE UPLOADED</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>

                            <tbody id="studentDocumentsTable">
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        Loading uploaded documents...
                                    </td>
                                </tr>
                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

        </section>

    </main>

</div>

<!-- DOCUMENT PREVIEW MODAL -->

<div class="modal" id="previewModal">

    <div class="modal-box">

        <div class="modal-heading">

            <h2 id="previewTitle">Document Preview</h2>

            <button
                id="closePreviewBtn"
                class="close-btn"
                type="button"
            >
                ✕
            </button>

        </div>

        <iframe
            id="previewFrame"
            title="Enrollment document preview"
        ></iframe>
<div id="srPageMessage" class="sr-page-message" role="status"></div>

<div id="studentListView">
    <div class="page-header">
        <div>
            <h1>Student Records</h1>
            <p>Select a student to view their profile, enrollment applications, and uploaded documents.</p>
        </div>
        <button class="refresh-btn" id="refreshStudentsBtn" type="button">↻ Refresh</button>
    </div>

    <div class="sr-summary-grid">
        <div class="stat-card">
            <div class="stat-label">Total Students</div>
            <div class="stat-number" id="totalStudents">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Applications</div>
            <div class="stat-number" id="totalStudentApplications">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending Documents</div>
            <div class="stat-number" id="totalPendingStudentDocuments">0</div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Registered Students</h2>
                <p>Each student appears once. Select a student to open their complete record.</p>
            </div>
        </div>

        <div class="toolbar">
            <input id="studentSearch" class="search-input" type="search" placeholder="Search student name, number, or email...">
            <select id="studentFilter" class="filter-select">
                <option value="">All Students</option>
                <option value="has_pending_documents">With Pending Documents</option>
                <option value="has_applications">With Enrollment Applications</option>
                <option value="no_applications">No Enrollment Applications</option>
            </select>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Applications</th>
                        <th>Documents</th>
                        <th>Pending</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="studentsTable">
                    <tr><td colspan="7" class="empty-state">Click Student Records to load student records.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="studentDetailView" class="sr-hidden">
    <div class="page-header">
        <div>
            <button id="backToStudentsBtn" class="secondary-btn sr-back-btn" type="button">← Back to Student List</button>
            <h1 id="detailStudentName">Student Details</h1>
            <p id="detailStudentNumber">Student record and enrollment information.</p>
        </div>
        <button id="refreshStudentDetailBtn" class="refresh-btn" type="button">↻ Refresh Record</button>
    </div>

    <div class="sr-detail-summary">
        <div class="stat-card">
            <div class="stat-label">Student Number</div>
            <div class="sr-detail-value" id="profileStudentNumber">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Email Address</div>
            <div class="sr-detail-value" id="profileStudentEmail">—</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Applications</div>
            <div class="sr-detail-value" id="profileApplicationCount">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Uploaded Documents</div>
            <div class="sr-detail-value" id="profileDocumentCount">0</div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Student Information</h2>
                <p>Basic information connected to this student account.</p>
            </div>
        </div>

        <div class="sr-information-grid">
            <div class="sr-information-item"><span>Full Name</span><strong id="profileFullName">—</strong></div>
            <div class="sr-information-item"><span>Student Number</span><strong id="profileNumber">—</strong></div>
            <div class="sr-information-item"><span>Email</span><strong id="profileEmail">—</strong></div>
            <div class="sr-information-item"><span>Student Account ID</span><strong id="profileUserId">—</strong></div>
            <div class="sr-information-item"><span>Birth Date</span><strong id="profileBirthDate">—</strong></div>
            <div class="sr-information-item"><span>Gender</span><strong id="profileGender">—</strong></div>
            <div class="sr-information-item"><span>Phone Number</span><strong id="profilePhone">—</strong></div>
            <div class="sr-information-item"><span>Address</span><strong id="profileAddress">—</strong></div>
            <div class="sr-information-item"><span>Emergency Contact Name</span><strong id="profileEmergencyName">—</strong></div>
            <div class="sr-information-item"><span>Emergency Contact Number</span><strong id="profileEmergencyNumber">—</strong></div>
            <div class="sr-information-item"><span>Account Status</span><strong id="profileAccountStatus">—</strong></div>
            <div class="sr-information-item"><span>Account Created</span><strong id="profileCreatedAt">—</strong></div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Enrollment Applications</h2>
                <p>All enrollment applications submitted by this student.</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Application ID</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Year Level</th>
                        <th>Academic Year</th>
                        <th>Semester</th>
                        <th>Student Type</th>
                        <th>Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="studentApplicationsTable">
                    <tr><td colspan="9" class="empty-state">Loading applications...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div>
                <h2>Uploaded Documents</h2>
                <p>Only documents belonging to this student are displayed here.</p>
            </div>
        </div>
        <div class="sr-document-stats" id="studentDocumentStats"></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Document ID</th>
                        <th>Document Name</th>
                        <th>Application</th>
                        <th>Date Uploaded</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentDocumentsTable">
                    <tr><td colspan="6" class="empty-state">Loading documents...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

    </div>

</div>

<div id="toast" class="toast"></div>

<script src="../assets/js/registrar-students.js" defer></script>

</body>
</html>