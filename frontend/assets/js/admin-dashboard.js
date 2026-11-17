
const endpointMap = {
    courses: {
        url: "../../backend/routes/courses.php",
        label: "Courses",
        description: "Manage courses offered by the college."
    },
    subjects: {
        url: "../../backend/routes/subjects.php",
        label: "Subjects",
        description: "Manage subjects, course assignments, semesters, and year levels."
    },
    sections: {
        url: "../../backend/routes/sections.php",
        label: "Sections",
        description: "Manage sections, course assignments, academic years, and capacity."
    },
    academic_years: {
        url: "../../backend/routes/academic_years.php",
        label: "Academic Years",
        description: "Academic year date ranges and enrollment availability."
    },
    activity_logs: {
        url: "../../backend/routes/activity_logs.php",
        label: "Activity Logs",
        description: "Review important account, enrollment, document, academic, and schedule changes. Routine sign-ins are kept separately."
    },
    schedules: {
        url: "../../backend/routes/schedules.php",
        label: "Class Schedules",
        description: "Create and manage subject schedules, class days, times, and classroom assignments."
    }
};

const state = {
    datasets: {},
    errors: {},
    currentView: "overview",
    filteredRows: [],
    scheduleAssignments: []
};

const byId = id => document.getElementById(id);

const escapeHtml = value =>
    String(value ?? "").replace(/[&<>"']/g, character => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[character]);

function extractRows(payload, depth = 0) {
    if (Array.isArray(payload)) return payload;
    if (!payload || typeof payload !== "object" || depth > 4) return null;

    const keys = [
        "courses", "subjects", "sections", "academic_years", "academicYears",
        "activities", "activity_logs", "logs", "schedules", "records", "items", "data"
    ];

    for (const key of keys) {
        if (payload[key] !== undefined) {
            const rows = extractRows(payload[key], depth + 1);
            if (rows !== null) return rows;
        }
    }

    return null;
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
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
        throw new Error("The server did not return valid JSON. Check the PHP route.");
    }

    if (!response.ok || result.success === false) {
        throw new Error(result.message || result.error || `Request failed (${response.status}).`);
    }

    return result;
}

async function fetchDataset(key) {
    const result = await requestJson(endpointMap[key].url);
    const rows = extractRows(result);

    if (rows === null) {
        throw new Error("The API response does not contain a recognizable records list.");
    }

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

    try {
        const assignmentData = await requestJson(`${endpointMap.schedules.url}?action=all_assignments`);
        state.scheduleAssignments = Array.isArray(assignmentData.assignments)
            ? assignmentData.assignments
            : [];
    } catch (error) {
        state.scheduleAssignments = [];
        console.warn('Section-subject folders could not load current assignments:', error.message);
    }

    updateOverview();
    updateConnectionStatus();

    if (state.currentView !== "overview") {
        renderRecords(state.currentView);
    }
}

function updateConnectionStatus() {
    const failed = Object.keys(state.errors);
    const status = byId("connectionStatus");

    if (!failed.length) {
        status.classList.remove("error");
        status.innerHTML = `
            <span class="status-dot"></span>
            <span>Connected. Records were loaded from the backend.</span>
        `;
    } else {
        status.classList.add("error");
        status.innerHTML = `
            <span class="status-dot"></span>
            <span>Some modules could not be loaded: ${failed.map(key =>
                `${escapeHtml(endpointMap[key].label)}: ${escapeHtml(state.errors[key])}`
            ).join(" | ")}</span>
        `;
    }
}

function updateOverview() {
    const count = key =>
        state.errors[key] ? "—" : (state.datasets[key] || []).length.toLocaleString();

    byId("coursesCount").textContent = count("courses");
    byId("subjectsCount").textContent = count("subjects");
    byId("sectionsCount").textContent = count("sections");
    byId("academicYearsCount").textContent = count("academic_years");

    byId("coursesSummary").textContent = count("courses");
    byId("subjectsSummary").textContent = count("subjects");
    byId("sectionsSummary").textContent = count("sections");

    const years = state.datasets.academic_years || [];

    const openCount = years.filter(year =>
        String(year.status ?? "").toLowerCase() === "open"
    ).length;

    byId("openYearsSummary").textContent =
        state.errors.academic_years ? "—" : openCount;

    renderRecentActivity();
}

function isRoutineAccessActivity(row) {
    const activity = String(row.activity || row.action || row.activity_type || "").toLowerCase();
    return /successful login|\blogin successful\b|^logout$|logged out/.test(activity);
}

function isDiagnosticActivity(row) {
    const activity = String(row.activity || row.action || row.activity_type || "").toLowerCase();
    return /system test|activity logging test/.test(`${activity} ${row.details || ""}`.toLowerCase());
}

function renderRecentActivity() {
    const container = byId("recentActivity");

    if (state.errors.activity_logs) {
        container.innerHTML = `
            <div class="error-state">
                Activity logs could not be loaded:
                ${escapeHtml(state.errors.activity_logs)}
            </div>
        `;
        return;
    }

    const rows = (state.datasets.activity_logs || []).filter(row =>
        !isRoutineAccessActivity(row) && !isDiagnosticActivity(row)
    );

    if (!rows.length) {
        container.innerHTML = `
            <div class="loading-state">
                No important system changes have been recorded yet.
            </div>
        `;
        return;
    }

    container.innerHTML = rows.slice(0, 6).map(row => {
        const action = row.activity || row.action || row.activity_type || "System activity";
        const person = row.full_name || row.name || row.email || (row.user_id ? `User #${row.user_id}` : "System user");
        const detail = row.details || row.description || "Activity recorded";
        const time = row.created_at || row.activity_date || row.timestamp || "";

        return `
            <div class="activity-row">
                <span class="activity-symbol">•</span>
                <div class="activity-main">
                    <strong>${escapeHtml(action)} · ${escapeHtml(surnameFirstLabel(person))}</strong>
                    <span>${escapeHtml(detail)}</span>
                </div>
                <span class="activity-time">${escapeHtml(time)}</span>
            </div>
        `;
    }).join("");
}

function prettyLabel(key) {
    return String(key)
        .replace(/_/g, " ")
        .replace(/([a-z])([A-Z])/g, "$1 $2")
        .replace(/\b\w/g, letter => letter.toUpperCase());
}

function formatCell(value) {
    if (value === null || value === undefined || value === "") return "—";
    if (typeof value === "object") return JSON.stringify(value);
    return String(value);
}

function rowSearchText(row) {
    return Object.values(row).map(value =>
        typeof value === "object" ? JSON.stringify(value) : String(value ?? "")
    ).join(" ").toLowerCase();
}

function getCourseById(id) {
    return (state.datasets.courses || []).find(course =>
        Number(course.course_id) === Number(id)
    );
}

function getSubjectById(id) {
    return (state.datasets.subjects || []).find(subject =>
        Number(subject.subject_id) === Number(id)
    );
}

function getSectionById(id) {
    return (state.datasets.sections || []).find(section =>
        Number(section.section_id) === Number(id)
    );
}

function getScheduleById(id) {
    return (state.datasets.schedules || []).find(schedule =>
        Number(schedule.schedule_id) === Number(id)
    );
}

function getAcademicYearId(year) {
    return Number(year.academic_year_id ?? year.id ?? 0);
}

function getAcademicYearLabel(year) {
    const directLabel =
        year.year_name ??
        year.academic_year ??
        year.academic_year_name ??
        year.school_year ??
        year.year_label;

    if (directLabel) return String(directLabel);

    const start = year.start_date ?? year.start_year ?? "";
    const end = year.end_date ?? year.end_year ?? "";

    if (start || end) return `${start} – ${end}`;

    return `Academic Year #${getAcademicYearId(year)}`;
}

function getRecordDetails(record, type) {
    const fields = Object.entries(record || {});

    byId("detailsTitle").textContent =
        `${endpointMap[type]?.label || "Record"} Details`;

    byId("detailsContent").innerHTML = fields.map(([key, value]) => `
        <div class="details-row">
            <span>${escapeHtml(prettyLabel(key))}</span>
            <strong>${escapeHtml(formatCell(value))}</strong>
        </div>
    `).join("");

    openModal("detailsModal");
}

function personNameParts(value) {
    const raw = String(value ?? '').trim().replace(/\s+/g, ' ');
    if (!raw) return { last: 'zzzz', given: '' };
    if (raw.includes(',')) {
        const [last, ...given] = raw.split(',');
        return { last: last.trim().toLocaleLowerCase(), given: given.join(' ').trim().toLocaleLowerCase() };
    }
    const parts = raw.split(' ');
    const last = parts.length > 1 ? parts.pop() : parts[0];
    return { last: last.toLocaleLowerCase(), given: parts.join(' ').toLocaleLowerCase() };
}

function surnameFirstLabel(value) {
    const raw = String(value ?? '').trim().replace(/\s+/g, ' ');
    if (!raw) return 'System';
    if (raw.includes(',')) return raw;
    const parts = raw.split(' ');
    if (parts.length < 2) return raw;
    const last = parts.pop();
    return `${last}, ${parts.join(' ')}`;
}

function compareBySurnameThenGiven(a, b, getName) {
    const pa = personNameParts(getName(a));
    const pb = personNameParts(getName(b));
    return pa.last.localeCompare(pb.last, 'en', { sensitivity: 'base' }) ||
        pa.given.localeCompare(pb.given, 'en', { sensitivity: 'base' });
}

function activityGroupFor(row) {
    const activity = String(row.activity || row.action || row.activity_type || "").toLowerCase();
    const details = String(row.details || row.description || "").toLowerCase();
    const text = `${activity} ${details}`;

    if (/failed login|unauthorized|suspicious|locked out/.test(text)) return "Security Alerts";
    if (isRoutineAccessActivity(row)) return "Routine Sign-in History";
    if (/document|requirement|verification|uploaded/.test(text)) return "Document Actions";
    if (/enrollment|application|student record/.test(text)) return "Enrollment Actions";
    if (/schedule|class timetable|room assignment/.test(text)) return "Schedule Changes";
    if (/course|subject|section|academic year|semester/.test(text)) return "Academic Setup Changes";
    if (/account|profile|password|user|registrar/.test(text)) return "Account Changes";
    return "Other System Events";
}

function activityTimestamp(row) {
    const raw = row.created_at || row.activity_date || row.timestamp || "";
    if (!raw) return 0;
    const parsed = Date.parse(String(raw).replace(" ", "T"));
    return Number.isFinite(parsed) ? parsed : 0;
}

function formatActivityPerson(row) {
    const person = row.full_name || row.name || row.email || "";
    if (person) return surnameFirstLabel(person);

    const details = String(row.details || row.description || "");
    const emailMatch = details.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);
    if (emailMatch) return `Unlinked account (${emailMatch[0]})`;
    return row.user_id ? `User #${row.user_id}` : "System";
}

