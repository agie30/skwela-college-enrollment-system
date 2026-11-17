
<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

require_once __DIR__ . '/../includes/profile_bootstrap.php';
$activeAdminPage = (string) ($_GET['view'] ?? 'overview');
$adminName = htmlspecialchars($sharedProfileName, ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars($sharedProfileEmail, ENT_QUOTES, 'UTF-8');
$adminInitial = htmlspecialchars($sharedProfileInitials, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Skwela College Enrollment System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=20261117-final">
    <link rel="stylesheet" href="../assets/css/profile-modal.css">
    <link rel="stylesheet" href="../assets/css/sidebar-unified.css?v=20261117-final">
</head>
<body>
<div class="app-shell">

    <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="main-area">

        <header class="topbar">

            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle navigation">☰</button>

            <div class="breadcrumb">
                <span>Workspace</span>
                <b>/</b>
                <strong id="breadcrumbCurrent">Dashboard</strong>
            </div>

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

            <section class="page-heading">

                <div>
                    <p class="eyebrow">SKWELA COLLEGE ENROLLMENT SYSTEM</p>
                    <h1 id="pageTitle">Dashboard Overview</h1>
                    <p class="page-subtitle" id="pageSubtitle">
                        Monitor academic setup and recent system activity.
                    </p>
                </div>

                <div class="heading-actions">
                    <span class="date-chip" id="todayDate"></span>
                    <button class="btn btn-primary" id="refreshButton" type="button">
                        ↻ Refresh data
                    </button>
                </div>

            </section>

            <section id="overviewView" class="view-panel">

                <div class="status-strip" id="connectionStatus">
                    <span class="status-dot"></span>
                    <span>Connecting to database services...</span>
                </div>

                <div class="stats-grid">

                    <button class="stat-card" type="button" data-open-view="courses">
                        <span class="stat-top">
                            <span class="stat-icon blue">▤</span>
                            <span class="stat-caption">ACADEMIC SETUP</span>
                        </span>
                        <strong class="stat-value" id="coursesCount">—</strong>
                        <span class="stat-label">Total Courses</span>
                        <span class="stat-foot">View course records <b>→</b></span>
                    </button>

                    <button class="stat-card" type="button" data-open-view="subjects">
                        <span class="stat-top">
                            <span class="stat-icon cyan">▧</span>
                            <span class="stat-caption">CURRICULUM</span>
                        </span>
                        <strong class="stat-value" id="subjectsCount">—</strong>
                        <span class="stat-label">Total Subjects</span>
                        <span class="stat-foot">View subject records <b>→</b></span>
                    </button>

                    <button class="stat-card" type="button" data-open-view="sections">
                        <span class="stat-top">
                            <span class="stat-icon violet">▥</span>
                            <span class="stat-caption">CLASS GROUPS</span>
                        </span>
                        <strong class="stat-value" id="sectionsCount">—</strong>
                        <span class="stat-label">Total Sections</span>
                        <span class="stat-foot">View section records <b>→</b></span>
                    </button>

                    <button class="stat-card" type="button" data-open-view="academic_years">
                        <span class="stat-top">
                            <span class="stat-icon amber">◷</span>
                            <span class="stat-caption">SCHOOL CALENDAR</span>
                        </span>
                        <strong class="stat-value" id="academicYearsCount">—</strong>
                        <span class="stat-label">Academic Years</span>
                        <span class="stat-foot">View academic years <b>→</b></span>
                    </button>

                </div>

                <div class="content-grid">

                    <section class="panel">

                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow">SYSTEM MONITORING</p>
                                <h2>Recent Activity</h2>
                            </div>

                            <button class="text-button" type="button" data-open-view="activity_logs">
                                View all →
                            </button>
                        </div>

                        <div id="recentActivity" class="recent-activity">
                            <div class="loading-state">Loading activity records...</div>
                        </div>

                    </section>

                    <section class="panel">

                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow">RECORD SUMMARY</p>
                                <h2>Academic Setup</h2>
                            </div>
                            <span class="summary-mark">✓</span>
                        </div>

                        <div class="summary-list">
                            <div>
                                <span>Courses available</span>
                                <strong id="coursesSummary">—</strong>
                            </div>

                            <div>
                                <span>Subjects available</span>
                                <strong id="subjectsSummary">—</strong>
                            </div>

                            <div>
                                <span>Sections available</span>
                                <strong id="sectionsSummary">—</strong>
                            </div>

                            <div>
                                <span>Open academic years</span>
                                <strong id="openYearsSummary">—</strong>
                            </div>
                        </div>

                        <p class="summary-footnote">
                            Counts are loaded from the system API.
                        </p>

                    </section>

                </div>

            </section>

            <section id="recordsView" class="view-panel hidden">

                <div class="panel records-panel">

                    <div class="records-toolbar">

                        <div>
                            <p class="eyebrow" id="recordsEyebrow">DATABASE RECORDS</p>
                            <h2 id="recordsTitle">Records</h2>
                            <p class="records-description" id="recordsDescription">
                                Live records returned by the backend.
                            </p>
                        </div>

                        <div class="records-actions">

                            <button class="btn btn-primary" type="button" id="addCourseButton" hidden>
                                + Add Course
                            </button>

                            <button class="btn btn-primary" type="button" id="addSubjectButton" hidden>
                                + Add Subject
                            </button>

                            <button class="btn btn-primary" type="button" id="addSectionButton" hidden>
                                + Add Section
                            </button>

                            <button class="btn btn-primary" type="button" id="addScheduleButton" hidden>
                                + Add Schedule
                            </button>

                            <button class="btn btn-light" type="button" id="exportButton">
                                ↓ Export CSV
                            </button>

                            <button class="btn btn-primary" type="button" id="recordsRefreshButton">
                                ↻ Refresh
                            </button>

                        </div>

                    </div>

                    <div class="table-controls">

                        <label class="search-box">
                            <span>⌕</span>
                            <input type="search" id="searchInput" placeholder="Search records...">
                        </label>

                        <select id="activityDateFilter" aria-label="Filter activity date" hidden>
                            <option value="all">All dates</option>
                            <option value="today">Today</option>
                            <option value="7d">Last 7 days</option>
                            <option value="30d">Last 30 days</option>
                        </select>

                        <select id="statusFilter" aria-label="Filter by status">
                            <option value="all">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>

                    </div>

                    <div class="table-meta">
                        <span id="recordCount">Loading records...</span>
                        <span>All available fields are displayed below.</span>
                    </div>

                    <div class="table-wrap">

                        <table id="recordsTable">
                            <thead></thead>
                            <tbody>
                                <tr>
                                    <td class="empty-cell">Loading records...</td>
                                </tr>
                            </tbody>
                        </table>

                    </div>

                    <div id="recordFolders" class="admin-record-folders" hidden></div>

                    <div id="activityLogFolders" class="activity-log-folders" hidden></div>

                    <div class="table-footer">
                        <span id="tableFooterText">Live database view</span>
                        <button class="text-button" type="button" id="clearFiltersButton">
                            Clear search and filters
                        </button>
                    </div>

                </div>

            </section>

            <footer class="page-footer">
                <span>SKWELA COLLEGE ENROLLMENT SYSTEM</span>
                <span>Administrator Workspace</span>
            </footer>

        </div>

    </main>

</div>

<!-- COURSE FORM -->

<div class="modal-overlay" id="courseModal" hidden>

    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="courseModalTitle">

        <div class="modal-header">
            <div>
                <p class="eyebrow">COURSE MANAGEMENT</p>
                <h2 id="courseModalTitle">Add New Course</h2>
            </div>

            <button type="button" class="modal-close" data-close-modal="courseModal">×</button>
        </div>

        <form id="courseForm">

            <input type="hidden" id="courseId">

            <div class="form-grid">

                <div class="form-field">
                    <label for="courseCode">Course Code *</label>
                    <input type="text" id="courseCode" maxlength="30" required>
                </div>

                <div class="form-field">
                    <label for="durationYears">Duration (Years) *</label>
                    <input type="number" id="durationYears" min="1" max="10" value="4" required>
                </div>

                <div class="form-field full-field">
                    <label for="courseName">Course Name *</label>
                    <input type="text" id="courseName" maxlength="180" required>
                </div>

                <div class="form-field full-field">
                    <label for="courseDescription">Description</label>
                    <textarea id="courseDescription" rows="4"></textarea>
                </div>

            </div>

            <div class="modal-message" id="courseFormMessage" hidden></div>

            <div class="modal-actions">
                <button type="button" class="btn btn-light" data-close-modal="courseModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveCourseButton">Save Course</button>
            </div>

        </form>

    </div>

</div>

<!-- SUBJECT FORM -->

<div class="modal-overlay" id="subjectModal" hidden>

    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="subjectModalTitle">

        <div class="modal-header">
            <div>
                <p class="eyebrow">SUBJECT MANAGEMENT</p>
                <h2 id="subjectModalTitle">Add New Subject</h2>
            </div>

            <button type="button" class="modal-close" data-close-modal="subjectModal">×</button>
        </div>

        <form id="subjectForm">

            <input type="hidden" id="subjectId">

            <div class="form-grid">

                <div class="form-field full-field">
                    <label for="subjectCourse">Course *</label>
                    <select id="subjectCourse" required>
                        <option value="">Select Course</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="subjectSemester">Semester *</label>
                    <select id="subjectSemester" required>
                        <option value="">Select Semester</option>
                        <option value="1">First Semester</option>
                        <option value="2">Second Semester</option>
                        <option value="3">Summer</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="subjectYearLevel">Year Level *</label>
                    <select id="subjectYearLevel" required>
                        <option value="">Select Year Level</option>
                        <option value="1">First Year</option>
                        <option value="2">Second Year</option>
                        <option value="3">Third Year</option>
                        <option value="4">Fourth Year</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="subjectCode">Subject Code *</label>
                    <input type="text" id="subjectCode" maxlength="30" required>
                </div>

                <div class="form-field">
                    <label for="subjectUnits">Units *</label>
                    <input type="number" id="subjectUnits" min="0.25" max="20" step="0.25" value="3" required>
                </div>

                <div class="form-field full-field">
                    <label for="subjectName">Subject Name *</label>
                    <input type="text" id="subjectName" maxlength="180" required>
                </div>

                <div class="form-field full-field">
                    <label for="subjectDescription">Description</label>
                    <textarea id="subjectDescription" rows="4"></textarea>
                </div>

            </div>

            <div class="modal-message" id="subjectFormMessage" hidden></div>

            <div class="modal-actions">
                <button type="button" class="btn btn-light" data-close-modal="subjectModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveSubjectButton">Save Subject</button>
            </div>

        </form>

    </div>

</div>

<!-- SECTION FORM -->

<div class="modal-overlay" id="sectionModal" hidden>

    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="sectionModalTitle">

        <div class="modal-header">
            <div>
                <p class="eyebrow">SECTION MANAGEMENT</p>
                <h2 id="sectionModalTitle">Add New Section</h2>
            </div>

            <button type="button" class="modal-close" data-close-modal="sectionModal">×</button>
        </div>

        <form id="sectionForm">

            <input type="hidden" id="sectionId">

            <div class="form-grid">

                <div class="form-field full-field">
                    <label for="sectionAcademicYear">Academic Year *</label>
                    <select id="sectionAcademicYear" required>
                        <option value="">Select Academic Year</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="sectionSemester">Semester *</label>
                    <select id="sectionSemester" required>
                        <option value="">Select Semester</option>
                        <option value="1">First Semester</option>
                        <option value="2">Second Semester</option>
                        <option value="3">Summer</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="sectionCourse">Course *</label>
                    <select id="sectionCourse" required>
                        <option value="">Select Course</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="sectionYearLevel">Year Level *</label>
                    <select id="sectionYearLevel" required>
                        <option value="">Select Year Level</option>
                        <option value="1">First Year</option>
                        <option value="2">Second Year</option>
                        <option value="3">Third Year</option>
                        <option value="4">Fourth Year</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="sectionName">Section Name *</label>
                    <input type="text" id="sectionName" maxlength="30" placeholder="Example: 1B" required>
                </div>

                <div class="form-field full-field">
                    <label for="sectionCapacity">Student Capacity *</label>
                    <input type="number" id="sectionCapacity" min="1" max="45" value="45" required>
                    <small class="field-hint">Maximum capacity: 45 students.</small>
                </div>

            </div>

            <div class="modal-message" id="sectionFormMessage" hidden></div>

            <div class="modal-actions">
                <button type="button" class="btn btn-light" data-close-modal="sectionModal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveSectionButton">Save Section</button>
            </div>

        </form>

    </div>

</div>

<!-- CLASS SCHEDULE FORM -->

<div class="modal-overlay" id="scheduleModal" hidden>

    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="scheduleModalTitle">

        <div class="modal-header">
            <div>
                <p class="eyebrow">SCHEDULE MANAGEMENT</p>
                <h2 id="scheduleModalTitle">Add Class Schedule</h2>
            </div>

            <button type="button" class="modal-close" data-close-modal="scheduleModal">×</button>
        </div>

        <form id="scheduleForm">

            <input type="hidden" id="scheduleId">

            <div class="form-grid">

                <div class="form-field full-field">
                    <label for="scheduleSectionSubject">Section and Subject *</label>
                    <select id="scheduleSectionSubject" required>
                        <option value="">Select section and subject</option>
                    </select>
                    <small class="field-hint">
                        Only existing subject assignments can be scheduled.
                    </small>
                </div>

                <div class="form-field full-field">
                    <label for="scheduleDay">Day of the Week *</label>
                    <select id="scheduleDay" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="scheduleStartTime">Start Time *</label>
                    <input type="time" id="scheduleStartTime" required>
                </div>

                <div class="form-field">
                    <label for="scheduleEndTime">End Time *</label>
                    <input type="time" id="scheduleEndTime" required>
                </div>

                <div class="form-field full-field">
                    <label for="scheduleRoom">Classroom</label>
                    <input
                        type="text"
                        id="scheduleRoom"
                        maxlength="80"
                        placeholder="Example: Room 102"
                    >
                </div>

            </div>

            <div class="modal-message" id="scheduleFormMessage" hidden></div>

            <div class="modal-actions">
                <button type="button" class="btn btn-light" data-close-modal="scheduleModal">
                    Cancel
                </button>

                <button type="submit" class="btn btn-primary" id="saveScheduleButton">
                    Save Schedule
                </button>
            </div>

        </form>

    </div>

</div>

<!-- RECORD DETAILS -->

<div class="modal-overlay" id="detailsModal" hidden>

    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="detailsTitle">

        <div class="modal-header">
            <div>
                <p class="eyebrow">RECORD INFORMATION</p>
                <h2 id="detailsTitle">Details</h2>
            </div>

            <button type="button" class="modal-close" data-close-modal="detailsModal">×</button>
        </div>

        <div id="detailsContent" class="details-content"></div>

        <div class="modal-actions">
            <button type="button" class="btn btn-light" data-close-modal="detailsModal">
                Close
            </button>
        </div>

    </div>

</div>

<?php require __DIR__ . '/../includes/profile_modal.php'; ?>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script src="../assets/js/profile-modal.js" defer></script>
<script src="../assets/js/admin-dashboard.js?v=20261117-final"></script>

</body>
</html>
