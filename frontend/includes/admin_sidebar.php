<?php
$activeAdminPage = isset($activeAdminPage) ? (string) $activeAdminPage : '';
$adminNav = [
    'overview' => 'Dashboard',
    'courses' => 'Courses',
    'subjects' => 'Subjects',
    'sections' => 'Sections',
    'schedules' => 'Class Schedules',
    'academic_years' => 'Academic Years',
    'student_accounts' => 'Student Accounts',
    'registrars' => 'Registrar Accounts',
    'document_tracking' => 'Document Tracking',
    'activity_logs' => 'Activity Logs',
];
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="dashboard.php">
        <span class="brand-logo" aria-hidden="true">S</span>
        <span class="brand-copy"><strong>SKWELA</strong><span>COLLEGE PORTAL</span></span>
    </a>
    <div class="menu-label"><span class="workspace-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>WORKSPACE</div>
    <nav class="main-nav" aria-label="Workspace">
        <a class="nav-link <?= $activeAdminPage === 'overview' ? 'active' : '' ?>" href="dashboard.php"><span class="nav-icon">▦</span><span>Dashboard</span></a>
    </nav>
    <div class="nav-label">ACADEMIC SETUP</div>
    <nav class="main-nav" aria-label="Academic setup">
        <a class="nav-link <?= $activeAdminPage === 'courses' ? 'active' : '' ?>" href="dashboard.php?view=courses"><span class="nav-icon">▤</span><span>Courses</span></a>
        <a class="nav-link <?= $activeAdminPage === 'subjects' ? 'active' : '' ?>" href="dashboard.php?view=subjects"><span class="nav-icon">▧</span><span>Subjects</span></a>
        <a class="nav-link <?= $activeAdminPage === 'sections' ? 'active' : '' ?>" href="dashboard.php?view=sections"><span class="nav-icon">▥</span><span>Sections</span></a>
        <a class="nav-link <?= $activeAdminPage === 'schedules' ? 'active' : '' ?>" href="dashboard.php?view=schedules"><span class="nav-icon">◷</span><span>Class Schedules</span></a>
        <a class="nav-link <?= $activeAdminPage === 'academic_years' ? 'active' : '' ?>" href="academic_years.php"><span class="nav-icon">◴</span><span>Academic Years</span></a>
    </nav>
    <div class="nav-label">ACCOUNT MANAGEMENT</div>
    <nav class="main-nav" aria-label="Account management">
        <a class="nav-link <?= $activeAdminPage === 'student_accounts' ? 'active' : '' ?>" href="student_accounts.php"><span class="nav-icon">♙</span><span>Student Accounts</span></a>
        <a class="nav-link <?= $activeAdminPage === 'registrars' ? 'active' : '' ?>" href="registrars.php"><span class="nav-icon">♧</span><span>Registrar Accounts</span></a>
    </nav>
    <div class="nav-label">SYSTEM</div>
    <nav class="main-nav" aria-label="System">
        <a class="nav-link <?= $activeAdminPage === 'document_tracking' ? 'active' : '' ?>" href="document_tracking.php"><span class="nav-icon">▧</span><span>Document Tracking</span></a>
        <a class="nav-link <?= $activeAdminPage === 'activity_logs' ? 'active' : '' ?>" href="dashboard.php?view=activity_logs"><span class="nav-icon">≡</span><span>Activity Logs</span></a>
    </nav>
    <div class="sidebar-bottom">
        <div class="sidebar-note"><span class="online-dot"></span><span>System workspace</span></div>
        <form method="POST" action="../../backend/routes/auth.php">
            <input type="hidden" name="action" value="logout">
            <button class="logout-link" type="submit"><span>↪</span> Sign out</button>
        </form>
    </div>
</aside>