function activityGroupDescription(groupName) {
    const descriptions = {
        "Security Alerts": "Failed login attempts and events that may need checking.",
        "Account Changes": "New accounts, profile changes, and account status updates.",
        "Enrollment Actions": "Applications submitted, reviewed, approved, or rejected.",
        "Document Actions": "Documents uploaded, verified, or rejected.",
        "Academic Setup Changes": "Course, subject, section, semester, and academic-year changes.",
        "Schedule Changes": "Class timetable and room assignment changes.",
        "Routine Sign-in History": "Successful logins and logouts. Kept separate so the main activity list stays clear.",
        "Other System Events": "Other recorded events and technical checks."
    };
    return descriptions[groupName] || "Recorded system activity.";
}

function renderActivityLogFolders(rows) {
    const folderHost = byId("activityLogFolders");
    const tableWrap = byId("recordsTable").closest(".table-wrap");
    tableWrap.hidden = true;
    folderHost.hidden = false;

    const routineRows = rows.filter(isRoutineAccessActivity);
    const diagnosticRows = rows.filter(isDiagnosticActivity);
    const importantRows = rows.filter(row => !isRoutineAccessActivity(row) && !isDiagnosticActivity(row));
    const failedLoginCount = rows.filter(row => /failed login|unauthorized|suspicious|locked out/i.test(`${row.activity || ""} ${row.details || ""}`)).length;

    const summary = `
        <div class="activity-summary-grid">
            <article class="activity-summary-card"><span>Important actions</span><strong>${importantRows.length.toLocaleString()}</strong><small>Account and system changes</small></article>
            <article class="activity-summary-card activity-summary-alert"><span>Failed logins</span><strong>${failedLoginCount.toLocaleString()}</strong><small>Security events to review</small></article>
            <article class="activity-summary-card"><span>Routine sign-ins</span><strong>${routineRows.length.toLocaleString()}</strong><small>Successful login and logout</small></article>
            <article class="activity-summary-card"><span>Total records</span><strong>${rows.length.toLocaleString()}</strong><small>All stored activity records</small></article>
        </div>
        <div class="activity-log-note"><strong>Admin view:</strong> Important changes appear first. Routine sign-ins and technical test entries are kept in separate folders. No activity history is deleted.</div>
    `;

    const priority = [
        "Security Alerts",
        "Account Changes",
        "Enrollment Actions",
        "Document Actions",
        "Academic Setup Changes",
        "Schedule Changes",
        "Routine Sign-in History",
        "Other System Events"
    ];
    const groups = new Map(priority.map(name => [name, []]));
    rows.forEach(row => groups.get(activityGroupFor(row)).push(row));

    const groupMarkup = priority.filter(name => groups.get(name).length).map(groupName => {
        const entries = groups.get(groupName).slice().sort((a, b) => activityTimestamp(b) - activityTimestamp(a));
        const folderId = `activity-${groupName.toLowerCase().replace(/[^a-z0-9]+/g, "-")}`;
        const body = entries.map((row, index) => {
            const action = row.activity || row.action || row.activity_type || "System activity";
            const person = formatActivityPerson(row);
            const timestamp = row.created_at || row.activity_date || row.timestamp || "—";
            const details = row.details || row.description || "No additional details.";
            const ip = row.ip_address ? `<div class="activity-detail-ip">IP address: ${escapeHtml(row.ip_address)}</div>` : "";
            const extraClass = index >= 10 ? "activity-extra-row" : "";
            return `<tr class="${extraClass}" ${index >= 10 ? "hidden" : ""}>
                <td class="activity-number">${index + 1}</td>
                <td><span class="activity-action-label">${escapeHtml(action)}</span></td>
                <td>${escapeHtml(person)}</td>
                <td><time>${escapeHtml(timestamp)}</time></td>
                <td><details class="activity-row-detail"><summary>View</summary><div>${escapeHtml(details)}${ip}</div></details></td>
            </tr>`;
        }).join("");
        const moreButton = entries.length > 10 ? `<div class="activity-more-wrap"><button type="button" class="activity-more-button" data-activity-more="${folderId}">Show all ${entries.length.toLocaleString()} records</button></div>` : "";
        const isRoutine = groupName === "Routine Sign-in History" || groupName === "Other System Events";
        return `<details class="activity-log-folder ${isRoutine ? "activity-secondary-folder" : ""}" id="${folderId}">
            <summary>
                <span class="activity-folder-icon" aria-hidden="true">▸</span>
                <span class="activity-folder-heading"><span class="activity-folder-name">${escapeHtml(groupName)}</span><small>${escapeHtml(activityGroupDescription(groupName))}</small></span>
                <span class="activity-folder-count">${entries.length.toLocaleString()} record(s)</span>
                <span class="activity-folder-toggle">⌄</span>
            </summary>
            <div class="activity-folder-content">
                <div class="activity-folder-table-wrap">
                    <table class="activity-folder-table">
                        <thead><tr><th>#</th><th>Activity</th><th>Name (Last, First)</th><th>Date / Time</th><th>Details</th></tr></thead>
                        <tbody>${body}</tbody>
                    </table>
                </div>
                ${moreButton}
            </div>
        </details>`;
    }).join("");

    folderHost.innerHTML = summary + (groupMarkup || '<div class="activity-empty">No activity records match your filters.</div>');
}

