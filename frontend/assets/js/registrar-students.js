const SR_STUDENTS_API = "../../backend/routes/registrar_students.php";
const SR_DOCUMENT_API = "../../backend/routes/documents.php";
const SR_PREVIEW_API = "../../backend/routes/document_preview.php";

let srStudents = [];
let srCurrentStudentId = 0;
let srCurrentDocuments = [];
let srHasLoadedStudents = false;

function srEscapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
    }[char]));
}

function srNormalizeStatus(value) {
    return String(value ?? "pending")
        .toLowerCase()
        .replace(/[\s-]+/g, "_");
}

function srStatusBadge(value) {
    const status = srNormalizeStatus(value);

    const allowed = [
        "pending",
        "under_review",
        "approved",
        "rejected",
        "verified"
    ];

    const cssClass = allowed.includes(status) ? status : "default";

    return `
        <span class="status ${cssClass}">
            ${srEscapeHTML(status.replace(/_/g, " "))}
        </span>
    `;
}

function srToast(message, type = "success") {
    if (typeof showToast === "function") {
        showToast(message, type);
        return;
    }

    const toast = document.getElementById("toast");

    if (!toast) return;

    toast.textContent = message;
    toast.className = "toast show" + (type === "error" ? " error" : "");

    window.setTimeout(() => {
        toast.className = "toast";
    }, 3500);
}

function srShowMessage(message, type = "error") {
    const box = document.getElementById("srPageMessage");

    if (!box) return;

    box.textContent = message;
    box.className = `sr-page-message ${type}`;
}

function srClearMessage() {
    const box = document.getElementById("srPageMessage");

    if (!box) return;

    box.textContent = "";
    box.className = "sr-page-message";
}

async function srRequestJSON(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options,
        headers: {
            ...(options.headers || {})
        }
    });

    const raw = await response.text();

    let data;

    try {
        data = JSON.parse(raw);
    } catch (error) {
        throw new Error(
            "The server returned an invalid response. Check the PHP route."
        );
    }

    if (!response.ok || data.success === false) {
        throw new Error(
            data.message || `Request failed (${response.status}).`
        );
    }

    return data;
}


async function loadStudentRecords(forceReload = false) {
    const folderHost = document.getElementById("studentSectionFolders");

    if (!folderHost) return;

    if (srHasLoadedStudents && !forceReload) {
        srRenderStudents();
        return;
    }

    folderHost.innerHTML = `<div class="sr-folder-empty">Loading student records...</div>`;

    srClearMessage();

    try {
        const data = await srRequestJSON(SR_STUDENTS_API);

        srStudents = Array.isArray(data.students)
            ? data.students
            : [];

        srHasLoadedStudents = true;

        document.getElementById("totalStudents").textContent =
            srStudents.length;

        document.getElementById("totalStudentApplications").textContent =
            srStudents.reduce(
                (sum, item) => sum + Number(item.application_count || 0),
                0
            );

        document.getElementById("totalPendingStudentDocuments").textContent =
            srStudents.reduce(
                (sum, item) => sum + Number(item.pending_document_count || 0),
                0
            );

        srRenderStudents();

    } catch (error) {
        folderHost.innerHTML = `<div class="sr-folder-empty">${srEscapeHTML(error.message)}</div>`;

        srShowMessage(error.message);
    }
}



