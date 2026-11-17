<div id="students">

    <div id="srPageMessage" class="sr-page-message" role="status"></div>

    <!-- ==========================================
         STUDENT LIST VIEW
    =========================================== -->

    <div id="studentListView">

        <div class="page-header">
            <div>
                <h1>Student Records</h1>
                <p>
                    Select a student to view their profile,
                    enrollment applications, and uploaded documents.
                </p>
            </div>

            <button
                class="refresh-btn"
                id="refreshStudentsBtn"
                type="button"
            >
                ↻ Refresh
            </button>
        </div>

        <!-- STUDENT SUMMARY -->

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

        <!-- REGISTERED STUDENTS -->

        <div class="panel">

            <div class="panel-heading">
                <div>
                    <h2>Registered Students</h2>
                    <p>
                        Each student appears once.
                        Select a student to open their complete record.
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

    </div>


    <!-- ==========================================
         INDIVIDUAL STUDENT DETAILS
    =========================================== -->

    <div id="studentDetailView" class="sr-hidden">

        <!-- STUDENT DETAIL HEADER -->

        <div class="page-header">

            <div>

                <button
                    id="backToStudentsBtn"
                    class="secondary-btn sr-back-btn"
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
                class="refresh-btn"
                type="button"
            >
                ↻ Refresh Record
            </button>

        </div>


        <!-- STUDENT SUMMARY -->

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


        <!-- ======================================
             STUDENT INFORMATION
        ======================================= -->

        <div class="panel">

            <div class="panel-heading">
                <div>
                    <h2>Student Information</h2>
                    <p>
                        Basic information connected to this student account.
                    </p>
                </div>
            </div>

            <div class="sr-information-grid">

                <div class="sr-information-item">
                    <span>Full Name</span>
                    <strong id="profileFullName">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Student Number</span>
                    <strong id="profileNumber">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Email</span>
                    <strong id="profileEmail">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Student Account ID</span>
                    <strong id="profileUserId">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Birth Date</span>
                    <strong id="profileBirthDate">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Gender</span>
                    <strong id="profileGender">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Phone Number</span>
                    <strong id="profilePhone">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Address</span>
                    <strong id="profileAddress">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Emergency Contact Name</span>
                    <strong id="profileEmergencyName">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Emergency Contact Number</span>
                    <strong id="profileEmergencyNumber">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Account Status</span>
                    <strong id="profileAccountStatus">—</strong>
                </div>

                <div class="sr-information-item">
                    <span>Account Created</span>
                    <strong id="profileCreatedAt">—</strong>
                </div>

            </div>

        </div>


        <!-- ======================================
             ENROLLMENT APPLICATIONS
        ======================================= -->

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
                        <tr>
                            <td colspan="9" class="empty-state">
                                Loading applications...
                            </td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </div>


        <!-- ======================================
             DOCUMENT REQUIREMENTS CHECKLIST
        ======================================= -->

        <div class="panel">

            <div class="panel-heading">

                <div>
                    <h2>Document Requirements Checklist</h2>

                    <p>
                        Review the required documents for each
                        enrollment application separately.
                    </p>
                </div>

            </div>

            <div class="document-summary">
                <strong>Document Verification Guide</strong>

                <p>
                    Complete means all three required documents
                    have been verified by the registrar.
                </p>
            </div>

            <!-- JavaScript renders individual requirements here -->

            <div
                id="studentRequirementChecklist"
                class="sr-requirement-checklist"
            >

                <div class="empty-state">
                    Loading document requirements...
                </div>

            </div>

        </div>


        <!-- ======================================
             UPLOADED DOCUMENTS
        ======================================= -->

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

            <!-- DOCUMENT STATISTICS -->

            <div
                class="sr-document-stats"
                id="studentDocumentStats"
            ></div>


            <!-- DOCUMENT TABLE -->

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
                        <tr>
                            <td colspan="6" class="empty-state">
                                Loading documents...
                            </td>
                        </tr>
                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>