function sortAdminRecords(view, rows) {
    const copy = rows.slice();
    const by = (a, b, key) => String(a[key] ?? '').localeCompare(String(b[key] ?? ''), 'en', { numeric: true, sensitivity: 'base' });
    if (view === 'courses') return copy.sort((a, b) => by(a, b, 'course_code'));
    if (view === 'subjects') return copy.sort((a, b) => by(a, b, 'course_code') || by(a, b, 'year_level') || by(a, b, 'semester_name') || by(a, b, 'subject_name') || by(a, b, 'subject_code'));
    if (view === 'sections') return copy.sort((a, b) => by(a, b, 'course_code') || by(a, b, 'year_level') || by(a, b, 'section_name') || by(a, b, 'year_name'));
    if (view === 'schedules') {
        const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return copy.sort((a, b) => by(a, b, 'course_code') || by(a, b, 'section_name') || dayOrder.indexOf(a.day_of_week) - dayOrder.indexOf(b.day_of_week) || by(a, b, 'start_time'));
    }
    if (view === 'academic_years') return copy.sort((a, b) => by(b, a, 'year_name'));
    return copy;
}

function adminFolderHtml(number, title, description, count, content, level = 0, extraClass = '') {
    const levelClass = level > 0 ? 'admin-folder-nested' : 'admin-folder-root';
    const recordWord = count === 1 ? 'record' : 'records';
    return `
        <details class="admin-record-folder ${levelClass} ${extraClass}">
            <summary>
                <span class="admin-folder-number">${escapeHtml(number)}.</span>
                <span class="admin-folder-icon" aria-hidden="true">▸</span>
                <span class="admin-folder-heading">
                    <strong>${escapeHtml(title)}</strong>
                    ${description ? `<small>${escapeHtml(description)}</small>` : ''}
                </span>
                <span class="admin-folder-count">${Number(count).toLocaleString()} ${recordWord}</span>
                <span class="admin-folder-chevron" aria-hidden="true">⌄</span>
            </summary>
            <div class="admin-folder-content">${content}</div>
        </details>
    `;
}

function renderAdminActionCell(view, row) {
    if (view === 'courses' || view === 'subjects') {
        const isActive = String(row.status ?? '').toLowerCase() === 'active';
        const id = view === 'courses' ? row.course_id : row.subject_id;
        return `<td class="admin-folder-actions">
            <button type="button" class="table-action view-action" data-record-action="view" data-record-type="${view}" data-record-id="${Number(id)}">View</button>
            <button type="button" class="table-action edit-action" data-record-action="edit" data-record-type="${view}" data-record-id="${Number(id)}">Edit</button>
            <button type="button" class="table-action ${isActive ? 'deactivate' : 'activate'}" data-record-action="status" data-record-type="${view}" data-record-id="${Number(id)}">${isActive ? 'Deactivate' : 'Activate'}</button>
        </td>`;
    }

    if (view === 'sections') {
        const status = String(row.status ?? '').toLowerCase();
        const isActive = status === 'active';
        const isClosed = status === 'closed';
        return `<td class="admin-folder-actions">
            <button type="button" class="table-action view-action" data-record-action="view" data-record-type="sections" data-record-id="${Number(row.section_id)}">View</button>
            <button type="button" class="table-action edit-action" data-record-action="edit" data-record-type="sections" data-record-id="${Number(row.section_id)}">Edit</button>
            <button type="button" class="table-action ${isActive ? 'deactivate' : 'activate'}" data-record-action="status" data-record-type="sections" data-record-id="${Number(row.section_id)}">${isActive ? 'Deactivate' : 'Activate'}</button>
            ${isClosed ? '' : `<button type="button" class="table-action close-section" data-record-action="close" data-record-type="sections" data-record-id="${Number(row.section_id)}">Close</button>`}
        </td>`;
    }

    if (view === 'schedules') {
        return `<td class="admin-folder-actions">
            <button type="button" class="table-action view-action" data-record-action="view" data-record-type="schedules" data-record-id="${Number(row.schedule_id)}">View</button>
            <button type="button" class="table-action edit-action" data-record-action="edit" data-record-type="schedules" data-record-id="${Number(row.schedule_id)}">Edit</button>
        </td>`;
    }
    return '';
}

function renderAdminFolderTable(view, rows, columns) {
    const hasActions = ['courses', 'subjects', 'sections', 'schedules'].includes(view);
    if (!rows.length) return '<div class="admin-folder-empty">No records in this folder.</div>';
    const head = `<thead><tr><th>#</th>${columns.map(column => `<th>${escapeHtml(prettyLabel(column))}</th>`).join('')}${hasActions ? '<th>Actions</th>' : ''}</tr></thead>`;
    const body = rows.map((row, index) => {
        const cells = `<td class="admin-folder-row-number">${index + 1}</td>` + columns.map(column => `<td>${escapeHtml(formatCell(row[column]))}</td>`).join('');
        return `<tr>${cells}${renderAdminActionCell(view, row)}</tr>`;
    }).join('');
    return `<div class="admin-folder-table-wrap"><table class="admin-folder-table">${head}<tbody>${body}</tbody></table></div>`;
}

function renderCourseFolders(rows) {
    if (!rows.length) return '<div class="admin-folder-empty">No courses match your search or filter.</div>';
    const columns = ['course_code', 'course_name', 'duration_years', 'status'].filter(column => rows.some(row => column in row));
    return rows.map((course, index) => {
        const content = renderAdminFolderTable('courses', [course], columns);
        const description = `${course.duration_years || '—'} year program · ${String(course.status || 'unknown').toUpperCase()}`;
        return adminFolderHtml(index + 1, `${course.course_code || 'Course'} — ${course.course_name || ''}`, description, 1, content);
    }).join('');
}