function srRenderStudents() {
    const host = document.getElementById("studentSectionFolders");
    if (!host) return;

    const search = (
        document.getElementById("studentSearch")?.value || ""
    ).trim().toLowerCase();

    const filter = document.getElementById("studentFilter")?.value || "";

    const alpha = (a, b) => String(a || "").localeCompare(
        String(b || ""), undefined, { sensitivity: "base", numeric: true }
    );

    const filtered = srStudents.filter(student => {
        const searchText = [
            student.display_name,
            student.student_number,
            student.email,
            student.current_course_code,
            student.current_section_name
        ].join(" ").toLowerCase();

        const appCount = Number(student.application_count || 0);
        const pendingCount = Number(student.pending_document_count || 0);
        let filterMatch = true;
        if (filter === "has_pending_documents") filterMatch = pendingCount > 0;
        if (filter === "has_applications") filterMatch = appCount > 0;
        if (filter === "no_applications") filterMatch = appCount === 0;
        return searchText.includes(search) && filterMatch;
    });

    const nameParts = student => {
        const explicitLast = String(student.last_name || "").trim();
        const explicitFirst = String(student.first_name || "").trim();
        if (explicitLast || explicitFirst) return { last: explicitLast, first: explicitFirst };

        const fullName = String(student.display_name || student.full_name || "").trim();
        if (fullName.includes(",")) {
            const [last, ...firstParts] = fullName.split(",");
            return { last: last.trim(), first: firstParts.join(" ").trim() };
        }
        const parts = fullName.split(/\s+/).filter(Boolean);
        return { last: parts.at(-1) || "", first: parts.slice(0, -1).join(" ") };
    };

    const bySurname = (a, b) => {
        const nameA = nameParts(a);
        const nameB = nameParts(b);
        return alpha(nameA.last, nameB.last) ||
            alpha(nameA.first, nameB.first) ||
            Number(a.student_id || 0) - Number(b.student_id || 0);
    };

    filtered.sort(bySurname);

    if (!filtered.length) {
        host.innerHTML = `<div class="sr-folder-empty">No matching student records found.</div>`;
        return;
    }

    const courses = new Map();
    filtered.forEach(student => {
        const courseCode = String(student.current_course_code || "Unassigned").trim() || "Unassigned";
        const courseName = String(student.current_course_name || "").trim();
        const section = String(student.current_section_name || "Unassigned").trim() || "Unassigned";
        if (!courses.has(courseCode)) courses.set(courseCode, { code: courseCode, name: courseName, sections: new Map(), count: 0 });
        const course = courses.get(courseCode);
        course.count++;
        if (!course.sections.has(section)) courses.get(courseCode).sections.set(section, []);
        courses.get(courseCode).sections.get(section).push(student);
    });

    const orderedCourses = [...courses.values()].sort((a, b) => alpha(a.code, b.code));
    host.innerHTML = orderedCourses.map(course => {
        const sectionHtml = [...course.sections.entries()].sort((a, b) => alpha(a[0], b[0])).map(([section, students]) => {
            students.sort(bySurname);
            const rows = students.map((student, index) => `
                <tr>
                    <td class="sr-folder-number">${index + 1}</td>
                    <td>${srEscapeHTML(student.student_number || "—")}</td>
                    <td><strong>${srEscapeHTML(student.display_name || student.full_name || "Student")}</strong></td>
                    <td>${srEscapeHTML(student.email || "—")}</td>
                    <td>${Number(student.application_count || 0)}</td>
                    <td>${Number(student.document_count || 0)}</td>
                    <td>${Number(student.pending_document_count || 0)}</td>
                    <td><button class="secondary-btn" type="button" data-sr-student-id="${Number(student.student_id)}">View Details →</button></td>
                </tr>
            `).join("");

            return `
                <details class="sr-section-folder">
                    <summary>
                        <span class="sr-folder-label"><span class="sr-folder-icon">📁</span>Section ${srEscapeHTML(section)}</span>
                        <span class="sr-folder-count">${students.length} student(s)</span>
                    </summary>
                    <div class="sr-folder-table-wrap">
                        <table class="sr-folder-table">
                            <thead><tr><th>#</th><th>STUDENT NUMBER</th><th>STUDENT NAME</th><th>EMAIL ADDRESS</th><th>APPLICATIONS</th><th>DOCUMENTS</th><th>PENDING</th><th>ACTION</th></tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </details>
            `;
        }).join("");

        return `
            <details class="sr-course-folder">
                <summary>
                    <span class="sr-folder-label"><span class="sr-folder-icon">📂</span>${srEscapeHTML(course.code)}${course.name ? ` — ${srEscapeHTML(course.name)}` : ""}</span>
                    <span class="sr-folder-count">${course.count} student(s)</span>
                </summary>
                <div class="sr-course-folder-content">${sectionHtml}</div>
            </details>
        `;
    }).join("");
}


