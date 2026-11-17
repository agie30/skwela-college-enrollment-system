const endpointMap = {
    courses: { url: "../../backend/routes/courses.php", label: "Courses", description: "Course information currently stored in the database." },
    subjects: { url: "../../backend/routes/subjects.php", label: "Subjects", description: "Subject codes, names, units, year levels, and other returned fields." },
    sections: { url: "../../backend/routes/sections.php", label: "Sections", description: "Section names, course links, capacities, and current statuses." },
    academic_years: { url: "../../backend/routes/academic_years.php", label: "Academic Years", description: "Academic year date ranges and enrollment availability." },
    activity_logs: { url: "../../backend/routes/activity_logs.php", label: "Activity Logs", description: "Recorded actions performed in the system." }
};

const state = { datasets: {}, errors: {}, currentView: "overview", filteredRows: [] };
const byId = id => document.getElementById(id);
const escapeHtml = value => String(value ?? "").replace(/[&<>"']/g, char => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[char]);

function extractRows(payload, depth = 0) {
    if (Array.isArray(payload)) return payload.filter(item => item && typeof item === "object");
    if (!payload || typeof payload !== "object" || depth > 4) return null;
    const keys = ["data", "records", "items", "courses", "subjects", "sections", "academic_years", "academicYears", "activities", "activity_logs", "logs"];
    for (const key of keys) {
        if (payload[key] !== undefined) {
            const result = extractRows(payload[key], depth + 1);
            if (result !== null) return result;
        }
    }
    return null;
}

async function fetchDataset(key) {
    const response = await fetch(endpointMap[key].url, {
        method: "GET",
        credentials: "same-origin",
        headers: { "Accept": "application/json" }
    });
    let payload;
    try { payload = await response.json(); }
    catch { throw new Error(`The ${endpointMap[key].label} API did not return valid JSON.`); }
    if (!response.ok || payload.success === false) {
        throw new Error(payload.message || payload.error || `Request failed (${response.status}).`);
    }
    const rows = extractRows(payload);
    if (rows === null) throw new Error("The API response format was not recognized. Check the endpoint response.");
    return rows;
}

async function loadAllDatasets() {
    const keys = Object.keys(endpointMap);
    await Promise.all(keys.map(async key => {
        try {
            state.datasets[key] = await fetchDataset(key);
            delete state.errors[key];
        } catch (error) {
            state.datasets[key] = [];
            state.errors[key] = error.message;
        }
    }));
    updateOverview();
    if (state.currentView !== "overview") renderRecords(state.currentView);
    updateConnectionStatus();
}

function updateConnectionStatus() {
    const failed = Object.keys(state.errors);
    const status = byId("connectionStatus");
    if (!failed.length) {
        status.classList.remove("error");
        status.innerHTML = '<span class="status-dot"></span><span>Connected. Academic setup and activity records were loaded from the backend.</span>';
    } else {
        status.classList.add("error");
        status.innerHTML = `<span class="status-dot"></span><span>${failed.map(key => `${escapeHtml(endpointMap[key].label)}: ${escapeHtml(state.errors[key])}`).join(" · ")}</span>`;
    }
}

function updateOverview() {
    const count = key => state.errors[key] ? "—" : state.datasets[key].length.toLocaleString();
    byId("coursesCount").textContent = count("courses");
    byId("subjectsCount").textContent = count("subjects");
    byId("sectionsCount").textContent = count("sections");
    byId("academicYearsCount").textContent = count("academic_years");
    byId("coursesSummary").textContent = count("courses");
    byId("subjectsSummary").textContent = count("subjects");
    byId("sectionsSummary").textContent = count("sections");
    const years = state.datasets.academic_years || [];
    const openCount = years.filter(row => String(row.status ?? "").toLowerCase() === "open").length;
    byId("openYearsSummary").textContent = state.errors.academic_years ? "—" : openCount;
    renderRecentActivity();
}

function prettyLabel(key) {
    return String(key).replace(/_/g, " ").replace(/([a-z])([A-Z])/g, "$1 $2").replace(/\b\w/g, letter => letter.toUpperCase());
}

function formatCell(value) {
    if (value === null || value === undefined || value === "") return "—";
    if (typeof value === "object") return JSON.stringify(value);
    return String(value);
}

function rowSearchText(row) {
    return Object.values(row).map(value => typeof value === "object" ? JSON.stringify(value) : String(value ?? "")).join(" ").toLowerCase();
}

function renderRecentActivity() {
    const container = byId("recentActivity");
    if (state.errors.activity_logs) {
        container.innerHTML = `<div class="error-state">Activity logs could not be loaded: ${escapeHtml(state.errors.activity_logs)}</div>`;
        return;
    }
    const rows = state.datasets.activity_logs || [];
    if (!rows.length) {
        container.innerHTML = '<div class="loading-state">No activity records are available yet.</div>';
        return;
    }
    container.innerHTML = rows.slice(0, 6).map(row => {
        const action = row.action || row.activity || row.activity_type || row.description || "System activity";
        const person = row.full_name || row.name || row.email || (row.user_id ? `User #${row.user_id}` : "System user");
        const detail = row.details || row.description || row.entity || "Activity recorded";
        const time = row.created_at || row.activity_date || row.timestamp || "";
        return `<div class="activity-row"><span class="activity-symbol">•</span><div class="activity-main"><strong>${escapeHtml(action)} · ${escapeHtml(person)}</strong><span>${escapeHtml(detail)}</span></div><span class="activity-time">${escapeHtml(time)}</span></div>`;
    }).join("");
}

function switchView(view) {
    if (view !== "overview" && !endpointMap[view]) return;
    state.currentView = view;
    byId("overviewView").classList.toggle("hidden", view !== "overview");
    byId("recordsView").classList.toggle("hidden", view === "overview");
    document.querySelectorAll(".nav-link").forEach(button => button.classList.toggle("active", button.dataset.view === view));
    const title = view === "overview" ? "Dashboard Overview" : endpointMap[view].label;
    byId("pageTitle").textContent = title;
    byId("breadcrumbCurrent").textContent = title;
    byId("pageSubtitle").textContent = view === "overview" ? "Monitor your academic setup and recent system activity." : endpointMap[view].description;
    if (view !== "overview") renderRecords(view);
    document.getElementById("sidebar").classList.remove("open");
}

function renderRecords(view) {
    const config = endpointMap[view];
    byId("recordsTitle").textContent = config.label;
    byId("recordsDescription").textContent = config.description;
    byId("recordsEyebrow").textContent = `${config.label.toUpperCase()} · DATABASE RECORDS`;
    if (state.errors[view]) {
        byId("recordCount").textContent = "Records unavailable";
        byId("recordsTable").querySelector("thead").innerHTML = "";
        byId("recordsTable").querySelector("tbody").innerHTML = `<tr><td class="empty-cell">${escapeHtml(state.errors[view])}</td></tr>`;
        state.filteredRows = [];
        return;
    }
    const rows = state.datasets[view] || [];
    const query = byId("searchInput").value.trim().toLowerCase();
    const status = byId("statusFilter").value;
    const filtered = rows.filter(row => {
        const matchesSearch = !query || rowSearchText(row).includes(query);
        const statusValue = String(row.status ?? row.verification_status ?? "").toLowerCase();
        const matchesStatus = status === "all" || statusValue === status;
        return matchesSearch && matchesStatus;
    });
    state.filteredRows = filtered;
    byId("recordCount").textContent = `${filtered.length.toLocaleString()} of ${rows.length.toLocaleString()} records`;
    byId("tableFooterText").textContent = `${config.label}: ${rows.length.toLocaleString()} total records returned by the API`;
    const keys = [...new Set(rows.flatMap(row => Object.keys(row)))];
    const thead = byId("recordsTable").querySelector("thead");
    const tbody = byId("recordsTable").querySelector("tbody");
    if (!keys.length) {
        thead.innerHTML = "";
        tbody.innerHTML = `<tr><td class="empty-cell">No ${escapeHtml(config.label.toLowerCase())} records found.</td></tr>`;
        return;
    }
    thead.innerHTML = `<tr>${keys.map(key => `<th>${escapeHtml(prettyLabel(key))}</th>`).join("")}</tr>`;
    if (!filtered.length) {
        tbody.innerHTML = `<tr><td class="empty-cell" colspan="${keys.length}">No records match your search or selected filter.</td></tr>`;
        return;
    }
    tbody.innerHTML = filtered.map(row => `<tr>${keys.map(key => `<td>${escapeHtml(formatCell(row[key]))}</td>`).join("")}</tr>`).join("");
}

function exportCurrentCsv() {
    const view = state.currentView;
    const rows = state.filteredRows;
    if (!rows.length) return showToast("There are no records to export.");
    const keys = [...new Set(rows.flatMap(row => Object.keys(row)))];
    const csvCell = value => `"${String(formatCell(value)).replace(/"/g, '""')}"`;
    const lines = [keys.map(csvCell).join(","), ...rows.map(row => keys.map(key => csvCell(row[key])).join(","))];
    const blob = new Blob(["\uFEFF" + lines.join("\r\n")], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `skwela-${view}-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(url);
    showToast("CSV export prepared.");
}

let toastTimer;
function showToast(message) {
    const toast = byId("toast");
    toast.textContent = message;
    toast.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove("show"), 2400);
}

document.querySelectorAll("[data-view]").forEach(button => button.addEventListener("click", () => switchView(button.dataset.view)));
document.querySelectorAll("[data-open-view]").forEach(button => button.addEventListener("click", () => switchView(button.dataset.openView)));
byId("refreshButton").addEventListener("click", loadAllDatasets);
byId("recordsRefreshButton").addEventListener("click", loadAllDatasets);
byId("searchInput").addEventListener("input", () => renderRecords(state.currentView));
byId("statusFilter").addEventListener("change", () => renderRecords(state.currentView));
byId("clearFiltersButton").addEventListener("click", () => { byId("searchInput").value = ""; byId("statusFilter").value = "all"; renderRecords(state.currentView); });
byId("exportButton").addEventListener("click", exportCurrentCsv);
byId("menuToggle").addEventListener("click", () => byId("sidebar").classList.toggle("open"));
byId("todayDate").textContent = new Intl.DateTimeFormat("en-PH", { month: "short", day: "numeric", year: "numeric" }).format(new Date());
loadAllDatasets();