function renderSubjectFolders(rows) {
    if (!rows.length) return '<div class="admin-folder-empty">No subjects match your search or filter.</div>';

    const assignmentBySubject = new Map();
    (state.scheduleAssignments || []).forEach(assignment => {
        const key = String(assignment.subject_id ?? '');
        if (!assignmentBySubject.has(key)) assignmentBySubject.set(key, []);
        assignmentBySubject.get(key).push(assignment);
    });

    const courseGroups = new Map();
    rows.forEach(subject => {
        const courseKey = String(subject.course_code || 'Other Courses');
        if (!courseGroups.has(courseKey)) {
            courseGroups.set(courseKey, { code: courseKey, name: subject.course_name || '', terms: new Map(), assignedCount: 0 });
        }
        const courseGroup = courseGroups.get(courseKey);
        const matches = assignmentBySubject.get(String(subject.subject_id)) || [];
        const sectionMatches = matches.length ? matches : [{
            section_id: 0,
            section_name: 'No active section assignment',
            year_level: subject.year_level,
            year_name: 'Current / Unassigned',
            semester_name: subject.semester_name || 'Semester not set'
        }];

        sectionMatches.forEach(assignment => {
            const yearLevel = Number(assignment.year_level || subject.year_level || 0);
            const yearName = assignment.year_name || 'School year not assigned';
            const semesterName = assignment.semester_name || subject.semester_name || 'Semester not assigned';
            const termKey = `${yearLevel}|${yearName}|${semesterName}`;
            if (!courseGroup.terms.has(termKey)) {
                courseGroup.terms.set(termKey, { yearLevel, yearName, semesterName, sections: new Map() });
            }
            const termGroup = courseGroup.terms.get(termKey);
            const sectionKey = Number(assignment.section_id || 0) ? String(assignment.section_id) : 'unassigned';
            if (!termGroup.sections.has(sectionKey)) {
                termGroup.sections.set(sectionKey, {
                    name: assignment.section_name || 'Unassigned Section',
                    items: []
                });
            }
            termGroup.sections.get(sectionKey).items.push(subject);
            courseGroup.assignedCount += 1;
        });
    });

    const courses = [...courseGroups.values()].sort((a, b) => a.code.localeCompare(b.code, 'en', { numeric: true, sensitivity: 'base' }));
    return courses.map((course, courseIndex) => {
        const terms = [...course.terms.values()].sort((a, b) => a.yearLevel - b.yearLevel || a.yearName.localeCompare(b.yearName) || a.semesterName.localeCompare(b.semesterName));
        const termMarkup = terms.map((term, termIndex) => {
            const sections = [...term.sections.values()].sort((a, b) => a.name.localeCompare(b.name, 'en', { numeric: true, sensitivity: 'base' }));
            const sectionMarkup = sections.map((section, sectionIndex) => {
                const uniqueSubjects = [...new Map(section.items.map(item => [Number(item.subject_id), item])).values()]
                    .sort((a, b) => String(a.subject_name || '').localeCompare(String(b.subject_name || ''), 'en', { sensitivity: 'base' }) || String(a.subject_code || '').localeCompare(String(b.subject_code || '')));
                const columns = ['subject_code', 'subject_name', 'units', 'status'].filter(column => uniqueSubjects.some(row => column in row));
                const sectionContent = renderAdminFolderTable('subjects', uniqueSubjects, columns);
                return adminFolderHtml(sectionIndex + 1, section.name.startsWith('Section') || section.name.includes('Unassigned') ? section.name : `Section ${section.name}`, 'Subjects assigned to this section', uniqueSubjects.length, sectionContent, 2);
            }).join('');
            return adminFolderHtml(termIndex + 1, `Year ${term.yearLevel || '—'} · ${term.yearName} · ${term.semesterName}`, 'Curriculum subjects for this academic term', sections.reduce((sum, section) => sum + section.items.length, 0), sectionMarkup, 1);
        }).join('');
        return adminFolderHtml(courseIndex + 1, `${course.code} — ${course.name}`, 'Open to view year level, semester, section, and assigned subjects', course.assignedCount, termMarkup);
    }).join('');
}

function renderSectionFolders(rows) {
    if (!rows.length) return '<div class="admin-folder-empty">No sections match your search or filter.</div>';
    const byCourse = new Map();
    rows.forEach(row => {
        const key = String(row.course_code || 'Other Courses');
        if (!byCourse.has(key)) byCourse.set(key, { code: key, name: row.course_name || '', rows: [] });
        byCourse.get(key).rows.push(row);
    });
    const courses = [...byCourse.values()].sort((a, b) => a.code.localeCompare(b.code, 'en', { numeric: true, sensitivity: 'base' }));
    const columns = ['section_name', 'year_level', 'year_name', 'semester_name', 'capacity', 'enrolled_count', 'remaining_slots', 'assigned_subject_count', 'schedule_count', 'status'];
    return courses.map((course, courseIndex) => {
        const sections = course.rows.slice().sort((a, b) => Number(a.year_level || 0) - Number(b.year_level || 0) || String(a.year_name || '').localeCompare(String(b.year_name || '')) || String(a.semester_name || '').localeCompare(String(b.semester_name || '')) || String(a.section_name || '').localeCompare(String(b.section_name || ''), 'en', { numeric: true, sensitivity: 'base' }));
        const sectionFolders = sections.map((section, sectionIndex) => {
            const title = `Section ${section.section_name || '—'}`;
            const description = `Year ${section.year_level || '—'} · ${section.year_name || 'School year'} · ${section.semester_name || 'Semester'}`;
            return adminFolderHtml(sectionIndex + 1, title, description, 1, renderAdminFolderTable('sections', [section], columns.filter(column => column in section)), 1, 'admin-section-folder');
        }).join('');
        return adminFolderHtml(courseIndex + 1, `${course.code} — ${course.name}`, 'Sections grouped by year level and academic term', sections.length, sectionFolders);
    }).join('');
}

function renderScheduleFolders(rows) {
    if (!rows.length) return '<div class="admin-folder-empty">No class schedules match your search or filter.</div>';
    const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const courses = new Map();
    rows.forEach(row => {
        const courseKey = String(row.course_code || 'Other Courses');
        if (!courses.has(courseKey)) courses.set(courseKey, { code: courseKey, name: row.course_name || '', sections: new Map() });
        const sectionKey = `${row.section_id || row.section_name}|${row.year_name || ''}|${row.semester_name || ''}|${row.year_level || ''}`;
        const course = courses.get(courseKey);
        if (!course.sections.has(sectionKey)) {
            course.sections.set(sectionKey, {
                name: row.section_name || 'Unnamed Section',
                year: row.year_name || 'School year',
                semester: row.semester_name || 'Semester',
                yearLevel: row.year_level || '—',
                rows: []
            });
        }
        course.sections.get(sectionKey).rows.push(row);
    });

    const courseList = [...courses.values()].sort((a, b) => a.code.localeCompare(b.code, 'en', { numeric: true, sensitivity: 'base' }));
    return courseList.map((course, courseIndex) => {
        const sectionList = [...course.sections.values()].sort((a, b) => String(a.name).localeCompare(String(b.name), 'en', { numeric: true, sensitivity: 'base' }) || String(a.year).localeCompare(String(b.year)));
        const sectionMarkup = sectionList.map((section, sectionIndex) => {
            const byDay = new Map();
            section.rows.forEach(row => {
                const day = String(row.day_of_week || 'Other Day');
                if (!byDay.has(day)) byDay.set(day, []);
                byDay.get(day).push(row);
            });
            const orderedDays = [...byDay.keys()].sort((a, b) => {
                const ai = dayOrder.indexOf(a), bi = dayOrder.indexOf(b);
                return (ai < 0 ? 99 : ai) - (bi < 0 ? 99 : bi);
            });
            const dayMarkup = orderedDays.map((day, dayIndex) => {
                const dayRows = byDay.get(day).slice().sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')) || String(a.subject_code || '').localeCompare(String(b.subject_code || '')));
                const columns = ['subject_code', 'subject_name', 'units', 'start_time', 'end_time', 'room'].filter(column => dayRows.some(row => column in row));
                return adminFolderHtml(dayIndex + 1, day, 'Class meetings arranged by start time', dayRows.length, renderAdminFolderTable('schedules', dayRows, columns), 2, 'admin-day-folder');
            }).join('');
            const sectionBody = `${dayMarkup}<div class="admin-folder-close-row"><button type="button" class="admin-folder-close-button" data-close-section-folder>⌃ Close Section</button></div>`;
            return adminFolderHtml(sectionIndex + 1, `Section ${section.name}`, `Year ${section.yearLevel} · ${section.year} · ${section.semester}`, section.rows.length, sectionBody, 1, 'admin-section-folder');
        }).join('');
        return adminFolderHtml(courseIndex + 1, `${course.code} — ${course.name}`, 'Open a section, then choose a weekday to view its timetable', sectionList.reduce((sum, section) => sum + section.rows.length, 0), sectionMarkup);
    }).join('');
}