function srShowStudentList() {
    document.getElementById("studentDetailView")
        ?.classList.add("sr-hidden");

    document.getElementById("studentListView")
        ?.classList.remove("sr-hidden");

    srCurrentStudentId = 0;

    srClearMessage();
}



async function srOpenStudentDetails(studentId) {
    srCurrentStudentId = Number(studentId);

    document.getElementById("studentListView")
        ?.classList.add("sr-hidden");

    document.getElementById("studentDetailView")
        ?.classList.remove("sr-hidden");

    document.getElementById("detailStudentName").textContent =
        "Loading student record...";

    document.getElementById("studentApplicationsTable").innerHTML = `
        <tr>
            <td colspan="9" class="empty-state">
                Loading applications...
            </td>
        </tr>
    `;

    document.getElementById("studentDocumentsTable").innerHTML = `
        <tr>
            <td colspan="6" class="empty-state">
                Loading documents...
            </td>
        </tr>
    `;

    srClearMessage();

    try {
        const data = await srRequestJSON(
            `${SR_STUDENTS_API}?student_id=${encodeURIComponent(srCurrentStudentId)}`
        );

        const student = data.student || {};

        const enrollments = Array.isArray(data.enrollments)
            ? data.enrollments
            : [];

        srCurrentDocuments = Array.isArray(data.documents)
            ? data.documents
            : [];

        const name = student.display_name || student.full_name || "Student";
        const number = student.student_number || "—";
        const email = student.email || "—";

        document.getElementById("detailStudentName").textContent = name;

        document.getElementById("detailStudentNumber").textContent =
            `Student Number: ${number}`;

        document.getElementById("profileStudentNumber").textContent = number;
        document.getElementById("profileStudentEmail").textContent = email;

        document.getElementById("profileApplicationCount").textContent =
            enrollments.length;

        document.getElementById("profileDocumentCount").textContent =
            srCurrentDocuments.length;

        const profileValues = {
            profileFullName: name,
            profileNumber: number,
            profileEmail: email,
            profileUserId: student.user_id ?? "—",
            profileBirthDate: student.birth_date || "—",
            profileGender: student.gender || "—",
            profilePhone: student.phone || "—",
            profileAddress: student.address || "—",
            profileEmergencyName: student.emergency_contact_name || "—",
            profileEmergencyNumber: student.emergency_contact_number || "—",
            profileAccountStatus: student.account_status || "—",
            profileCreatedAt: student.created_at || "—"
        };

        Object.entries(profileValues).forEach(([id, value]) => {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = value;
            }
        });

        srRenderStudentEnrollments(enrollments);
        srRenderDocumentChecklist(enrollments);
        srRenderStudentDocuments();

    } catch (error) {
        srShowMessage(error.message);

        document.getElementById("detailStudentName").textContent =
            "Unable to load student record";

        document.getElementById("studentApplicationsTable").innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">
                    ${srEscapeHTML(error.message)}
                </td>
            </tr>
        `;

        document.getElementById("studentDocumentsTable").innerHTML = `
            <tr>
                <td colspan="6" class="empty-state">
                    ${srEscapeHTML(error.message)}
                </td>
            </tr>
        `;
    }
}



function srRenderStudentEnrollments(enrollments) {
    const tbody = document.getElementById("studentApplicationsTable");

    if (!tbody) return;

    if (!enrollments.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">
                    This student has no enrollment applications yet.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = enrollments.map(item => `
        <tr>
            <td>#${Number(item.enrollment_id)}</td>

            <td>
                ${srEscapeHTML(item.course_code || item.course_name || "—")}
            </td>

            <td>${srEscapeHTML(item.section_name || "—")}</td>

            <td>${srEscapeHTML(item.year_level ?? "—")}</td>

            <td>${srEscapeHTML(item.year_name || "—")}</td>

            <td>${srEscapeHTML(item.semester_name || "—")}</td>

            <td>${srEscapeHTML(item.student_type || "—")}</td>

            <td>${srEscapeHTML(item.submitted_at || "—")}</td>

            <td>${srStatusBadge(item.status)}</td>
        </tr>
    `).join("");
}



const SR_DOCUMENT_REQUIREMENTS = [
    { name: "Birth Certificate", type: "required" },
    { name: "Form 138 / Report Card", type: "required" },
    { name: "Good Moral Certificate", type: "required" },
    { name: "Transcript of Records", type: "conditional" },
    { name: "Certificate of Transfer", type: "conditional" },
    { name: "Other Supporting Document", type: "optional" }
];



function srRenderDocumentChecklist(enrollments) {
    const host = document.getElementById("studentRequirementChecklist");

    if (!host) return;

    if (!enrollments || enrollments.length === 0) {
        host.innerHTML = `
            <div class="empty-state">
                No enrollment applications yet.
            </div>
        `;
        return;
    }

    host.innerHTML = enrollments.map(enrollment => {
        const items = SR_DOCUMENT_REQUIREMENTS.map(req => {
            const matchingDocs = srCurrentDocuments.filter(doc =>
                Number(doc.enrollment_id) === Number(enrollment.enrollment_id) &&
                String(doc.document_name || "").trim().toLowerCase() ===
                req.name.trim().toLowerCase()
            );

            const doc = matchingDocs[matchingDocs.length - 1];

            let label = req.type === "required" ? "Missing" :
                req.type === "conditional" ? "Conditional" : "Optional";

            let css = req.type === "required" ? "rejected" : "default";

            if (doc) {
                const status = srNormalizeStatus(doc.verification_status);

                if (status === "verified" || status === "approved") {
                    label = "Verified";
                    css = "verified";

                } else if (status === "rejected") {
                    label = "Resubmit Required";
                    css = "rejected";

                } else {
                    label = "Pending Verification";
                    css = "pending";
                }
            }

            return {
                ...req,
                label,
                css
            };
        });

        const required = items.filter(item => item.type === "required");

        const verified = required.filter(
            item => item.label === "Verified"
        ).length;

        const complete = verified === required.length;

        return `
            <div class="sr-requirement-card">

                <div class="sr-requirement-heading">
                    <strong>
                        Application #${Number(enrollment.enrollment_id)}
                    </strong>

                    <span class="status ${complete ? "verified" : "rejected"}">
                        ${complete ? "Complete" : "Incomplete"}
                    </span>
                </div>

                <div class="sr-requirement-list">

                    ${items.map(item => `
                        <div class="sr-requirement-row">
                            <span>${srEscapeHTML(item.name)}</span>

                            <span class="status ${item.css}">
                                ${srEscapeHTML(item.label)}
                            </span>
                        </div>
                    `).join("")}

                </div>

                <small>
                    ${verified} of ${required.length} required documents verified
                </small>

            </div>
        `;
    }).join("");
}



function srRenderStudentDocuments() {
    const tbody = document.getElementById("studentDocumentsTable");
    const statsHost = document.getElementById("studentDocumentStats");
    const docs = srCurrentDocuments;

    if (!tbody) return;

    const pending = docs.filter(item =>
        srNormalizeStatus(item.verification_status) === "pending"
    ).length;

    const verified = docs.filter(item =>
        srNormalizeStatus(item.verification_status) === "verified"
    ).length;

    const rejected = docs.filter(item =>
        srNormalizeStatus(item.verification_status) === "rejected"
    ).length;

    if (statsHost) {
        statsHost.innerHTML = `
            <span class="sr-document-stat">
                Total <strong>${docs.length}</strong>
            </span>

            <span class="sr-document-stat">
                Pending <strong>${pending}</strong>
            </span>

            <span class="sr-document-stat">
                Verified <strong>${verified}</strong>
            </span>

            <span class="sr-document-stat">
                Rejected <strong>${rejected}</strong>
            </span>
        `;
    }

    if (!docs.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="empty-state">
                    This student has not uploaded any documents.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = docs.map(item => {
        const id = Number(item.document_id);
        const status = srNormalizeStatus(item.verification_status);

        let actions = `
            <button
                class="secondary-btn"
                type="button"
                data-sr-document-action="preview"
                data-id="${id}"
            >
                Preview
            </button>
        `;

        if (status === "pending") {
            actions += `
                <button
                    class="success-btn"
                    type="button"
                    data-sr-document-action="verify"
                    data-id="${id}"
                >
                    Verify
                </button>

                <button
                    class="danger-btn"
                    type="button"
                    data-sr-document-action="reject"
                    data-id="${id}"
                >
                    Reject
                </button>
            `;
        }

        return `
            <tr>
                <td>#${id}</td>

                <td>
                    ${srEscapeHTML(item.document_name || "—")}
                </td>

                <td>
                    #${Number(item.enrollment_id)}
                </td>

                <td>
                    ${srEscapeHTML(item.uploaded_at || "—")}
                </td>

                <td>
                    ${srStatusBadge(status)}
                </td>

                <td>
                    <div class="sr-action-group">
                        ${actions}
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}


