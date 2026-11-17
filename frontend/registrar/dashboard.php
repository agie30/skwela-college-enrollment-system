<?php
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "registrar"
) {
    header("Location: ../../index.php");
    exit();
}

require_once __DIR__ . '/../includes/profile_bootstrap.php';
$registrarName = $sharedProfileName;
$registrarEmail = $sharedProfileEmail;
$registrarId = $sharedProfileUserId;
$registrarProfileImage = $sharedProfileImageUrl;
$registrarInitials = $sharedProfileInitials;
$profileUploadMessage = $sharedProfileUploadMessage;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar Dashboard | Skwela College Enrollment System</title>
<link rel="stylesheet" href="../assets/css/registrar-students.css">
<link rel="stylesheet" href="../assets/css/profile-modal.css">

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

button, input, select {
    font: inherit;
}

button {
    cursor: pointer;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 255px;
    background: #102a56;
    color: white;
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    padding: 25px 17px;
    display: flex;
    flex-direction: column;
    z-index: 10;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 5px 10px 30px;
}

.brand-logo {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: linear-gradient(145deg, #60a5fa, #2563eb);
    color: #ffffff;
    font-size: 23px;
    font-weight: 800;
    letter-spacing: -1px;
    box-shadow: 0 5px 14px rgba(37, 99, 235, .25);
    border: 1px solid rgba(255,255,255,.2);
}

.brand-copy {
    min-width: 0;
    line-height: 1.35;
}

.brand-copy strong {
    display: block;
    color: #ffffff;
    font-size: 18px;
    font-weight: 750;
    letter-spacing: .5px;
}

.brand-copy span {
    display: block;
    margin-top: 3px;
    color: #a9c8f3;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 1.7px;
}

.menu-label {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #a9bdd9;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.7px;
    margin: 4px 10px 13px;
}

.workspace-mark {
    width: 14px;
    height: 14px;
    display: grid;
    grid-template-columns: repeat(2, 5px);
    grid-template-rows: repeat(2, 5px);
    gap: 2px;
}

.workspace-mark i {
    display: block;
    border-radius: 1px;
    background: #72b5ff;
}

.nav-btn {
    width: 100%;
    min-height: 46px;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 8px 12px;
    margin-bottom: 6px;
    border: 1px solid transparent;
    border-radius: 9px;
    text-align: left;
    background: transparent;
    color: #dbeafe;
    font-size: 13px;
    font-weight: 500;
    transition: background .18s ease, border-color .18s ease, color .18s ease;
}

.nav-btn span {
    width: 29px;
    height: 29px;
    flex: 0 0 29px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    border-radius: 8px;
    background: rgba(255,255,255,.07);
    color: #b9d8ff;
    font-size: 14px;
}

.nav-btn:hover {
    background: rgba(96,165,250,.12);
    border-color: rgba(147,197,253,.12);
    color: #ffffff;
}

.nav-btn.active {
    background: #24549a;
    border-color: rgba(147,197,253,.18);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(3, 17, 43, .12);
}

.nav-btn.active span {
    background: rgba(255,255,255,.16);
    color: #ffffff;
}

.sidebar-bottom {
    margin-top: auto;
}

.profile-small {
    border-top: 1px solid rgba(255,255,255,.15);
    padding: 18px 8px 12px;
    margin-bottom: 10px;
}

.profile-small strong {
    display: block;
    font-size: 13px;
}

.profile-small small {
    display: block;
    color: #b7c9e0;
    margin-top: 5px;
    font-size: 11px;
    overflow-wrap: anywhere;
}

.logout-btn {
    display: block;
    text-decoration: none;
    text-align: center;
    background: #25436d;
    color: white;
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
}

.logout-btn:hover {
    background: #b83232;
}

.main {
    flex: 1;
    margin-left: 255px;
    min-width: 0;
}

.topbar {
    height: 72px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 30px;
    position: sticky;
    top: 0;
    z-index: 5;
}

.topbar-title {
    font-weight: 700;
    color: #102a56;
    font-size: 16px;
}

.topbar-profile {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: 0;
    background: transparent;
    color: #102a56;
    padding: 5px 0 5px 10px;
    font-size: 14px;
    font-weight: 700;
}

.profile-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 38px;
    background: #dbeafe;
    color: #174ea6;
    font-size: 13px;
    font-weight: 700;
    border: 2px solid #e2e8f0;
}

.profile-avatar.large {
    width: 92px;
    height: 92px;
    flex-basis: 92px;
    font-size: 25px;
    border-width: 3px;
}

