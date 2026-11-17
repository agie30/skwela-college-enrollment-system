<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION["full_name"] ?? "Administrator", ENT_QUOTES, "UTF-8");
$adminEmail = htmlspecialchars($_SESSION["email"] ?? "", ENT_QUOTES, "UTF-8");
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
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="dashboard.php">
            <span class="brand-mark">S</span>
            <span class="brand-copy"><strong>SKWELA</strong><small>COLLEGE PORTAL</small></span>
        </a>

        <div class="nav-label">WORKSPACE</div>
        <nav class="main-nav" aria-label="Main navigation">
            <button class="nav-link active" data-view="overview"><span class="nav-icon">▦</span><span>Dashboard</span></button>
        </nav>

        <div class="nav-label">ACADEMIC SETUP</div>
        <nav class="main-nav" aria-label="Academic setup">
            <button class="nav-link" data-view="courses"><span class="nav-icon">▤</span><span>Courses</span></button>
            <button class="nav-link" data-view="subjects"><span class="nav-icon">▧</span><span>Subjects</span></button>
            <button class="nav-link" data-view="sections"><span class="nav-icon">▥</span><span>Sections</span></button>
            <button class="nav-link" data-view="academic_years"><span class="nav-icon">◷</span><span>Academic Years</span></button>
        </nav>

        <div class="nav-label">SYSTEM</div>
        <nav class="main-nav" aria-label="System">
            <button class="nav-link" data-view="activity_logs"><span class="nav-icon">≡</span><span>Activity Logs</span></button>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-note"><span class="online-dot"></span><span>System connected</span></div>
            <form method="POST" action="../../backend/routes/auth.php"><input type="hidden" name="action" value="logout"><button class="logout-link" type="submit"><span>↪</span> Sign out</button></form>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation">☰</button>
            <div class="breadcrumb"><span>Workspace</span><b>/</b><strong id="breadcrumbCurrent">Dashboard</strong></div>
            <div class="topbar-user">
                <div class="user-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                <div class="user-meta"><strong><?= $adminName ?></strong><small>Administrator</small></div>
            </div>
        </header>

        <div class="page-content">
            <section class="page-heading">
                <div>
                    <p class="eyebrow">SKWELA COLLEGE ENROLLMENT SYSTEM</p>
                    <h1 id="pageTitle">Dashboard Overview</h1>
                    <p class="page-subtitle" id="pageSubtitle">Monitor your academic setup and recent system activity.</p>
                </div>
                <div class="heading-actions">
                    <span class="date-chip" id="todayDate"></span>
                    <button class="btn btn-primary" id="refreshButton">↻ <span>Refresh data</span></button>
                </div>
            </section>

            <section id="overviewView" class="view-panel">
                <div class="status-strip" id="connectionStatus"><span class="status-dot"></span><span>Connecting to database services...</span></div>

                <div class="stats-grid">
                    <button class="stat-card" data-open-view="courses"><span class="stat-top"><span class="stat-icon blue">▤</span><span class="stat-caption">ACADEMIC SETUP</span></span><strong class="stat-value" id="coursesCount">—</strong><span class="stat-label">Total Courses</span><span class="stat-foot">View course records <b>→</b></span></button>
                    <button class="stat-card" data-open-view="subjects"><span class="stat-top"><span class="stat-icon cyan">▧</span><span class="stat-caption">CURRICULUM</span></span><strong class="stat-value" id="subjectsCount">—</strong><span class="stat-label">Total Subjects</span><span class="stat-foot">View subject records <b>→</b></span></button>
                    <button class="stat-card" data-open-view="sections"><span class="stat-top"><span class="stat-icon violet">▥</span><span class="stat-caption">CLASS GROUPS</span></span><strong class="stat-value" id="sectionsCount">—</strong><span class="stat-label">Total Sections</span><span class="stat-foot">View section records <b>→</b></span></button>
                    <button class="stat-card" data-open-view="academic_years"><span class="stat-top"><span class="stat-icon amber">◷</span><span class="stat-caption">SCHOOL CALENDAR</span></span><strong class="stat-value" id="academicYearsCount">—</strong><span class="stat-label">Academic Years</span><span class="stat-foot">View academic years <b>→</b></span></button>
                </div>

                <div class="content-grid">
                    <section class="panel activity-panel">
                        <div class="panel-heading"><div><p class="eyebrow">SYSTEM MONITORING</p><h2>Recent Activity</h2></div><button class="text-button" data-open-view="activity_logs">View all →</button></div>
                        <div id="recentActivity" class="recent-activity"><div class="loading-state">Loading activity records...</div></div>
                    </section>
                    <section class="panel summary-panel">
                        <div class="panel-heading"><div><p class="eyebrow">RECORD SUMMARY</p><h2>Academic Setup</h2></div><span class="summary-mark">✓</span></div>
                        <div class="summary-list">
                            <div><span>Courses available</span><strong id="coursesSummary">—</strong></div>
                            <div><span>Subjects available</span><strong id="subjectsSummary">—</strong></div>
                            <div><span>Sections available</span><strong id="sectionsSummary">—</strong></div>
                            <div><span>Open academic years</span><strong id="openYearsSummary">—</strong></div>
                        </div>
                        <p class="summary-footnote">Counts are loaded from the system API. No sample figures are used.</p>
                    </section>
                </div>
            </section>

            <section id="recordsView" class="view-panel hidden">
                <div class="panel records-panel">
                    <div class="records-toolbar">
                        <div><p class="eyebrow" id="recordsEyebrow">DATABASE RECORDS</p><h2 id="recordsTitle">Records</h2><p class="records-description" id="recordsDescription">Live records returned by the backend.</p></div>
                        <div class="records-actions">
                            <button class="btn btn-light" id="exportButton">↓ Export CSV</button>
                            <button class="btn btn-primary" id="recordsRefreshButton">↻ Refresh</button>
                        </div>
                    </div>
                    <div class="table-controls">
                        <label class="search-box"><span>⌕</span><input type="search" id="searchInput" placeholder="Search across all fields..."></label>
                        <select id="statusFilter" aria-label="Filter by status"><option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="open">Open</option><option value="closed">Closed</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select>
                    </div>
                    <div class="table-meta"><span id="recordCount">Loading records...</span><span>All available fields are displayed below.</span></div>
                    <div class="table-wrap"><table id="recordsTable"><thead></thead><tbody><tr><td class="empty-cell">Loading records...</td></tr></tbody></table></div>
                    <div class="table-footer"><span id="tableFooterText">Live database view</span><button class="text-button" id="clearFiltersButton">Clear search and filters</button></div>
                </div>
            </section>

            <footer class="page-footer"><span>SKWELA COLLEGE ENROLLMENT SYSTEM</span><span>Administrator Workspace</span></footer>
        </div>
    </main>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>