function srPreviewDocument(documentId) {
    const docItem = srCurrentDocuments.find(item =>
        Number(item.document_id) === Number(documentId)
    );

    if (!docItem) {
        srToast("Document record not found.", "error");
        return;
    }

    const title = document.getElementById("previewTitle");
    const content = document.getElementById("previewContent");
    const modal = document.getElementById("previewModal");

    if (!title || !content || !modal) {
        srToast(
            "The existing document preview window is unavailable.",
            "error"
        );
        return;
    }

    title.textContent = docItem.document_name || "Document Preview";

    const url =
        `${SR_PREVIEW_API}?document_id=${encodeURIComponent(documentId)}`;

    content.innerHTML = `
        <iframe
            class="preview-frame"
            src="${srEscapeHTML(url)}"
            title="Enrollment Document Preview"
        ></iframe>
    `;

    modal.classList.add("show");
}



async function srUpdateDocumentStatus(documentId, status) {
    const actionText = status === "verified" ? "verify" : "reject";

    if (!window.confirm(
        `Are you sure you want to ${actionText} this document?`
    )) {
        return;
    }

    try {
        const data = await srRequestJSON(SR_DOCUMENT_API, {
            method: "POST",

            headers: {
                "Content-Type": "application/json"
            },

            body: JSON.stringify({
                document_id: Number(documentId),
                verification_status: status
            })
        });

        srToast(
            data.message || "Document status updated successfully."
        );

        await srOpenStudentDetails(srCurrentStudentId);
        await loadStudentRecords(true);

    } catch (error) {
        srToast(error.message, "error");
    }
}
function srInitializeStudentRecords() {

    const navButton = document.querySelector(
        '.nav-btn[data-view="students"]'
    );

    if (navButton) {
        navButton.addEventListener("click", () => {
            loadStudentRecords();
        });
    }



    document.getElementById("studentSectionFolders")?.addEventListener(
        "click",
        event => {
            const button = event.target.closest(
                "button[data-sr-student-id]"
            );

            if (button) {
                srOpenStudentDetails(
                    Number(button.dataset.srStudentId)
                );
            }
        }
    );


    document.getElementById("studentDocumentsTable")?.addEventListener(
        "click",
        event => {
            const button = event.target.closest(
                "button[data-sr-document-action]"
            );

            if (!button) return;

            const id = Number(button.dataset.id);
            const action = button.dataset.srDocumentAction;

            if (action === "preview") {
                srPreviewDocument(id);
            }

            if (action === "verify") {
                srUpdateDocumentStatus(id, "verified");
            }

            if (action === "reject") {
                srUpdateDocumentStatus(id, "rejected");
            }
        }
    );



    document.getElementById("studentSearch")?.addEventListener(
        "input",
        srRenderStudents
    );


    document.getElementById("studentFilter")?.addEventListener(
        "change",
        srRenderStudents
    );



    document.getElementById("refreshStudentsBtn")?.addEventListener(
        "click",
        () => loadStudentRecords(true)
    );



    document.getElementById("backToStudentsBtn")?.addEventListener(
        "click",
        srShowStudentList
    );



    document.getElementById("refreshStudentDetailBtn")?.addEventListener(
        "click",
        () => {
            if (srCurrentStudentId) {
                srOpenStudentDetails(srCurrentStudentId);
            }
        }
    );
}


document.addEventListener(
    "DOMContentLoaded",
    srInitializeStudentRecords
);