function renderRecords(view) {
    const config = endpointMap[view];
    if (!config) return;

    byId('recordsTitle').textContent = config.label;
    byId('recordsDescription').textContent = config.description;
    byId('recordsEyebrow').textContent = `${config.label.toUpperCase()} · DATABASE RECORDS`;

    byId('addCourseButton').hidden = view !== 'courses';
    byId('addSubjectButton').hidden = view !== 'subjects';
    byId('addSectionButton').hidden = view !== 'sections';
    byId('addScheduleButton').hidden = view !== 'schedules';
    byId('statusFilter').hidden = view === 'activity_logs';
    byId('activityDateFilter').hidden = view !== 'activity_logs';

    const recordFolders = byId('recordFolders');
    const activityFolders = byId('activityLogFolders');
    const tableWrap = byId('recordsTable').closest('.table-wrap');
    recordFolders.hidden = true;
    recordFolders.innerHTML = '';
    activityFolders.hidden = true;
    tableWrap.hidden = false;

    const folderedViews = ['courses', 'subjects', 'sections', 'schedules'];
    const isFolderedView = folderedViews.includes(view);

    if (state.errors[view]) {
        byId('recordCount').textContent = 'Records unavailable';
        if (isFolderedView) {
            tableWrap.hidden = true;
            recordFolders.hidden = false;
            recordFolders.innerHTML = `<div class="admin-folder-empty error-state">${escapeHtml(state.errors[view])}</div>`;
        } else {
            byId('recordsTable').querySelector('thead').innerHTML = '';
            byId('recordsTable').querySelector('tbody').innerHTML = `<tr><td class="empty-cell">${escapeHtml(state.errors[view])}</td></tr>`;
        }
        state.filteredRows = [];
        return;
    }

    const rows = state.datasets[view] || [];
    const query = byId('searchInput').value.trim().toLowerCase();
    const selectedStatus = byId('statusFilter').value;
    const dateRange = view === 'activity_logs' ? byId('activityDateFilter').value : 'all';
    const now = new Date();
    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
    const cutoff = dateRange === 'today' ? startOfToday
        : dateRange === '7d' ? now.getTime() - 7 * 86400000
        : dateRange === '30d' ? now.getTime() - 30 * 86400000
        : 0;

    let filtered = rows.filter(row => {
        const matchesSearch = !query || rowSearchText(row).includes(query);
        const rowStatus = String(row.status ?? row.verification_status ?? '').toLowerCase();
        const matchesStatus = view === 'activity_logs' || selectedStatus === 'all' || rowStatus === selectedStatus;
        const matchesDate = view !== 'activity_logs' || !cutoff || activityTimestamp(row) >= cutoff;
        return matchesSearch && matchesStatus && matchesDate;
    });

    if (view === 'activity_logs') {
        state.filteredRows = filtered;
        byId('recordCount').textContent = `${filtered.length.toLocaleString()} activity record(s)`;
        byId('tableFooterText').textContent = 'Important actions are shown first. Entries are newest first; names are displayed Last, First. Routine sign-ins remain available in their own folder.';
        renderActivityLogFolders(filtered);
        return;
    }

    filtered = sortAdminRecords(view, filtered);
    state.filteredRows = filtered;
    byId('recordCount').textContent = `${filtered.length.toLocaleString()} of ${rows.length.toLocaleString()} records`;
    byId('tableFooterText').textContent = `${config.label}: ${rows.length.toLocaleString()} total records returned by the API`;

    if (isFolderedView) {
        tableWrap.hidden = true;
        recordFolders.hidden = false;
        const folderRenderers = {
            courses: renderCourseFolders,
            subjects: renderSubjectFolders,
            sections: renderSectionFolders,
            schedules: renderScheduleFolders
        };
        recordFolders.innerHTML = filtered.length
            ? folderRenderers[view](filtered)
            : `<div class="admin-folder-empty">No ${escapeHtml(config.label.toLowerCase())} match your search or selected filter.</div>`;
        byId('recordsTable').querySelector('thead').innerHTML = '';
        byId('recordsTable').querySelector('tbody').innerHTML = '';
        return;
    }

    const preferredColumns = {
        courses: ['course_code', 'course_name', 'duration_years', 'status'],
        subjects: ['course_code', 'subject_code', 'subject_name', 'semester_name', 'year_level', 'units', 'status'],
        sections: ['course_code', 'year_level', 'section_name', 'capacity', 'status', 'year_name', 'semester_name'],
        schedules: ['course_code', 'section_name', 'subject_code', 'subject_name', 'day_of_week', 'start_time', 'end_time', 'room']
    };
    const availableColumns = new Set(rows.flatMap(row => Object.keys(row)));
    const columns = (preferredColumns[view] || []).filter(column => availableColumns.has(column));
    const head = byId('recordsTable').querySelector('thead');
    const body = byId('recordsTable').querySelector('tbody');

    if (!columns.length) {
        head.innerHTML = '';
        body.innerHTML = `<tr><td class="empty-cell">No ${escapeHtml(config.label.toLowerCase())} records found.</td></tr>`;
        return;
    }
    head.innerHTML = `<tr><th>#</th>${columns.map(column => `<th>${escapeHtml(prettyLabel(column))}</th>`).join('')}</tr>`;
    body.innerHTML = filtered.length
        ? filtered.map((row, index) => `<tr><td>${index + 1}</td>${columns.map(column => `<td>${escapeHtml(formatCell(row[column]))}</td>`).join('')}</tr>`).join('')
        : `<tr><td class="empty-cell" colspan="${columns.length + 1}">No records match your search or selected filter.</td></tr>`;
}