.profile-modal-heading {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 4px 0 22px;
    margin-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.profile-modal-heading h3 { color: #102a56; font-size: 18px; margin-bottom: 5px; }
.profile-modal-heading p { color: #64748b; font-size: 13px; }

.profile-upload-form {
    margin-top: 22px;
    padding: 18px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
}

.profile-upload-form label {
    display: block;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 9px;
}

.profile-upload-form input[type="file"] {
    display: block;
    width: 100%;
    padding: 10px;
    border: 1px solid #dbe2ea;
    border-radius: 7px;
    background: white;
    margin-bottom: 12px;
}

.profile-upload-help { color: #64748b; font-size: 11px; margin-top: 9px; }
.profile-upload-message {
    margin-bottom: 15px;
    padding: 11px 13px;
    border-radius: 8px;
    background: #ecfdf5;
    color: #166534;
    font-size: 12px;
}
.profile-upload-message.error { background: #fef2f2; color: #991b1b; }

.content {
    padding: 30px;
    max-width: 1600px;
    margin: auto;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.page-header h1 {
    font-size: 25px;
    color: #102a56;
    margin-bottom: 8px;
}

.page-header p {
    color: #64748b;
    font-size: 14px;
}

.refresh-btn {
    border: 1px solid #dbe2ea;
    background: white;
    color: #334155;
    border-radius: 8px;
    padding: 10px 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 23px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15,23,42,.04);
    border: 1px solid #edf0f5;
}

.stat-label {
    color: #64748b;
    font-size: 13px;
}

.stat-number {
    font-size: 30px;
    color: #102a56;
    font-weight: 700;
    margin-top: 13px;
}

.stat-note {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 7px;
}

.panel {
    background: white;
    border: 1px solid #edf0f5;
    border-radius: 12px;
    padding: 23px;
    margin-bottom: 23px;
    box-shadow: 0 2px 10px rgba(15,23,42,.04);
}

.panel-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.panel-heading h2 {
    font-size: 17px;
    color: #102a56;
}

.panel-heading p {
    color: #64748b;
    font-size: 12px;
    margin-top: 5px;
}

.primary-btn {
    border: none;
    border-radius: 7px;
    background: #174ea6;
    color: white;
    padding: 10px 15px;
    font-size: 13px;
}

.primary-btn:hover {
    background: #123b80;
}

.secondary-btn {
    border: 1px solid #dbe2ea;
    border-radius: 7px;
    background: white;
    color: #334155;
    padding: 9px 13px;
    font-size: 12px;
}

.success-btn {
    border: none;
    background: #15803d;
    color: white;
    padding: 8px 11px;
    border-radius: 6px;
    font-size: 12px;
}

.warning-btn {
    border: none;
    background: #d97706;
    color: white;
    padding: 8px 11px;
    border-radius: 6px;
    font-size: 12px;
}

.danger-btn {
    border: none;
    background: #dc2626;
    color: white;
    padding: 8px 11px;
    border-radius: 6px;
    font-size: 12px;
}

.action-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
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
    border-radius: 8px;
    padding: 11px 13px;
    outline: none;
    background: white;
    color: #334155;
    font-size: 13px;
}

.search-input {
    min-width: 230px;
    flex: 1;
}

.filter-select:focus,
.search-input:focus {
    border-color: #174ea6;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    padding: 14px 12px;
    text-align: left;
    white-space: nowrap;
}

td {
    padding: 15px 12px;
    font-size: 13px;
    border-bottom: 1px solid #edf0f5;
    color: #334155;
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
    white-space: nowrap;
}

.status.pending {
    background: #fef3c7;
    color: #92400e;
}

.status.under_review {
    background: #dbeafe;
    color: #1e40af;
}

.status.approved,
.status.verified {
    background: #dcfce7;
    color: #166534;
}

.status.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.status.default {
    background: #e2e8f0;
    color: #475569;
}

.empty-state {
    text-align: center;
    padding: 35px 15px;
    color: #94a3b8;
    font-size: 13px;
}

.connection {
    padding: 11px 14px;
    margin-bottom: 20px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    color: #1e40af;
    font-size: 12px;
    overflow-wrap: anywhere;
}

.connection.error {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 13px;
}

.quick-link {
    border: 1px solid #e2e8f0;
    padding: 17px;
    border-radius: 9px;
    background: white;
    cursor: pointer;
    text-align: left;
}

.quick-link:hover {
    border-color: #174ea6;
    background: #f8fbff;
}

.quick-link strong {
    color: #102a56;
    display: block;
    font-size: 13px;
    margin-bottom: 7px;
}

.quick-link span {
    color: #64748b;
    font-size: 12px;
}

.view {
    display: none;
}

.view.active {
    display: block;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 30;
    background: rgba(15,23,42,.58);
    padding: 20px;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-box {
    background: white;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 25px;
    border-radius: 13px;
}

.modal-box h2 {
    color: #102a56;
    font-size: 19px;
}

.modal-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.close-btn {
    background: #f1f5f9;
    border: none;
    border-radius: 7px;
    padding: 8px 11px;
    color: #334155;
}

.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.detail-item {
    padding: 13px;
    background: #f8fafc;
    border-radius: 8px;
    overflow-wrap: anywhere;
}

.detail-item small {
    display: block;
    color: #64748b;
    font-size: 11px;
    margin-bottom: 6px;
}

.detail-item strong {
    font-size: 13px;
    color: #1e293b;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 22px;
}

.preview-frame {
    width: 100%;
    height: 65vh;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.toast {
    position: fixed;
    right: 25px;
    bottom: 25px;
    z-index: 60;
    background: #102a56;
    color: white;
    padding: 14px 19px;
    border-radius: 8px;
    font-size: 13px;
    box-shadow: 0 5px 20px rgba(0,0,0,.15);
    display: none;
    max-width: 350px;
}

.toast.show {
    display: block;
}

.toast.error {
    background: #991b1b;
}

.mobile-menu {
    display: none;
    border: none;
    background: transparent;
    font-size: 23px;
    color: #102a56;
}

@media(max-width: 1050px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 750px) {
    .sidebar {
        transform: translateX(-100%);
        transition: .25s;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .main {
        margin-left: 0;
    }

    .mobile-menu {
        display: block;
    }

    .topbar {
        padding: 0 17px;
    }

    .content {
        padding: 20px 15px;
    }

    .quick-links {
        grid-template-columns: 1fr;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 16px;
    }
}

@media(max-width: 420px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* =========================================================
   SKWELA REGISTRAR DASHBOARD — COMPLETE VISUAL REFINEMENT
   Keeps existing PHP, API calls, IDs, and dashboard actions.
   ========================================================= */

:root {
    --skwela-navy: #102a56;
    --skwela-blue: #24549a;
    --skwela-bright: #2563eb;
    --skwela-sky: #60a5fa;
    --skwela-text: #172b4d;
    --skwela-muted: #64748b;
    --skwela-border: #e5ebf3;
    --skwela-surface: #ffffff;
    --skwela-page: #f4f7fb;
}

html {
    scroll-behavior: smooth;
}

body {
    background: var(--skwela-page);
    color: var(--skwela-text);
    -webkit-font-smoothing: antialiased;
}

button, input, select, textarea {
    font-family: inherit;
}

button {
    transition: background .18s ease, border-color .18s ease, color .18s ease,
                box-shadow .18s ease, transform .18s ease;
}

.sidebar {
    background: linear-gradient(180deg, #102a56 0%, #112f60 100%);
    box-shadow: 5px 0 24px rgba(15, 42, 86, .08);
    padding: 25px 16px 18px;
}

.brand {
    padding: 5px 9px 32px;
}

.brand-logo {
    position: relative;
    overflow: hidden;
    background: linear-gradient(145deg, #73b5ff 0%, #3478e5 58%, #1d4ed8 100%);
    box-shadow: 0 7px 18px rgba(37, 99, 235, .28);
}

.brand-logo::after {
    content: "";
    position: absolute;
    width: 27px;
    height: 27px;
    right: -12px;
    bottom: -13px;
    border: 2px solid rgba(255,255,255,.28);
    border-radius: 8px;
    transform: rotate(35deg);
}

.brand-copy strong {
    letter-spacing: .8px;
}

.menu-label {
    margin: 2px 11px 14px;
}

.nav-btn {
    min-height: 47px;
    padding: 8px 11px;
    margin-bottom: 5px;
    border-radius: 10px;
    letter-spacing: .05px;
}

.nav-btn span {
    border-radius: 8px;
}

.nav-btn.active {
    background: linear-gradient(100deg, #2a5da8, #24549a);
    box-shadow: 0 5px 13px rgba(3, 17, 43, .18);
}

.sidebar-bottom {
    padding-top: 15px;
    border-top: 1px solid rgba(219,234,254,.14);
}

.logout-btn {
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 9px;
    background: rgba(255,255,255,.10);
    padding: 12px;
    font-weight: 600;
}

.logout-btn:hover {
    background: #a8323e;
    transform: translateY(-1px);
}

.main {
    background: var(--skwela-page);
}

.topbar {
    height: 72px;
    padding: 0 32px;
    background: rgba(255,255,255,.96);
    border-bottom: 1px solid var(--skwela-border);
    box-shadow: 0 2px 10px rgba(16,42,86,.025);
    backdrop-filter: blur(10px);
}

.topbar-title {
    font-size: 15px;
    letter-spacing: .1px;
}

.topbar-profile {
    padding: 6px 10px 6px 7px;
    border: 1px solid transparent;
    border-radius: 30px;
    cursor: pointer;
}

.topbar-profile:hover {
    background: #f5f8fd;
    border-color: #e3ebf7;
}

.profile-avatar {
    border: 2px solid #e7eef8;
    box-shadow: 0 2px 6px rgba(16,42,86,.08);
}

.content {
    width: 100%;
    padding: 30px 32px 42px;
}

.connection {
    border-radius: 10px;
    padding: 12px 15px;
    box-shadow: 0 2px 8px rgba(37,99,235,.035);
}

.page-header {
    align-items: center;
    margin-bottom: 24px;
}

.page-header h1 {
    font-size: 25px;
    line-height: 1.25;
    letter-spacing: -.45px;
    margin-bottom: 7px;
}

.page-header p {
    line-height: 1.55;
}

.refresh-btn,
.secondary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 39px;
    border: 1px solid #dce5f0;
    border-radius: 9px;
    background: #fff;
    color: #344967;
    font-size: 12px;
    font-weight: 600;
    padding: 9px 14px;
}

.refresh-btn:hover,
.secondary-btn:hover {
    border-color: #9bb9e5;
    background: #f7faff;
    color: #174ea6;
    box-shadow: 0 3px 9px rgba(23,78,166,.07);
}

.stats-grid {
    gap: 16px;
    margin-bottom: 23px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    padding: 21px 22px;
    border: 1px solid #e8edf5;
    border-radius: 13px;
    box-shadow: 0 4px 14px rgba(16,42,86,.045);
    transition: transform .18s ease, box-shadow .18s ease;
}

.stat-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #6aa5ed;
    opacity: .9;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16,42,86,.075);
}

.stat-label {
    font-size: 12px;
    font-weight: 600;
    color: #657792;
}

.stat-number {
    margin-top: 11px;
    font-size: 29px;
    line-height: 1.1;
    letter-spacing: -.7px;
    color: var(--skwela-navy);
}

.stat-note {
    margin-top: 8px;
    line-height: 1.45;
}

.panel {
    padding: 23px;
    margin-bottom: 21px;
    border: 1px solid #e7edf5;
    border-radius: 13px;
    box-shadow: 0 4px 15px rgba(16,42,86,.04);
}

.panel-heading {
    margin-bottom: 19px;
}

.panel-heading h2 {
    font-size: 16px;
    letter-spacing: -.15px;
}

.panel-heading p {
    line-height: 1.5;
}

.quick-links {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.quick-link {
    position: relative;
    min-height: 102px;
    padding: 17px 17px 16px;
    border: 1px solid #e3eaf4;
    border-radius: 10px;
    background: #fff;
}

.quick-link::before {
    content: "";
    position: absolute;
    left: 0;
    top: 13px;
    bottom: 13px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: #7eb2f1;
    opacity: 0;
    transition: opacity .18s ease;
}

.quick-link:hover {
    border-color: #b8cdec;
    background: #fbfdff;
    box-shadow: 0 5px 14px rgba(23,78,166,.06);
    transform: translateY(-1px);
}

.quick-link:hover::before {
    opacity: 1;
}

.quick-link strong {
    font-size: 12px;
    line-height: 1.5;
    margin-bottom: 8px;
}

.quick-link span {
    display: block;
    line-height: 1.5;
}

.toolbar {
    align-items: center;
    gap: 10px;
    margin-bottom: 17px;
}

.search-input,
.filter-select {
    min-height: 41px;
    border: 1px solid #dce5f0;
    border-radius: 9px;
    padding: 10px 13px;
    color: #334967;
    background-color: #fff;
    transition: border-color .18s ease, box-shadow .18s ease;
}

.search-input::placeholder {
    color: #9aa9bd;
}

.search-input:focus,
.filter-select:focus {
    border-color: #6c9fe1;
    box-shadow: 0 0 0 3px rgba(37,99,235,.09);
    outline: none;
}

.table-wrap {
    border: 1px solid #edf1f6;
    border-radius: 10px;
    background: #fff;
}

table {
    min-width: 760px;
    border-collapse: separate;
    border-spacing: 0;
}

th {
    padding: 14px 14px;
    background: #f6f8fc;
    color: #64748b;
    font-size: 10px;
    letter-spacing: .7px;
    border-bottom: 1px solid #e8edf4;
}

th:first-child {
    border-top-left-radius: 9px;
}

th:last-child {
    border-top-right-radius: 9px;
}

td {
    padding: 14px;
    color: #354761;
    font-size: 12px;
    line-height: 1.5;
    border-bottom: 1px solid #edf1f6;
}

tbody tr:last-child td {
    border-bottom: 0;
}

tbody tr:hover {
    background: #f8fbff;
}

.status {
    padding: 5px 9px;
    font-size: 10px;
    letter-spacing: .1px;
}

.primary-btn,
.success-btn,
.warning-btn,
.danger-btn {
    min-height: 34px;
    border-radius: 8px;
    font-weight: 600;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.primary-btn {
    background: #24549a;
    padding: 10px 16px;
}

.primary-btn:hover {
    background: #173f7b;
    box-shadow: 0 4px 10px rgba(23,78,166,.16);
    transform: translateY(-1px);
}

.success-btn:hover,
.warning-btn:hover,
.danger-btn:hover {
    filter: brightness(.95);
    transform: translateY(-1px);
}

.action-group {
    align-items: center;
    gap: 7px;
}

.modal {
    background: rgba(9, 25, 51, .58);
    backdrop-filter: blur(3px);
}

.modal-box {
    border: 1px solid rgba(226,232,240,.8);
    border-radius: 15px;
    padding: 26px;
    box-shadow: 0 20px 55px rgba(15,23,42,.2);
}

.modal-box h2 {
    letter-spacing: -.25px;
}

.close-btn {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e7edf5;
    border-radius: 9px;
}

.close-btn:hover {
    background: #eaf1fb;
    color: #174ea6;
}

.detail-grid {
    gap: 12px;
}

.detail-item {
    border: 1px solid #e9eef5;
    border-radius: 9px;
    padding: 14px;
}

.toast {
    border-radius: 10px;
    box-shadow: 0 10px 28px rgba(15,23,42,.2);
}

#students .sr-summary-grid,
#students .sr-detail-summary {
    gap: 16px;
}

#students .sr-information-grid {
    gap: 14px;
}

#students .sr-information-item {
    padding: 17px;
    border: 1px solid #edf1f6;
    border-radius: 10px;
    background: #f8fafd;
}

#students .sr-information-item span {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .55px;
    text-transform: uppercase;
}

#students .sr-information-item strong {
    font-size: 13px;
    line-height: 1.55;
}

#students .sr-document-stat {
    border: 1px solid #e6edf5;
    background: #f8fafd;
    padding: 8px 12px;
    font-weight: 600;
}

#students .sr-page-message {
    border-radius: 9px;
}

#students .sr-action-group {
    gap: 7px;
}

@media (max-width: 1100px) {
    .content {
        padding: 25px 23px 35px;
    }

    .quick-links {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 750px) {
    .sidebar {
        box-shadow: 12px 0 30px rgba(15,42,86,.2);
    }

    .topbar {
        height: 66px;
        padding: 0 15px;
    }

    .topbar-title {
        font-size: 13px;
    }

    .topbar-profile {
        gap: 7px;
        font-size: 12px;
        max-width: 52%;
    }

    .topbar-profile > span:last-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .profile-avatar {
        width: 34px;
        height: 34px;
        flex-basis: 34px;
    }

    .content {
        padding: 20px 14px 30px;
    }

    .page-header h1 {
        font-size: 21px;
    }

    .panel {
        padding: 17px;
        border-radius: 11px;
    }

    .quick-links {
        grid-template-columns: 1fr;
    }

    .quick-link {
        min-height: auto;
    }

    .toolbar {
        align-items: stretch;
    }

    .search-input,
    .filter-select {
        width: 100%;
        min-width: 0;
    }

    .modal {
        padding: 12px;
    }

    .modal-box {
        padding: 19px;
        max-height: 94vh;
    }
}

@media (max-width: 420px) {
    .topbar-title {
        max-width: 145px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .page-header {
        align-items: stretch;
    }

    .refresh-btn {
        align-self: flex-start;
    }

    .stat-card {
        padding: 17px;
    }

    .profile-modal-heading {
        align-items: flex-start;
        gap: 12px;
    }

    .profile-avatar.large {
        width: 70px;
        height: 70px;
        flex-basis: 70px;
    }
}



/* =========================================
   DOCUMENT VERIFICATION: STUDENT DIRECTORY
========================================= */
#documentStudentListView[hidden],
#documentStudentDetailView[hidden] {
    display: none !important;
}

.document-directory-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 22px;
}

.document-directory-stat {
    min-width: 0;
    padding: 18px 20px;
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 11px;
    box-shadow: 0 2px 9px rgba(15, 23, 42, .035);
}

.document-directory-stat span {
    display: block;
    margin-bottom: 8px;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
}

.document-directory-stat strong {
    display: block;
    color: #102a56;
    font-size: 24px;
    font-weight: 750;
}

.document-section-group {
    margin: 10px 12px 0;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
}
.document-course-folder { margin-top: 14px; border:1px solid #dce7f3; border-radius:12px; background:#fff; overflow:hidden; }
.document-course-folder > summary { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:15px 18px; cursor:pointer; background:#f2f6fc; color:#15345f; font-size:13px; font-weight:800; }
.document-course-folder > summary::-webkit-details-marker { display:none; }
.document-course-folder > summary::after { content:'⌄'; color:#2467b7; font-size:18px; transition:transform .18s; }
.document-course-folder[open] > summary::after { transform:rotate(180deg); }
.document-course-content { padding:0 0 12px; }
.document-folder-count { color:#8190a4; font-size:10px; font-weight:700; white-space:nowrap; }
.document-section-group > summary { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:15px 18px; cursor:pointer; background:#f9fbfe; }
.document-section-group > summary::-webkit-details-marker { display:none; }
.document-section-group > summary::after { content:'⌄'; color:#2467b7; font-size:17px; transition:transform .18s; }
.document-section-group[open] > summary::after { transform:rotate(180deg); }
.document-section-group .document-section-title { border:0; padding:0; background:transparent; }
.document-student-number { display:inline-flex; align-items:center; justify-content:center; width:25px; height:25px; margin-right:8px; border-radius:7px; background:#eaf2ff; color:#245e9e; font-size:10px; font-weight:800; }

.document-section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 19px;
    background: #f3f7fc;
    border-bottom: 1px solid #e2e8f0;
}

.document-section-title strong {
    display: block;
    color: #15345f;
    font-size: 13px;
}

.document-section-title small {
    display: block;
    margin-top: 5px;
    color: #64748b;
    font-size: 11px;
}

.document-student-list {
    display: flex;
    flex-direction: column;
}

.document-student-row {
    display: grid;
    grid-template-columns: minmax(170px, 1.5fr) minmax(100px, .8fr) minmax(100px, .8fr) minmax(130px, 1fr) auto;
    align-items: center;
    gap: 16px;
    padding: 16px 19px;
    border-bottom: 1px solid #edf1f6;
}

.document-student-row:last-child {
    border-bottom: 0;
}

.document-student-row:hover {
    background: #fafcff;
}

.document-student-name,
.document-student-meta {
    min-width: 0;
}

.document-student-name strong,
.document-student-meta strong {
    display: block;
    color: #1e3557;
    font-size: 12px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.document-student-name small,
.document-student-meta small {
    display: block;
    margin-top: 4px;
    color: #718096;
    font-size: 10px;
    line-height: 1.4;
}

.document-count-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.document-count-pill {
    display: inline-flex;
    align-items: center;
    padding: 5px 8px;
    border-radius: 20px;
    background: #e2e8f0;
    color: #475569;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.document-count-pill.pending {
    background: #fef3c7;
    color: #92400e;
}

.document-count-pill.verified {
    background: #dcfce7;
    color: #166534;
}

.document-count-pill.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.document-view-btn {
    padding: 9px 13px;
    border: 1px solid #dbe4ef;
    border-radius: 8px;
    background: #fff;
    color: #174ea6;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    cursor: pointer;
}

.document-view-btn:hover {
    background: #eff6ff;
    border-color: #9ab8df;
}

.document-detail-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.document-detail-heading h2 {
    margin: 14px 0 6px;
    color: #102a56;
    font-size: 22px;
}

.document-detail-heading p {
    margin: 0;
    color: #64748b;
    font-size: 12px;
}

@media (max-width: 1100px) {
    .document-student-row {
        grid-template-columns: minmax(160px, 1fr) minmax(110px, .7fr) minmax(110px, .7fr) auto;
    }
    .document-student-row .document-count-pills {
        grid-column: 1 / -1;
    }
}

@media (max-width: 800px) {
    .document-directory-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .document-student-row {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .document-student-row .document-count-pills,
    .document-student-row .document-view-btn {
        grid-column: 1 / -1;
        justify-self: start;
    }
    .document-detail-heading {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .document-directory-stats {
        gap: 9px;
    }
    .document-directory-stat {
        padding: 14px;
    }
    .document-student-row {
        grid-template-columns: 1fr;
    }
    .document-student-row .document-count-pills,
    .document-student-row .document-view-btn {
        grid-column: auto;
    }
    .document-section-title {
        align-items: flex-start;
        flex-direction: column;
    }
}


/* =========================================
   ADDITIVE REQUIRED-DOCUMENT TRACKING
   Keeps the existing registrar theme intact.
========================================= */
.document-directory-stats {
    grid-template-columns: repeat(5, minmax(0, 1fr));
}

.document-directory-stat-attention {
    border-color: #f5d7a7;
    background: #fffaf0;
}

.document-directory-stat-attention strong {
    color: #9a5b08;
}

.document-count-pill.missing {
    background: #fee2e2;
    color: #991b1b;
}

.required-application-card {
    margin-top: 15px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
    box-shadow: 0 2px 9px rgba(15, 23, 42, .035);
}

.required-application-card:first-child {
    margin-top: 0;
}

.required-application-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    padding-bottom: 15px;
    margin-bottom: 16px;
    border-bottom: 1px solid #edf1f6;
}

.required-application-heading strong {
    display: block;
    color: #15345f;
    font-size: 14px;
}

.required-application-heading small {
    display: block;
    margin-top: 5px;
    color: #718096;
    font-size: 11px;
    line-height: 1.5;
}

.required-document-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.required-document-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    min-height: 58px;
    padding: 12px 14px;
    background: #f8fafc;
    border: 1px solid #e8edf5;
    border-radius: 9px;
}

.required-document-name {
    min-width: 0;
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.5;
}

.required-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.required-status.missing,
.required-status.rejected {
    background: #fee2e2;
    color: #991b1b;
}

.required-status.pending {
    background: #fef3c7;
    color: #92400e;
}

.required-status.verified {
    background: #dcfce7;
    color: #166534;
}

.required-application-footer {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    padding-top: 14px;
    margin-top: 16px;
    border-top: 1px solid #edf1f6;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
}

@media (max-width: 1050px) {
    .document-directory-stats {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 800px) {
    .required-document-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .document-directory-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .required-document-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .required-application-heading {
        align-items: flex-start;
        flex-direction: column;
    }
}

</style>
<link rel="stylesheet" href="../assets/css/sidebar-unified.css">
</head>

<body>
<!-- REGISTRAR-DASHBOARD-TEST-123 -->
<div class="layout">

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
            WORKSPACE
        </div>

        <button class="nav-btn active" data-view="overview" onclick="showView('overview')">
            <span>▦</span> Dashboard
        </button>

        <button class="nav-btn" data-view="applications" onclick="showView('applications')">
            <span>▤</span> Enrollment Applications
        </button>

        <button class="nav-btn" data-view="documents" onclick="showView('documents')">
            <span>▧</span> Document Verification
        </button>

        <button class="nav-btn" data-view="students" onclick="showView('students')">
            <span>▣</span> Student Records
        </button>

        <div class="sidebar-bottom">
            <a href="?logout=1" class="logout-btn">↪ Sign out</a>
        </div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div style="display:flex;align-items:center;gap:13px;">
                <button class="mobile-menu" onclick="toggleSidebar()">☰</button>
                <div class="topbar-title">Skwela College Portal</div>
            </div>

            <button class="shared-profile-trigger" type="button" onclick="openSharedProfile()" aria-label="Open registrar profile">
                <?php if ($registrarProfileImage !== ''): ?>
                    <img class="shared-profile-avatar" src="<?= htmlspecialchars($registrarProfileImage, ENT_QUOTES, 'UTF-8') ?>" alt="Registrar profile picture">
                <?php else: ?>
                    <span class="shared-profile-avatar"><?= htmlspecialchars($registrarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="shared-profile-trigger-name"><?= htmlspecialchars($registrarName, ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </header>

        <div class="content">

            <div id="connectionMessage" class="connection">
                Connecting to enrollment services...
            </div>

            <section class="view active" id="overview">

                <div class="page-header">
                    <div>
                        <h1>Registrar Overview</h1>
                        <p>Review student applications and verify enrollment documents.</p>
                    </div>

                    <button class="refresh-btn" onclick="loadDashboard()">↻ Refresh Data</button>
                </div>

                <div class="stats-grid">

                    <div class="stat-card">
                        <div class="stat-label">Total Applications</div>
                        <div class="stat-number" id="totalApplications">0</div>
                        <div class="stat-note">All submitted applications</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Pending Review</div>
                        <div class="stat-number" id="pendingApplications">0</div>
                        <div class="stat-note">Awaiting registrar review</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Approved Applications</div>
                        <div class="stat-number" id="approvedApplications">0</div>
                        <div class="stat-note">Successfully approved</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Documents for Verification</div>
                        <div class="stat-number" id="pendingDocuments">0</div>
                        <div class="stat-note">Documents awaiting checking</div>
                    </div>

                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Quick Access</h2>
                            <p>Open the registrar workspace you need.</p>
                        </div>
                    </div>

                    <div class="quick-links">
                        <button class="quick-link" onclick="showView('applications')">
                            <strong>▤ Enrollment Applications</strong>
                            <span>Review and manage student applications.</span>
                        </button>

                        <button class="quick-link" onclick="showView('documents')">
                            <strong>▧ Document Verification</strong>
                            <span>Check uploaded enrollment documents.</span>
                        </button>

                        <button class="quick-link" onclick="showView('students'); loadStudentRecords();">
                            <strong>▣ Student Records</strong>
                            <span>View each student's profile, applications, and documents.</span>
                        </button>

                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Latest Enrollment Applications</h2>
                            <p>Recently submitted student applications.</p>
                        </div>

                        <button class="secondary-btn" onclick="showView('applications')">
                            View All →
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Student Number</th>
                                    <th>Course</th>
                                    <th>Date Submitted</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="latestApplications">
                                <tr>
                                    <td colspan="5" class="empty-state">Loading applications...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Document Summary</h2>
                            <p>Overview of submitted enrollment documents.</p>
                        </div>

                        <button class="secondary-btn" onclick="showView('documents')">
                            Open Document Verification →
                        </button>
                    </div>

                    <div class="stats-grid" style="margin-bottom:0;">
                        <div>
                            <div class="stat-label">Total Documents</div>
                            <div class="stat-number" id="totalDocuments">0</div>
                        </div>
                        <div>
                            <div class="stat-label">Pending Verification</div>
                            <div class="stat-number" id="pendingDocumentSummary">0</div>
                        </div>
                        <div>
                            <div class="stat-label">Verified Documents</div>
                            <div class="stat-number" id="verifiedDocuments">0</div>
                        </div>
                        <div>
                            <div class="stat-label">Rejected Documents</div>
                            <div class="stat-number" id="rejectedDocuments">0</div>
                        </div>
                    </div>
                </div>

            </section>

            <section class="view" id="applications">

                <div class="page-header">
                    <div>
                        <h1>Enrollment Applications</h1>
                        <p>Review submitted enrollment applications and update their status.</p>
                    </div>

                    <button class="refresh-btn" onclick="loadDashboard()">↻ Refresh</button>
                </div>

                <div class="panel">

                    <div class="panel-heading">
                        <div>
                            <h2>Student Applications</h2>
                            <p>View, review, approve, or reject applications.</p>
                        </div>
                    </div>

                    <div class="toolbar">
                        <input
                            type="text"
                            class="search-input"
                            id="applicationSearch"
                            placeholder="Search student name or number..."
                            oninput="renderApplications()"
                        >

                        <select class="filter-select" id="applicationFilter" onchange="renderApplications()">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="under_review">Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Application ID</th>
                                    <th>Student</th>
                                    <th>Student Number</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Date Submitted</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody id="applicationTable">
                                <tr>
                                    <td colspan="8" class="empty-state">Loading applications...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </section>

            <section class="view" id="documents">

    <div class="page-header">
        <div>
            <h1>Document Verification</h1>
            <p>Choose a student to review only their own enrollment documents.</p>
        </div>
        <button class="refresh-btn" type="button" onclick="loadDashboard()">↻ Refresh</button>
    </div>

    <!-- STUDENT DIRECTORY: GROUPED BY COURSE AND SECTION -->
    <div id="documentStudentListView">
        <div class="document-directory-stats">
            <div class="document-directory-stat">
                <span>Total Students</span>
                <strong id="documentStudentCount">0</strong>
            </div>
            <div class="document-directory-stat">
                <span>Pending Documents</span>
                <strong id="directoryPendingCount">0</strong>
            </div>
            <div class="document-directory-stat">
                <span>Verified Documents</span>
                <strong id="directoryVerifiedCount">0</strong>
            </div>
            <div class="document-directory-stat">
                <span>Rejected Documents</span>
                <strong id="directoryRejectedCount">0</strong>
            </div>
            <div class="document-directory-stat document-directory-stat-attention">
                <span>Missing / Rejected Requirements</span>
                <strong id="directoryMissingRequirementCount">0</strong>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Students by Section</h2>
                    <p>Sections are grouped together. Students are sorted alphabetically by surname.</p>
                </div>
            </div>

            <div class="toolbar">
                <input
                    type="search"
                    class="search-input"
                    id="documentSearch"
                    placeholder="Search student name or student number..."
                    oninput="renderDocuments()"
                >

                <select class="filter-select" id="documentSectionFilter" onchange="renderDocuments()">
                    <option value="">All Sections</option>
                </select>

                <select class="filter-select" id="documentFilter" onchange="renderDocuments()">
                    <option value="">All Document Statuses</option>
                    <option value="pending">With Pending Documents</option>
                    <option value="rejected">With Rejected Documents</option>
                    <option value="verified">With Verified Documents</option>
                    <option value="missing">With Missing / Rejected Requirements</option>
                    <option value="none">No Uploaded Documents</option>
                </select>
            </div>

            <div id="documentStudentDirectory">
                <div class="empty-state">Loading student list...</div>
            </div>
        </div>
    </div>

    <!-- INDIVIDUAL STUDENT DOCUMENTS -->
    <div id="documentStudentDetailView" hidden>
        <div class="document-detail-heading">
            <div>
                <button class="secondary-btn" type="button" id="backToDocumentStudents">← Back to Students</button>
                <h2 id="selectedDocumentStudentName">Student Documents</h2>
                <p id="selectedDocumentStudentMeta">Student information</p>
            </div>
            <button class="refresh-btn" type="button" onclick="loadDashboard()">↻ Refresh</button>
        </div>

        <div class="document-directory-stats document-detail-stats">
            <div class="document-directory-stat">
                <span>Total Documents</span>
                <strong id="selectedStudentDocumentCount">0</strong>
            </div>
            <div class="document-directory-stat">
                <span>Pending</span>
                <strong id="selectedStudentPendingCount">0</strong>
            </div>
            <div class="document-directory-stat">
                <span>Verified</span>
                <strong id="selectedStudentVerifiedCount">0</strong>
            </div>
            <div class="document-directory-stat">
                <span>Rejected</span>
                <strong id="selectedStudentRejectedCount">0</strong>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Missing Required Documents</h2>
                    <p>Requirements are checked separately for each enrollment application. Missing or rejected items need attention.</p>
                </div>
            </div>
            <div id="studentRequirementByApplication">
                <div class="empty-state">Loading requirement checklist...</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Uploaded Documents</h2>
                    <p>Only documents belonging to this selected student are displayed.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Application</th>
                            <th>Date Uploaded</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentDocumentTable">
                        <tr><td colspan="5" class="empty-state">Select a student to view documents.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</section>

<section class="view" id="students">
                <?php require __DIR__ . '/student_records_view.php'; ?>
            </section>

        </div>
    </main>
</div>

<div class="modal" id="detailModal">
    <div class="modal-box">
        <div class="modal-top">
            <h2>Enrollment Application Details</h2>
            <button class="close-btn" onclick="closeModal('detailModal')">✕</button>
        </div>

        <div id="applicationDetails"></div>

        <div class="modal-actions">
            <button class="secondary-btn" onclick="closeModal('detailModal')">Close</button>
        </div>
    </div>
</div>

<div class="modal" id="previewModal">
    <div class="modal-box" style="max-width:1000px;">
        <div class="modal-top">
            <h2 id="previewTitle">Document Preview</h2>
            <button class="close-btn" onclick="closeModal('previewModal')">✕</button>
        </div>

        <div id="previewContent"></div>

        <div class="modal-actions">
            <button class="secondary-btn" onclick="closeModal('previewModal')">Close</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/profile_modal.php'; ?>

<div class="toast" id="toast"></div>

<script>
const ENROLLMENT_API = "../../backend/routes/enrollments.php";
const DOCUMENT_API = "../../backend/routes/documents.php";
const PREVIEW_API = "../../backend/routes/document_preview.php";

let applications = [];
let documents = [];
let selectedDocumentStudentId = 0;

function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    }[char]));
}

function pick(item, keys, fallback = "—") {
    for (const key of keys) {
        if (
            item &&
            item[key] !== undefined &&
            item[key] !== null &&
            item[key] !== ""
        ) {
            return item[key];
        }
    }

    return fallback;
}

function normalizeStatus(status) {
    return String(status ?? "pending")
        .toLowerCase()
        .replace(/[\s-]+/g, "_");
}

function statusBadge(status) {
    const value = normalizeStatus(status);
    const supported = [
        "pending",
        "under_review",
        "approved",
        "rejected",
        "verified"
    ];

    const cssClass = supported.includes(value) ? value : "default";
    const label = value.replace(/_/g, " ");

    return `<span class="status ${cssClass}">${escapeHTML(label)}</span>`;
}

function showToast(message, type = "success") {
    const toast = document.getElementById("toast");

    toast.textContent = message;
    toast.className = "toast show" + (type === "error" ? " error" : "");

    setTimeout(() => {
        toast.className = "toast";
    }, 4000);
}

function showConnection(message, isError = false) {
    const box = document.getElementById("connectionMessage");

    box.textContent = message;
    box.className = isError ? "connection error" : "connection";
}

function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("open");
}

 function showView(viewId) {
    document.querySelectorAll(".view").forEach(view => {
        view.classList.remove("active");
    });

    document.querySelectorAll(".nav-btn[data-view]").forEach(button => {
        button.classList.toggle(
            "active",
            button.dataset.view === viewId
        );
    });

    const selectedView = document.getElementById(viewId);

    if (selectedView) {
        selectedView.classList.add("active");
    }

    document.getElementById("sidebar").classList.remove("open");

    if (viewId === "students" && typeof loadStudentRecords === "function") {
        loadStudentRecords();
    }
}
function closeModal(id) {
    document.getElementById(id).classList.remove("show");
}

async function fetchJSON(url, options = {}) {
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
        throw new Error(
            "The server returned an invalid response. Check the backend PHP route."
        );
    }

    if (!response.ok || data.success === false) {
        throw new Error(
            data.message || `Request failed with status ${response.status}.`
        );
    }

    return data;
}

async function loadDashboard() {
    showConnection("Connecting to enrollment services...");

    const results = await Promise.allSettled([
        fetchJSON(ENROLLMENT_API),
        fetchJSON(DOCUMENT_API)
    ]);

    let errors = [];

    if (results[0].status === "fulfilled") {
        const data = results[0].value;
        applications = Array.isArray(data.enrollments)
            ? data.enrollments
            : [];
    } else {
        applications = [];
        errors.push(
            "Enrollment applications: " + results[0].reason.message
        );
    }

    if (results[1].status === "fulfilled") {
        const data = results[1].value;
        documents = Array.isArray(data.documents)
            ? data.documents
            : [];
    } else {
        documents = [];
        errors.push("Documents: " + results[1].reason.message);
    }

    if (errors.length === 0) {
        showConnection(
            "Connected successfully. Dashboard data is up to date."
        );
    } else {
        showConnection(errors.join(" | "), true);
    }

    updateStatistics();
    renderApplications();
    renderDocuments();
    renderLatestApplications();

    if (errors.length > 0) {
        showToast(
            "Some dashboard information could not be loaded.",
            "error"
        );
    }
}

function updateStatistics() {
    const pendingApps = applications.filter(item =>
        normalizeStatus(item.status) === "pending"
    ).length;

    const approvedApps = applications.filter(item =>
        normalizeStatus(item.status) === "approved"
    ).length;

    const pendingDocs = documents.filter(item =>
        normalizeStatus(item.verification_status) === "pending"
    ).length;

    const verifiedDocs = documents.filter(item =>
        normalizeStatus(item.verification_status) === "verified"
    ).length;

    const rejectedDocs = documents.filter(item =>
        normalizeStatus(item.verification_status) === "rejected"
    ).length;

    document.getElementById("totalApplications").textContent =
        applications.length;

    document.getElementById("pendingApplications").textContent =
        pendingApps;

    document.getElementById("approvedApplications").textContent =
        approvedApps;

    document.getElementById("pendingDocuments").textContent =
        pendingDocs;

    document.getElementById("totalDocuments").textContent =
        documents.length;

    document.getElementById("pendingDocumentSummary").textContent =
        pendingDocs;

    document.getElementById("verifiedDocuments").textContent =
        verifiedDocs;

    document.getElementById("rejectedDocuments").textContent =
        rejectedDocs;
}

function applicationStudentName(item) {
    return pick(item, ["full_name", "student_name", "name"]);
}

function applicationStudentNumber(item) {
    return pick(item, ["student_number", "student_no"]);
}

function applicationCourse(item) {
    return pick(item, ["course_code", "course_name", "course"]);
}

function applicationSection(item) {
    return pick(item, ["section_name", "section"]);
}

function applicationDate(item) {
    return pick(item, [
        "submitted_at",
        "created_at",
        "enrollment_date"
    ]);
}

function renderLatestApplications() {
    const tbody = document.getElementById("latestApplications");

    if (!applications.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state">
                    No enrollment applications found.
                </td>
            </tr>
        `;
        return;
    }

    const latest = [...applications]
        .sort((a, b) => {
            return String(
                pick(b, ["submitted_at", "created_at"], "")
            ).localeCompare(
                String(pick(a, ["submitted_at", "created_at"], ""))
            );
        })
        .slice(0, 5)
        .sort(compareStudentsBySurname);

    tbody.innerHTML = latest.map(item => `
        <tr>
            <td>${escapeHTML(applicationStudentName(item))}</td>
            <td>${escapeHTML(applicationStudentNumber(item))}</td>
            <td>${escapeHTML(applicationCourse(item))}</td>
            <td>${escapeHTML(applicationDate(item))}</td>
            <td>${statusBadge(item.status)}</td>
        </tr>
    `).join("");
}

function renderApplications() {
    const tbody = document.getElementById("applicationTable");
    const search = document.getElementById("applicationSearch").value.toLowerCase();
    const filter = document.getElementById("applicationFilter").value;

    const filtered = applications.filter(item => {
        const searchText = [
            applicationStudentName(item),
            applicationStudentNumber(item),
            item.email,
            item.enrollment_id
        ].join(" ").toLowerCase();

        const matchesSearch = searchText.includes(search);
        const matchesStatus = !filter || normalizeStatus(item.status) === filter;
        return matchesSearch && matchesStatus;
    }).sort(compareStudentsBySurname);

    if (!filtered.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    No matching enrollment applications found.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(item => {
        const id = Number(item.enrollment_id);
        const status = normalizeStatus(item.status);

        let actions = `
            <button class="secondary-btn" data-action="view" data-id="${id}">
                View
            </button>
        `;

        if (status === "pending") {
            actions += `
                <button class="warning-btn" data-action="review" data-id="${id}">
                    Start Review
                </button>
            `;
        }

        if (status === "pending" || status === "under_review") {
            actions += `
                <button class="success-btn" data-action="approve" data-id="${id}">
                    Approve
                </button>
                <button class="danger-btn" data-action="reject" data-id="${id}">
                    Reject
                </button>
            `;
        }

        return `
            <tr>
                <td>#${escapeHTML(id)}</td>
                <td>${escapeHTML(applicationStudentName(item))}</td>
                <td>${escapeHTML(applicationStudentNumber(item))}</td>
                <td>${escapeHTML(applicationCourse(item))}</td>
                <td>${escapeHTML(applicationSection(item))}</td>
                <td>${escapeHTML(applicationDate(item))}</td>
                <td>${statusBadge(status)}</td>
                <td>
                    <div class="action-group">${actions}</div>
                </td>
            </tr>
        `;
    }).join("");
}

document.getElementById("applicationTable").addEventListener("click", event => {
    const button = event.target.closest("button[data-action]");

    if (!button) return;

    const id = Number(button.dataset.id);
    const action = button.dataset.action;

    if (action === "view") {
        viewApplication(id);
    } else if (action === "review") {
        updateEnrollmentStatus(id, "under_review");
    } else if (action === "approve") {
        updateEnrollmentStatus(id, "approved");
    } else if (action === "reject") {
        updateEnrollmentStatus(id, "rejected");
    }
});

function viewApplication(id) {
    const item = applications.find(app =>
        Number(app.enrollment_id) === id
    );

    if (!item) {
        showToast("Application not found.", "error");
        return;
    }

    const details = [
        ["Application ID", pick(item, ["enrollment_id"])],
        ["Student Name", applicationStudentName(item)],
        ["Student Number", applicationStudentNumber(item)],
        ["Email", pick(item, ["email"])],
        ["Course", applicationCourse(item)],
        ["Section", applicationSection(item)],
        ["Academic Year", pick(item, [
            "year_name",
            "academic_year",
            "academic_year_name"
        ])],
        ["Semester", pick(item, ["semester_name", "semester"])],
        ["Year Level", pick(item, ["year_level"])],
        ["Date Submitted", applicationDate(item)],
        ["Status", pick(item, ["status"])]
    ];

    document.getElementById("applicationDetails").innerHTML = `
        <div class="detail-grid">
            ${details.map(([label, value]) => `
                <div class="detail-item">
                    <small>${escapeHTML(label)}</small>
                    <strong>${escapeHTML(value)}</strong>
                </div>
            `).join("")}
        </div>
    `;

    document.getElementById("detailModal").classList.add("show");
}

async function updateEnrollmentStatus(enrollmentId, status) {
    const labels = {
        under_review: "start reviewing",
        approved: "approve",
        rejected: "reject"
    };

    if (!confirm(
        `Are you sure you want to ${labels[status]} this application?`
    )) {
        return;
    }

    try {
        const data = await fetchJSON(ENROLLMENT_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "update_status",
                enrollment_id: enrollmentId,
                status
            })
        });

        showToast(
            data.message || "Application status updated successfully."
        );

        await loadDashboard();

    } catch (error) {
        showToast(error.message, "error");
    }
}

function documentStudentName(item) {
    const directName = String(
        pick(item, ["full_name", "student_name", "name"], "")
    ).trim();

    if (directName) return directName;

    return [item.first_name, item.middle_name, item.last_name]
        .filter(Boolean)
        .join(" ") || "Student";
}

function documentDate(item) {
    return pick(item, ["uploaded_at", "created_at", "submitted_at"]);
}

function studentSurnameSortParts(item) {
    const last = String(item.last_name || "").trim();
    const first = String(item.first_name || "").trim();
    if (last || first) return { last, first };

    const fullName = documentStudentName(item).trim();
    if (fullName.includes(",")) {
        const [surname, ...givenNames] = fullName.split(",");
        return { last: surname.trim(), first: givenNames.join(" ").trim() };
    }
    const parts = fullName.split(/\s+/).filter(Boolean);
    return {
        last: parts.length ? parts[parts.length - 1] : "",
        first: parts.slice(0, -1).join(" ")
    };
}

function compareStudentsBySurname(a, b) {
    const nameA = studentSurnameSortParts(a);
    const nameB = studentSurnameSortParts(b);
    const bySurname = nameA.last.localeCompare(nameB.last, undefined, {
        sensitivity: "base", numeric: true
    });
    if (bySurname) return bySurname;
    const byGivenName = nameA.first.localeCompare(nameB.first, undefined, {
        sensitivity: "base", numeric: true
    });
    if (byGivenName) return byGivenName;
    return Number(a.enrollment_id || a.student_id || 0) - Number(b.enrollment_id || b.student_id || 0);
}

function documentStudentSortName(item) {
    const parts = studentSurnameSortParts(item);
    return `${parts.last} ${parts.first}`.trim();
}

function compareApplicationDate(a, b) {
    const dateA = Date.parse(a.submitted_at || a.created_at || "") || 0;
    const dateB = Date.parse(b.submitted_at || b.created_at || "") || 0;
    if (dateA !== dateB) return dateB - dateA;
    return Number(b.enrollment_id || 0) - Number(a.enrollment_id || 0);
}

const REQUIRED_ENROLLMENT_DOCUMENTS = [
    "Birth Certificate",
    "Form 138 / Report Card",
    "Good Moral Certificate"
];

function normalizeDocumentName(value) {
    return String(value || "")
        .toLowerCase()
        .replace(/&/g, " and ")
        .replace(/[^a-z0-9]+/g, " ")
        .trim()
        .replace(/\s+/g, " ");
}

function getApplicationRequirementSummary(application, studentDocuments) {
    const enrollmentId = Number(application.enrollment_id);
    const enrollmentDocuments = studentDocuments.filter(doc =>
        Number(doc.enrollment_id) === enrollmentId
    );

    const items = REQUIRED_ENROLLMENT_DOCUMENTS.map(name => {
        const normalizedName = normalizeDocumentName(name);
        const matches = enrollmentDocuments
            .filter(doc => normalizeDocumentName(doc.document_name) === normalizedName)
            .sort((a, b) => {
                const dateA = Date.parse(a.uploaded_at || a.created_at || "") || 0;
                const dateB = Date.parse(b.uploaded_at || b.created_at || "") || 0;
                if (dateA !== dateB) return dateB - dateA;
                return Number(b.document_id || 0) - Number(a.document_id || 0);
            });

        if (!matches.length) {
            return { name, state: "missing", label: "Missing — Not submitted" };
        }

        const latestDocument = matches[0];
        const status = normalizeStatus(latestDocument.verification_status);

        if (status === "verified") {
            return { name, state: "verified", label: "Verified" };
        }
        if (status === "rejected") {
            return { name, state: "rejected", label: "Rejected — Resubmit" };
        }
        return { name, state: "pending", label: "Pending verification" };
    });

    const verifiedCount = items.filter(item => item.state === "verified").length;
    const missingCount = items.filter(item => item.state === "missing").length;
    const rejectedCount = items.filter(item => item.state === "rejected").length;
    const pendingCount = items.filter(item => item.state === "pending").length;

    return {
        enrollmentId,
        items,
        verifiedCount,
        missingCount,
        rejectedCount,
        pendingCount,
        outstandingCount: missingCount + rejectedCount,
        isComplete: verifiedCount === REQUIRED_ENROLLMENT_DOCUMENTS.length
    };
}

function getStudentRequirementTotals(student) {
    return student.applications.reduce((totals, app) => {
        const summary = getApplicationRequirementSummary(app, student.documents);
        totals.requiredCount += summary.items.length;
        totals.verifiedCount += summary.verifiedCount;
        totals.missingCount += summary.missingCount;
        totals.rejectedCount += summary.rejectedCount;
        totals.pendingCount += summary.pendingCount;
        return totals;
    }, {
        requiredCount: 0,
        verifiedCount: 0,
        missingCount: 0,
        rejectedCount: 0,
        pendingCount: 0
    });
}

function buildDocumentStudentDirectory() {
    const map = new Map();

    // Use enrollment records as the source of student, course, and section data.
    [...applications].sort(compareApplicationDate).forEach(app => {
        const studentId = Number(app.student_id);
        if (!studentId) return;

        if (!map.has(studentId)) {
            map.set(studentId, {
                student_id: studentId,
                student_number: app.student_number || "—",
                full_name: documentStudentName(app),
                first_name: app.first_name || "",
                last_name: app.last_name || "",
                email: app.email || "",
                latestApplication: app,
                applications: [app]
            });
        } else {
            map.get(studentId).applications.push(app);
        }
    });

    return [...map.values()].map(student => {
        // documents.php returns e.student_id for every document; use it to isolate records.
        student.documents = documents.filter(doc =>
            Number(doc.student_id) === student.student_id
        );

        student.pendingCount = student.documents.filter(doc =>
            normalizeStatus(doc.verification_status) === "pending"
        ).length;
        student.verifiedCount = student.documents.filter(doc =>
            normalizeStatus(doc.verification_status) === "verified"
        ).length;
        student.rejectedCount = student.documents.filter(doc =>
            normalizeStatus(doc.verification_status) === "rejected"
        ).length;
        student.documentCount = student.documents.length;

        // Requirements are evaluated per enrollment_id so documents from another
        // application or another student can never satisfy this checklist.
        student.requirementTotals = getStudentRequirementTotals(student);
        student.missingRequirementCount = student.requirementTotals.missingCount;
        student.rejectedRequirementCount = student.requirementTotals.rejectedCount;
        student.pendingRequirementCount = student.requirementTotals.pendingCount;
        student.outstandingRequirementCount =
            student.missingRequirementCount + student.rejectedRequirementCount;

        const app = student.latestApplication;
        student.courseCode = pick(app, ["course_code", "course_name"], "Course not specified");
        student.sectionName = pick(app, ["section_name", "section"], "No section assigned");
        student.yearName = pick(app, ["year_name", "academic_year"], "");
        student.semesterName = pick(app, ["semester_name"], "");
        student.yearLevel = pick(app, ["year_level"], "");
        student.sectionKey = [
            student.courseCode,
            student.sectionName,
            student.yearName,
            student.semesterName
        ].join(" | ");

        return student;
    });
}

function renderDocuments() {
    const directory = document.getElementById("documentStudentDirectory");
    if (!directory) return;

    const students = buildDocumentStudentDirectory();
    const search = (document.getElementById("documentSearch")?.value || "")
        .trim().toLowerCase();
    const statusFilter = document.getElementById("documentFilter")?.value || "";
    const sectionSelect = document.getElementById("documentSectionFilter");
    const previousSection = sectionSelect?.value || "";

    const sections = [...new Set(students.map(student => student.sectionKey))]
        .sort((a, b) => a.localeCompare(b, undefined, {
            numeric: true,
            sensitivity: "base"
        }));

    if (sectionSelect) {
        sectionSelect.innerHTML = '<option value="">All Sections</option>' +
            sections.map(key => {
                const student = students.find(item => item.sectionKey === key);
                const label = `${student.courseCode} • Section ${student.sectionName}`;
                return `<option value="${escapeHTML(key)}">${escapeHTML(label)}</option>`;
            }).join("");
        if (sections.includes(previousSection)) sectionSelect.value = previousSection;
    }

    const activeSection = sectionSelect?.value || "";

    const filtered = students.filter(student => {
        const searchText = [
            student.full_name,
            student.student_number,
            student.courseCode,
            student.sectionName,
            student.yearName
        ].join(" ").toLowerCase();

        const matchesSearch = searchText.includes(search);
        const matchesSection = !activeSection || student.sectionKey === activeSection;

        let matchesStatus = true;
        if (statusFilter === "pending") matchesStatus = student.pendingCount > 0;
        if (statusFilter === "verified") matchesStatus = student.verifiedCount > 0;
        if (statusFilter === "rejected") matchesStatus = student.rejectedCount > 0;
        if (statusFilter === "missing") matchesStatus = student.outstandingRequirementCount > 0;
        if (statusFilter === "none") matchesStatus = student.documentCount === 0;

        return matchesSearch && matchesSection && matchesStatus;
    });

    document.getElementById("documentStudentCount").textContent = students.length;
    document.getElementById("directoryPendingCount").textContent = documents.filter(doc =>
        normalizeStatus(doc.verification_status) === "pending"
    ).length;
    document.getElementById("directoryVerifiedCount").textContent = documents.filter(doc =>
        normalizeStatus(doc.verification_status) === "verified"
    ).length;
    document.getElementById("directoryRejectedCount").textContent = documents.filter(doc =>
        normalizeStatus(doc.verification_status) === "rejected"
    ).length;
    document.getElementById("directoryMissingRequirementCount").textContent =
        students.reduce((sum, student) => sum + student.outstandingRequirementCount, 0);

    if (!filtered.length) {
        directory.innerHTML = '<div class="empty-state">No students match the selected search or filters.</div>';
        if (selectedDocumentStudentId) renderSelectedStudentDocuments();
        return;
    }

    const courseGroups = new Map();
    filtered.forEach(student => {
        const courseKey = student.courseCode || "Unassigned Course";
        if (!courseGroups.has(courseKey)) courseGroups.set(courseKey, new Map());
        const sections = courseGroups.get(courseKey);
        if (!sections.has(student.sectionKey)) sections.set(student.sectionKey, []);
        sections.get(student.sectionKey).push(student);
    });

    const orderedCourses = [...courseGroups.entries()].sort((a, b) =>
        a[0].localeCompare(b[0], undefined, { numeric: true, sensitivity: "base" })
    );

    directory.innerHTML = orderedCourses.map(([courseCode, sections]) => {
        const sectionEntries = [...sections.entries()].sort((a, b) =>
            a[0].localeCompare(b[0], undefined, { numeric: true, sensitivity: "base" })
        );
        const courseCount = sectionEntries.reduce((sum, [, list]) => sum + list.length, 0);
        const sectionMarkup = sectionEntries.map(([sectionKey, sectionStudents]) => {
            sectionStudents.sort((a, b) =>
                documentStudentSortName(a).localeCompare(documentStudentSortName(b), undefined, { sensitivity: "base", numeric: true })
            );
            const first = sectionStudents[0];
            const sectionDescription = [first.yearName, first.semesterName].filter(Boolean).join(" • ");
            return `
                <details class="document-section-group">
                    <summary>
                        <div class="document-section-title">
                            <div>
                                <strong>Section ${escapeHTML(first.sectionName)}</strong>
                                ${sectionDescription ? `<small>${escapeHTML(sectionDescription)}</small>` : ""}
                            </div>
                            <span class="status default">${sectionStudents.length} student(s)</span>
                        </div>
                    </summary>
                    <div class="document-student-list">
                    ${sectionStudents.map((student, index) => `
                        <div class="document-student-row">
                            <div class="document-student-name">
                                <strong><span class="document-student-number">${index + 1}</span>${escapeHTML(student.full_name)}</strong>
                                <small>Student No. ${escapeHTML(student.student_number)}</small>
                            </div>

                            <div class="document-student-meta">
                                <strong>${escapeHTML(student.yearLevel ? `Year ${student.yearLevel}` : student.courseCode)}</strong>
                                <small>${student.applications.length} enrollment application(s)</small>
                            </div>

                            <div class="document-student-meta">
                                <strong>${student.documentCount} document(s)</strong>
                                <small>${student.verifiedCount} verified</small>
                            </div>

                            <div class="document-count-pills">
                                ${student.missingRequirementCount ? `<span class="document-count-pill missing">${student.missingRequirementCount} Missing Requirements</span>` : ""}
                                ${student.rejectedRequirementCount ? `<span class="document-count-pill rejected">${student.rejectedRequirementCount} Rejected Requirements</span>` : ""}
                                ${student.pendingRequirementCount ? `<span class="document-count-pill pending">${student.pendingRequirementCount} Awaiting Review</span>` : ""}
                                ${student.outstandingRequirementCount === 0 && student.pendingRequirementCount === 0 && student.applications.length ? `<span class="document-count-pill verified">Requirements Complete</span>` : ""}
                                ${student.documentCount === 0 ? `<span class="document-count-pill">No uploads</span>` : ""}
                            </div>

                            <button class="document-view-btn" type="button"
                                data-view-student-documents="${student.student_id}">
                                View Documents →
                            </button>
                        </div>
                    `).join("")}
                    </div>
                </details>
            `;
        }).join("");
        return `
            <details class="document-course-folder">
                <summary><span>📂 ${escapeHTML(courseCode)}</span><span class="document-folder-count">${courseCount} student(s) · ${sectionEntries.length} section(s)</span></summary>
                <div class="document-course-content">${sectionMarkup}</div>
            </details>
        `;
    }).join("");

    if (selectedDocumentStudentId) renderSelectedStudentDocuments();
}

function viewStudentDocuments(studentId) {
    selectedDocumentStudentId = Number(studentId);
    document.getElementById("documentStudentListView").hidden = true;
    document.getElementById("documentStudentDetailView").hidden = false;
    renderSelectedStudentDocuments();
}

function backToDocumentStudents() {
    selectedDocumentStudentId = 0;
    document.getElementById("documentStudentDetailView").hidden = true;
    document.getElementById("documentStudentListView").hidden = false;
    renderDocuments();
}

function renderStudentRequirementChecklist(student) {
    const host = document.getElementById("studentRequirementByApplication");
    if (!host) return;

    if (!student.applications.length) {
        host.innerHTML = '<div class="empty-state">No enrollment applications found for this student.</div>';
        return;
    }

    const orderedApplications = [...student.applications].sort(compareApplicationDate);

    host.innerHTML = orderedApplications.map(application => {
        const summary = getApplicationRequirementSummary(application, student.documents);
        const course = pick(application, ["course_code", "course_name"], "Course not specified");
        const section = pick(application, ["section_name", "section"], "No section assigned");
        const academicYear = pick(application, ["year_name", "academic_year"], "");
        const semester = pick(application, ["semester_name"], "");
        const complete = summary.isComplete;
        const overallLabel = complete ? "Complete" : "Incomplete";
        const overallClass = complete ? "verified" : "missing";
        const detail = [course, `Section ${section}`, academicYear, semester]
            .filter(Boolean).join(" • ");

        return `
            <div class="required-application-card">
                <div class="required-application-heading">
                    <div>
                        <strong>Application #${escapeHTML(application.enrollment_id)}</strong>
                        <small>${escapeHTML(detail)}</small>
                    </div>
                    <span class="required-status ${overallClass}">${overallLabel}</span>
                </div>

                <div class="required-document-grid">
                    ${summary.items.map(item => `
                        <div class="required-document-item">
                            <div class="required-document-name">${escapeHTML(item.name)}</div>
                            <span class="required-status ${item.state}">${escapeHTML(item.label)}</span>
                        </div>
                    `).join("")}
                </div>

                <div class="required-application-footer">
                    <span>${summary.verifiedCount} of ${summary.items.length} required documents verified</span>
                    <span>${summary.outstandingCount} missing / rejected • ${summary.pendingCount} pending</span>
                </div>
            </div>
        `;
    }).join("");
}

function renderSelectedStudentDocuments() {
    if (!selectedDocumentStudentId) return;

    const student = buildDocumentStudentDirectory().find(item =>
        item.student_id === selectedDocumentStudentId
    );
    const tbody = document.getElementById("studentDocumentTable");

    if (!student || !tbody) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="empty-state">Student record not found.</td></tr>';
        return;
    }

    document.getElementById("selectedDocumentStudentName").textContent = student.full_name;
    document.getElementById("selectedDocumentStudentMeta").textContent =
        `Student No. ${student.student_number} • ${student.courseCode} • Section ${student.sectionName}`;

    document.getElementById("selectedStudentDocumentCount").textContent = student.documentCount;
    document.getElementById("selectedStudentPendingCount").textContent = student.pendingCount;
    document.getElementById("selectedStudentVerifiedCount").textContent = student.verifiedCount;
    document.getElementById("selectedStudentRejectedCount").textContent = student.rejectedCount;

    renderStudentRequirementChecklist(student);

    const requirementOrder = [
        "Birth Certificate",
        "Form 138 / Report Card",
        "Good Moral Certificate",
        "Transcript of Records",
        "Certificate of Transfer",
        "Other Supporting Document"
    ];

    const studentDocuments = [...student.documents].sort((a, b) => {
        const indexA = requirementOrder.indexOf(String(a.document_name || ""));
        const indexB = requirementOrder.indexOf(String(b.document_name || ""));
        const rankA = indexA < 0 ? requirementOrder.length : indexA;
        const rankB = indexB < 0 ? requirementOrder.length : indexB;
        if (rankA !== rankB) return rankA - rankB;
        const enrollmentCompare = Number(a.enrollment_id || 0) - Number(b.enrollment_id || 0);
        if (enrollmentCompare !== 0) return enrollmentCompare;
        return Number(a.document_id || 0) - Number(b.document_id || 0);
    });

    if (!studentDocuments.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">This student has not uploaded any documents yet.</td></tr>';
        return;
    }

    tbody.innerHTML = studentDocuments.map(item => {
        const id = Number(item.document_id);
        const status = normalizeStatus(item.verification_status);
        let actions = `
            <button class="secondary-btn" data-doc-action="preview" data-id="${id}">Preview</button>
        `;

        if (status === "pending") {
            actions += `
                <button class="success-btn" data-doc-action="verify" data-id="${id}">Verify</button>
                <button class="danger-btn" data-doc-action="reject" data-id="${id}">Reject</button>
            `;
        }

        return `
            <tr>
                <td>${escapeHTML(pick(item, ["document_name"], "Unnamed Document"))}</td>
                <td>#${escapeHTML(item.enrollment_id || "—")}</td>
                <td>${escapeHTML(documentDate(item))}</td>
                <td>${statusBadge(status)}</td>
                <td><div class="action-group">${actions}</div></td>
            </tr>
        `;
    }).join("");
}

document.getElementById("documentStudentDirectory").addEventListener("click", event => {
    const button = event.target.closest("[data-view-student-documents]");
    if (!button) return;
    viewStudentDocuments(Number(button.dataset.viewStudentDocuments));
});

document.getElementById("studentDocumentTable").addEventListener("click", event => {
    const button = event.target.closest("button[data-doc-action]");
    if (!button) return;

    const id = Number(button.dataset.id);
    const action = button.dataset.docAction;

    if (action === "preview") previewDocument(id);
    if (action === "verify") updateDocumentStatus(id, "verified");
    if (action === "reject") updateDocumentStatus(id, "rejected");
});

document.getElementById("backToDocumentStudents").addEventListener("click", backToDocumentStudents);

function previewDocument(id) {
    const item = documents.find(doc =>
        Number(doc.document_id) === id
    );

    if (!item) {
        showToast("Document not found.", "error");
        return;
    }

    document.getElementById("previewTitle").textContent =
        pick(item, ["document_name"], "Document Preview");

    const fileUrl =
        PREVIEW_API + "?document_id=" + encodeURIComponent(id);

    document.getElementById("previewContent").innerHTML = `
        <iframe
            class="preview-frame"
            src="${escapeHTML(fileUrl)}"
            title="Enrollment Document Preview">
        </iframe>
    `;

    document.getElementById("previewModal").classList.add("show");
}

async function updateDocumentStatus(documentId, verificationStatus) {
    const actionText = verificationStatus === "verified"
        ? "verify"
        : "reject";

    if (!confirm(
        `Are you sure you want to ${actionText} this document?`
    )) {
        return;
    }

    try {
        const data = await fetchJSON(DOCUMENT_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                document_id: documentId,
                verification_status: verificationStatus
            })
        });

        showToast(
            data.message || "Document status updated successfully."
        );

        await loadDashboard();

    } catch (error) {
        showToast(error.message, "error");
    }
}

document.querySelectorAll(".modal").forEach(modal => {
    modal.addEventListener("click", event => {
        if (event.target === modal) {
            modal.classList.remove("show");
        }
    });
});

loadDashboard();

<?php if ($profileUploadMessage !== ''): ?>
document.addEventListener("DOMContentLoaded", () => {
    if (typeof openSharedProfile === "function") openSharedProfile();
});
<?php endif; ?>
</script>
<script src="../assets/js/profile-modal.js" defer></script>
<script src="../assets/js/registrar-students.js" defer></script>

</body>
</html>
