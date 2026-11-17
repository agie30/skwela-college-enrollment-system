<?php
$adminShellTitle = (string)($adminShellTitle ?? 'Workspace');
$adminShellActive = (string)($adminShellActive ?? '');
$adminShellName = htmlspecialchars($sharedProfileName ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$adminShellEmail = htmlspecialchars($sharedProfileEmail ?? '', ENT_QUOTES, 'UTF-8');
$adminShellInitials = htmlspecialchars($sharedProfileInitials ?? 'A', ENT_QUOTES, 'UTF-8');
$adminShellImage = htmlspecialchars($sharedProfileImageUrl ?? '', ENT_QUOTES, 'UTF-8');
$adminShellNav = [
    'dashboard' => ['Dashboard', 'dashboard.php', '▦'],
    'courses' => ['Courses', 'dashboard.php', '▤'],
    'subjects' => ['Subjects', 'dashboard.php', '▧'],
    'sections' => ['Sections', 'dashboard.php', '▥'],
    'schedules' => ['Class Schedules', 'dashboard.php', '◷'],
    'academic_years' => ['Academic Years', 'academic_years.php', '◴'],
    'student_accounts' => ['Student Accounts', 'student_accounts.php', '♙'],
    'registrar_accounts' => ['Registrar Accounts', 'registrars.php', '♧'],
    'document_tracking' => ['Document Tracking', 'document_tracking.php', '▧'],
    'activity_logs' => ['Activity Logs', 'dashboard.php', '≡'],
];
?>
<div class="app-shell admin-subpage-shell">
    <aside class="sidebar" id="adminPageSidebar" aria-label="Admin navigation">
        <a class="brand" href="dashboard.php">
            <span class="brand-logo" aria-hidden="true">S</span>
            <span class="brand-copy"><strong>SKWELA</strong><span>COLLEGE PORTAL</span></span>
        </a>

        <div class="menu-label">
            <span class="workspace-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
            WORKSPACE
        </div>
        <nav class="main-nav" aria-label="Workspace">
            <a class="nav-link <?= $adminShellActive === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                <span class="nav-icon">▦</span><span>Dashboard</span>
            </a>
        </nav>

        <div class="nav-label">ACADEMIC SETUP</div>
        <nav class="main-nav" aria-label="Academic setup">
            <a class="nav-link" href="dashboard.php"><span class="nav-icon">▤</span><span>Courses</span></a>
            <a class="nav-link" href="dashboard.php"><span class="nav-icon">▧</span><span>Subjects</span></a>
            <a class="nav-link" href="dashboard.php"><span class="nav-icon">▥</span><span>Sections</span></a>
            <a class="nav-link" href="dashboard.php"><span class="nav-icon">◷</span><span>Class Schedules</span></a>
            <a class="nav-link <?= $adminShellActive === 'academic_years' ? 'active' : '' ?>" href="academic_years.php" <?= $adminShellActive === 'academic_years' ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon">◴</span><span>Academic Years</span>
            </a>
        </nav>

        <div class="nav-label">ACCOUNT MANAGEMENT</div>
        <nav class="main-nav" aria-label="Account management">
            <a class="nav-link <?= $adminShellActive === 'student_accounts' ? 'active' : '' ?>" href="student_accounts.php" <?= $adminShellActive === 'student_accounts' ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon">♙</span><span>Student Accounts</span>
            </a>
            <a class="nav-link <?= $adminShellActive === 'registrar_accounts' ? 'active' : '' ?>" href="registrars.php" <?= $adminShellActive === 'registrar_accounts' ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon">♧</span><span>Registrar Accounts</span>
            </a>
        </nav>

        <div class="nav-label">SYSTEM</div>
        <nav class="main-nav" aria-label="System">
            <a class="nav-link <?= $adminShellActive === 'document_tracking' ? 'active' : '' ?>" href="document_tracking.php" <?= $adminShellActive === 'document_tracking' ? 'aria-current="page"' : '' ?>>
                <span class="nav-icon">▧</span><span>Document Tracking</span>
            </a>
            <a class="nav-link" href="dashboard.php"><span class="nav-icon">≡</span><span>Activity Logs</span></a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-note"><span class="online-dot"></span><span>System workspace</span></div>
            <div class="student-account-info">
                <strong><?= $adminShellName ?></strong>
                <?php if ($adminShellEmail !== ''): ?><small><?= $adminShellEmail ?></small><?php endif; ?>
                <small>Administrator Account</small>
            </div>
            <form method="POST" action="../../backend/routes/auth.php">
                <input type="hidden" name="action" value="logout">
                <button class="logout-link" type="submit"><span>↪</span> Sign out</button>
            </form>
        </div>
    </aside>

    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

    <div class="main-area admin-subpage-main">
        <header class="admin-global-topbar">
            <button class="admin-page-menu-toggle" id="adminPageMenuToggle" type="button" aria-label="Open navigation" aria-expanded="false">☰</button>
            <div class="admin-global-breadcrumb">
                <span>Workspace</span><b>/</b><strong><?= htmlspecialchars($adminShellTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <button type="button" class="shared-profile-trigger" onclick="openSharedProfile()" aria-label="Open administrator profile">
                <?php if ($adminShellImage !== ''): ?>
                    <img class="shared-profile-avatar" src="<?= $adminShellImage ?>" alt="Administrator profile picture">
                <?php else: ?>
                    <span class="shared-profile-avatar"><?= $adminShellInitials ?></span>
                <?php endif; ?>
                <span class="shared-profile-trigger-name"><?= $adminShellName ?></span>
            </button>
        </header>
        <div class="admin-subpage-content">
            <a href="dashboard.php" class="admin-page-back">← Back</a>