function switchView(view) {
    if (view !== "overview" && !endpointMap[view]) return;

    state.currentView = view;

    byId("overviewView").classList.toggle("hidden", view !== "overview");
    byId("recordsView").classList.toggle("hidden", view === "overview");

    document.querySelectorAll(".nav-link").forEach(button => {
        button.classList.toggle("active", button.dataset.view === view);
    });

    const title = view === "overview"
        ? "Dashboard Overview"
        : endpointMap[view].label;

    byId("pageTitle").textContent = title;
    byId("breadcrumbCurrent").textContent = title;

    byId("pageSubtitle").textContent = view === "overview"
        ? "Monitor academic setup and recent system activity."
        : endpointMap[view].description;

    if (view !== "overview") renderRecords(view);

    byId("sidebar").classList.remove("open");
}

function populateCourseSelect(selectId, selectedId = "") {
    const select = byId(selectId);
    const courses = state.datasets.courses || [];

    select.innerHTML = `<option value="">Select Course</option>` + courses.map(course => {
        const id = Number(course.course_id);
        const active = String(course.status ?? "").toLowerCase() === "active";
        const selected = Number(selectedId) === id ? "selected" : "";
        const disabled = !active && Number(selectedId) !== id ? "disabled" : "";

        return `
            <option value="${id}" ${selected} ${disabled}>
                ${escapeHtml(course.course_code)} - ${escapeHtml(course.course_name)}
                ${active ? "" : " (Inactive)"}
            </option>
        `;
    }).join("");
}

function populateSectionOptions(section = null) {
    populateCourseSelect("sectionCourse", section?.course_id ?? "");

    const yearSelect = byId("sectionAcademicYear");
    const years = state.datasets.academic_years || [];
    const selectedYearId = Number(section?.academic_year_id ?? 0);

    yearSelect.innerHTML =
        `<option value="">Select Academic Year</option>` +
        years.map(year => {
            const id = getAcademicYearId(year);
            const status = String(year.status ?? "").toLowerCase();
            const selected = selectedYearId === id ? "selected" : "";
            const disabled = status !== "open" && selectedYearId !== id
                ? "disabled"
                : "";
            const suffix = status ? ` (${status})` : "";

            return `
                <option value="${id}" ${selected} ${disabled}>
                    ${escapeHtml(getAcademicYearLabel(year))}${escapeHtml(suffix)}
                </option>
            `;
        }).join("");

    if (!years.length) {
        yearSelect.innerHTML =
            `<option value="">No academic years returned by the API</option>`;
    }
}

function openModal(id) {
    byId(id).hidden = false;
    document.body.classList.add("modal-open");
}

function closeModal(id) {
    const modal = byId(id);
    if (modal) modal.hidden = true;

    const modalIds = [
        "courseModal",
        "subjectModal",
        "sectionModal",
        "scheduleModal",
        "detailsModal"
    ];

    if (modalIds.every(modalId => byId(modalId).hidden)) {
        document.body.classList.remove("modal-open");
    }
}

function showFormError(id, message) {
    const box = byId(id);
    box.textContent = message;
    box.hidden = false;
}

function showRecordDetails(record, type) {
    byId("detailsTitle").textContent =
        `${endpointMap[type].label} Details`;

    byId("detailsContent").innerHTML =
        Object.entries(record).map(([key, value]) => `
            <div class="details-row">
                <span>${escapeHtml(prettyLabel(key))}</span>
                <strong>${escapeHtml(formatCell(value))}</strong>
            </div>
        `).join("");

    openModal("detailsModal");
}

/* COURSES */

function openCourseModal(course = null) {
    byId("courseForm").reset();
    byId("courseFormMessage").hidden = true;

    if (course) {
        byId("courseModalTitle").textContent = "Edit Course";
        byId("saveCourseButton").textContent = "Save Changes";

        byId("courseId").value = course.course_id ?? "";
        byId("courseCode").value = course.course_code ?? "";
        byId("courseName").value = course.course_name ?? "";
        byId("courseDescription").value = course.description ?? "";
        byId("durationYears").value = course.duration_years ?? 4;
    } else {
        byId("courseModalTitle").textContent = "Add New Course";
        byId("saveCourseButton").textContent = "Save Course";

        byId("courseId").value = "";
        byId("durationYears").value = 4;
    }

    openModal("courseModal");
}

async function saveCourse(event) {
    event.preventDefault();

    const id = byId("courseId").value.trim();

    const payload = {
        action: id ? "update" : "add",
        course_code: byId("courseCode").value.trim().toUpperCase(),
        course_name: byId("courseName").value.trim(),
        description: byId("courseDescription").value.trim(),
        duration_years: Number(byId("durationYears").value)
    };

    if (id) payload.course_id = Number(id);

    if (!payload.course_code || !payload.course_name) {
        showFormError(
            "courseFormMessage",
            "Please complete the course code and course name."
        );
        return;
    }

    if (
        !Number.isInteger(payload.duration_years) ||
        payload.duration_years < 1 ||
        payload.duration_years > 10
    ) {
        showFormError(
            "courseFormMessage",
            "Duration must be a whole number from 1 to 10 years."
        );
        return;
    }

    setButtonBusy("saveCourseButton", true, "Saving...");

    try {
        const result = await requestJson(endpointMap.courses.url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        closeModal("courseModal");
        showToast(result.message || "Course saved successfully.");

        await loadAllDatasets();
        switchView("courses");
    } catch (error) {
        showFormError("courseFormMessage", error.message);
    } finally {
        setButtonBusy(
            "saveCourseButton",
            false,
            id ? "Save Changes" : "Save Course"
        );
    }
}

async function changeCourseStatus(course) {
    const newStatus =
        String(course.status).toLowerCase() === "active"
            ? "inactive"
            : "active";

    if (!window.confirm(
        `Are you sure you want to ${newStatus === "active" ? "activate" : "deactivate"} ${course.course_code}?`
    )) return;

    try {
        const result = await requestJson(endpointMap.courses.url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                action: "update_status",
                course_id: Number(course.course_id),
                status: newStatus
            })
        });

        showToast(result.message || "Course status updated.");

        await loadAllDatasets();
        switchView("courses");
    } catch (error) {
        showToast(error.message);
    }
}

/* SUBJECTS */

function openSubjectModal(subject = null) {
    byId("subjectForm").reset();
    byId("subjectFormMessage").hidden = true;

    populateCourseSelect("subjectCourse", subject?.course_id ?? "");

    if (subject) {
        byId("subjectModalTitle").textContent = "Edit Subject";
        byId("saveSubjectButton").textContent = "Save Changes";

        byId("subjectId").value = subject.subject_id ?? "";
        byId("subjectSemester").value = subject.semester_id ?? "";
        byId("subjectYearLevel").value = subject.year_level ?? "";
        byId("subjectCode").value = subject.subject_code ?? "";
        byId("subjectName").value = subject.subject_name ?? "";
        byId("subjectUnits").value = subject.units ?? 3;
        byId("subjectDescription").value = subject.description ?? "";
    } else {
        byId("subjectModalTitle").textContent = "Add New Subject";
        byId("saveSubjectButton").textContent = "Save Subject";

        byId("subjectId").value = "";
        byId("subjectUnits").value = 3;
    }

    openModal("subjectModal");
}

async function saveSubject(event) {
    event.preventDefault();

    const id = byId("subjectId").value.trim();

    const payload = {
        action: id ? "update" : "add",
        course_id: Number(byId("subjectCourse").value),
        semester_id: Number(byId("subjectSemester").value),
        year_level: Number(byId("subjectYearLevel").value),
        subject_code: byId("subjectCode").value.trim().toUpperCase(),
        subject_name: byId("subjectName").value.trim(),
        units: Number(byId("subjectUnits").value),
        description: byId("subjectDescription").value.trim()
    };

    if (id) payload.subject_id = Number(id);

    if (
        !payload.course_id ||
        !payload.semester_id ||
        !payload.year_level ||
        !payload.subject_code ||
        !payload.subject_name
    ) {
        showFormError(
            "subjectFormMessage",
            "Please complete all required subject fields."
        );
        return;
    }

    if (!Number.isFinite(payload.units) || payload.units <= 0) {
        showFormError(
            "subjectFormMessage",
            "Units must be greater than zero."
        );
        return;
    }

    setButtonBusy("saveSubjectButton", true, "Saving...");

    try {
        const result = await requestJson(endpointMap.subjects.url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        closeModal("subjectModal");
        showToast(result.message || "Subject saved successfully.");

        await loadAllDatasets();
        switchView("subjects");
    } catch (error) {
        showFormError("subjectFormMessage", error.message);
    } finally {
        setButtonBusy(
            "saveSubjectButton",
            false,
            id ? "Save Changes" : "Save Subject"
        );
    }
}

async function changeSubjectStatus(subject) {
    const newStatus =
        String(subject.status).toLowerCase() === "active"
            ? "inactive"
            : "active";

    if (!window.confirm(
        `Are you sure you want to ${newStatus === "active" ? "activate" : "deactivate"} ${subject.subject_code}?`
    )) return;

    try {
        const result = await requestJson(endpointMap.subjects.url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                action: "update_status",
                subject_id: Number(subject.subject_id),
                status: newStatus
            })
        });

        showToast(result.message || "Subject status updated.");

        await loadAllDatasets();
        switchView("subjects");
    } catch (error) {
        showToast(error.message);
    }
}

/* SECTIONS */

function openSectionModal(section = null) {
    byId("sectionForm").reset();
    byId("sectionFormMessage").hidden = true;

    populateSectionOptions(section);

    if (section) {
        byId("sectionModalTitle").textContent = "Edit Section";
        byId("saveSectionButton").textContent = "Save Changes";

        byId("sectionId").value = section.section_id ?? "";
        byId("sectionSemester").value = section.semester_id ?? "";
        byId("sectionYearLevel").value = section.year_level ?? "";
        byId("sectionName").value = section.section_name ?? "";
        byId("sectionCapacity").value = section.capacity ?? 45;
    } else {
        byId("sectionModalTitle").textContent = "Add New Section";
        byId("saveSectionButton").textContent = "Save Section";

        byId("sectionId").value = "";
        byId("sectionCapacity").value = 45;
    }

    openModal("sectionModal");
}

async function saveSection(event) {
    event.preventDefault();

    const id = byId("sectionId").value.trim();

    const payload = {
        action: id ? "update" : "add",
        academic_year_id: Number(byId("sectionAcademicYear").value),
        semester_id: Number(byId("sectionSemester").value),
        course_id: Number(byId("sectionCourse").value),
        year_level: Number(byId("sectionYearLevel").value),
        section_name: byId("sectionName").value.trim().toUpperCase(),
        capacity: Number(byId("sectionCapacity").value)
    };

    if (id) payload.section_id = Number(id);

    if (
        !payload.academic_year_id ||
        !payload.semester_id ||
        !payload.course_id ||
        !payload.year_level ||
        !payload.section_name
    ) {
        showFormError(
            "sectionFormMessage",
            "Please complete all required section fields."
        );
        return;
    }

    if (
        !Number.isInteger(payload.year_level) ||
        payload.year_level < 1 ||
        payload.year_level > 4
    ) {
        showFormError(
            "sectionFormMessage",
            "Please select a valid year level."
        );
        return;
    }

    if (
        !Number.isInteger(payload.capacity) ||
        payload.capacity < 1 ||
        payload.capacity > 45
    ) {
        showFormError(
            "sectionFormMessage",
            "Capacity must be between 1 and 45 students."
        );
        return;
    }

    setButtonBusy("saveSectionButton", true, "Saving...");

    try {
        const result = await requestJson(endpointMap.sections.url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        closeModal("sectionModal");
        showToast(result.message || "Section saved successfully.");

        await loadAllDatasets();
        switchView("sections");
    } catch (error) {
        showFormError("sectionFormMessage", error.message);
    } finally {
        setButtonBusy(
            "saveSectionButton",
            false,
            id ? "Save Changes" : "Save Section"
        );
    }
}

/* CLASS SCHEDULES */

async function loadScheduleAssignments(selectedId = "") {
    const select = byId("scheduleSectionSubject");
    select.innerHTML = `<option value="">Loading subject assignments...</option>`;

    const result = await requestJson(
        `${endpointMap.schedules.url}?action=assignments`
    );

    const assignments = Array.isArray(result.assignments)
        ? result.assignments
        : [];

    state.scheduleAssignments = assignments;

    if (!assignments.length) {
        select.innerHTML = `<option value="">No section-subject assignments found</option>`;
        return;
    }

    select.innerHTML =
        `<option value="">Select section and subject</option>` +
        assignments.map(item => {
            const id = Number(item.section_subject_id);
            const selected = Number(selectedId) === id ? "selected" : "";
            const course = item.course_code || "Course";
            const section = item.section_name || "Section";
            const subject = item.subject_code || item.subject_name || "Subject";
            const year = item.year_name || "";
            const semester = item.semester_name || "";

            return `
                <option value="${id}" ${selected}>
                    ${escapeHtml(course)} · ${escapeHtml(section)} ·
                    ${escapeHtml(subject)} · ${escapeHtml(year)} · ${escapeHtml(semester)}
                </option>
            `;
        }).join("");
}

async function openScheduleModal(schedule = null) {
    byId("scheduleForm").reset();
    byId("scheduleFormMessage").hidden = true;

    byId("scheduleId").value = schedule?.schedule_id ?? "";
    byId("scheduleModalTitle").textContent =
        schedule ? "Edit Class Schedule" : "Add Class Schedule";
    byId("saveScheduleButton").textContent =
        schedule ? "Save Changes" : "Save Schedule";

    byId("scheduleDay").value = schedule?.day_of_week ?? "";
    byId("scheduleStartTime").value =
        schedule?.start_time ? String(schedule.start_time).slice(0, 5) : "";
    byId("scheduleEndTime").value =
        schedule?.end_time ? String(schedule.end_time).slice(0, 5) : "";
    byId("scheduleRoom").value = schedule?.room ?? "";

    openModal("scheduleModal");

    try {
        await loadScheduleAssignments(schedule?.section_subject_id ?? "");
    } catch (error) {
        showFormError("scheduleFormMessage", error.message);
    }
}

async function saveSchedule(event) {
    event.preventDefault();

    const id = byId("scheduleId").value.trim();

    const payload = {
        action: id ? "update" : "add",
        section_subject_id: Number(byId("scheduleSectionSubject").value),
        day_of_week: byId("scheduleDay").value,
        start_time: byId("scheduleStartTime").value,
        end_time: byId("scheduleEndTime").value,
        room: byId("scheduleRoom").value.trim()
    };

    if (id) {
        payload.schedule_id = Number(id);
    }

    if (
        !payload.section_subject_id ||
        !payload.day_of_week ||
        !payload.start_time ||
        !payload.end_time
    ) {
        showFormError(
            "scheduleFormMessage",
            "Please complete the subject assignment, day, start time, and end time."
        );
        return;
    }

    if (payload.start_time >= payload.end_time) {
        showFormError(
            "scheduleFormMessage",
            "End time must be later than start time."
        );
        return;
    }

    setButtonBusy("saveScheduleButton", true, "Saving...");

    try {
        const result = await requestJson(endpointMap.schedules.url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        closeModal("scheduleModal");
        showToast(result.message || "Class schedule saved successfully.");

        await loadAllDatasets();
        switchView("schedules");
    } catch (error) {
        showFormError("scheduleFormMessage", error.message);
    } finally {
        setButtonBusy(
            "saveScheduleButton",
            false,
            id ? "Save Changes" : "Save Schedule"
        );
    }
}

async function changeSectionStatus(section, newStatus) {
    const confirmed = window.confirm(
        `Change section ${section.section_name} status to ${newStatus}?`
    );

    if (!confirmed) return;

    try {
        const result = await requestJson(endpointMap.sections.url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                action: "status",
                section_id: Number(section.section_id),
                status: newStatus
            })
        });

        showToast(result.message || "Section status updated.");

        await loadAllDatasets();
        switchView("sections");
    } catch (error) {
        showToast(error.message);
    }
}

function setButtonBusy(id, busy, text) {
    const button = byId(id);
    button.disabled = busy;
    button.textContent = text;
}

/* TABLE ACTIONS */

byId("recordsView").addEventListener("click", event => {
    const closeButton = event.target.closest("[data-close-section-folder]");
    if (closeButton) {
        closeButton.closest("details.admin-section-folder")?.removeAttribute("open");
        return;
    }

    const button = event.target.closest("[data-record-action]");
    if (!button) return;

    const type = button.dataset.recordType;
    const action = button.dataset.recordAction;
    const id = Number(button.dataset.recordId);
    let record = null;

    if (type === "courses") record = getCourseById(id);
    if (type === "subjects") record = getSubjectById(id);
    if (type === "sections") record = getSectionById(id);
    if (type === "schedules") record = getScheduleById(id);

    if (!record) {
        showToast("Record was not found.");
        return;
    }

    if (action === "view") getRecordDetails(record, type);
    if (action === "edit") {
        if (type === "courses") openCourseModal(record);
        if (type === "subjects") openSubjectModal(record);
        if (type === "sections") openSectionModal(record);
        if (type === "schedules") openScheduleModal(record);
    }
    if (action === "status") {
        if (type === "courses") changeCourseStatus(record);
        if (type === "subjects") changeSubjectStatus(record);
        if (type === "sections") {
            const current = String(record.status ?? "").toLowerCase();
            changeSectionStatus(record, current === "active" ? "inactive" : "active");
        }
    }
    if (action === "close" && type === "sections") changeSectionStatus(record, "closed");
});

/* CSV EXPORT */

function exportCurrentCsv() {
    const view = state.currentView;
    const rows = state.filteredRows;

    if (!rows.length) {
        showToast("There are no records to export.");
        return;
    }

    const columns = [
        ...new Set(rows.flatMap(row => Object.keys(row)))
    ];

    const quote = value =>
        `"${String(formatCell(value)).replace(/"/g, '""')}"`;

    const lines = [
        columns.map(quote).join(","),
        ...rows.map(row =>
            columns.map(column => quote(row[column])).join(",")
        )
    ];

    const blob = new Blob(
        ["\uFEFF" + lines.join("\r\n")],
        { type: "text/csv;charset=utf-8;" }
    );

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download =
        `skwela-${view}-${new Date().toISOString().slice(0, 10)}.csv`;

    document.body.appendChild(link);
    link.click();
    link.remove();

    URL.revokeObjectURL(url);

    showToast("CSV export prepared.");
}

let toastTimer;

function showToast(message) {
    const toast = byId("toast");

    toast.textContent = message;
    toast.classList.add("show");

    clearTimeout(toastTimer);

    toastTimer = setTimeout(() => {
        toast.classList.remove("show");
    }, 2800);
}

/* NAVIGATION AND CONTROLS */

document.querySelectorAll("[data-view]").forEach(button => {
    button.addEventListener("click", () => switchView(button.dataset.view));
});

document.querySelectorAll("[data-open-view]").forEach(button => {
    button.addEventListener("click", () => switchView(button.dataset.openView));
});

byId("refreshButton").addEventListener("click", loadAllDatasets);
byId("recordsRefreshButton").addEventListener("click", loadAllDatasets);

byId("searchInput").addEventListener("input", () => {
    if (state.currentView !== "overview") {
        renderRecords(state.currentView);
    }
});

byId("statusFilter").addEventListener("change", () => {
    if (state.currentView !== "overview") {
        renderRecords(state.currentView);
    }
});

byId("activityDateFilter").addEventListener("change", () => {
    if (state.currentView === "activity_logs") renderRecords("activity_logs");
});

byId("activityLogFolders").addEventListener("click", event => {
    const button = event.target.closest("[data-activity-more]");
    if (!button) return;
    const folder = document.getElementById(button.dataset.activityMore);
    if (!folder) return;
    const extraRows = [...folder.querySelectorAll(".activity-extra-row")];
    const showingAll = extraRows.some(row => row.hidden);
    extraRows.forEach(row => { row.hidden = !showingAll; });
    button.textContent = showingAll ? "Show fewer records" : `Show all ${folder.querySelectorAll("tbody tr").length.toLocaleString()} records`;
});

byId("clearFiltersButton").addEventListener("click", () => {
    byId("searchInput").value = "";
    byId("statusFilter").value = "all";
    byId("activityDateFilter").value = "all";

    if (state.currentView !== "overview") {
        renderRecords(state.currentView);
    }
});

byId("exportButton").addEventListener("click", exportCurrentCsv);

byId("addCourseButton").addEventListener("click", () => openCourseModal());
byId("addSubjectButton").addEventListener("click", () => openSubjectModal());
byId("addSectionButton").addEventListener("click", () => openSectionModal());
byId("addScheduleButton").addEventListener("click", () => openScheduleModal());

byId("courseForm").addEventListener("submit", saveCourse);
byId("subjectForm").addEventListener("submit", saveSubject);
byId("sectionForm").addEventListener("submit", saveSection);
byId("scheduleForm").addEventListener("submit", saveSchedule);

document.querySelectorAll("[data-close-modal]").forEach(button => {
    button.addEventListener("click", () => {
        closeModal(button.dataset.closeModal);
    });
});

document.querySelectorAll(".modal-overlay").forEach(modal => {
    modal.addEventListener("click", event => {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
});

document.addEventListener("keydown", event => {
    if (event.key === "Escape") {
        ["courseModal", "subjectModal", "sectionModal", "scheduleModal", "detailsModal"].forEach(closeModal);
    }
});

byId("menuToggle").addEventListener("click", () => {
    byId("sidebar").classList.toggle("open");
});

byId("todayDate").textContent = new Intl.DateTimeFormat("en-PH", {
    month: "short",
    day: "numeric",
    year: "numeric"
}).format(new Date());

const requestedView = new URLSearchParams(window.location.search).get('view');
if (requestedView && endpointMap[requestedView]) switchView(requestedView);
loadAllDatasets();
