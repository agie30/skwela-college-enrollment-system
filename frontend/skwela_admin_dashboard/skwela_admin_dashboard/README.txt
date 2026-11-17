SKWELA ADMIN DASHBOARD - INSTALLATION

1. Extract this folder.
2. Copy the included frontend folder into your existing project root:
   C:\xampp\htdocs\college-enrollment-system\
   Allow the files to merge with your existing frontend folder.
3. Confirm these paths exist:
   frontend/admin/dashboard.php
   frontend/assets/css/admin-dashboard.css
   frontend/assets/js/admin-dashboard.js
4. Start Apache and MySQL in XAMPP.
5. Log in using an Admin account, then open:
   http://localhost:8080/college-enrollment-system/frontend/admin/dashboard.php

NOTES
- The page requires an authenticated Admin session.
- It reads Courses, Subjects, Sections, Academic Years, and Activity Logs from the existing backend routes.
- Search, status filtering, refresh, responsive sidebar, and CSV export are included.
- The table builds columns from the fields returned by each API. If a route returns an error or a response format the page does not recognize, the page displays that error rather than inserting sample data.
- This is the first Admin dashboard/data-view stage. Add/Edit/Delete/Status action forms and the Registrar/Student workspaces are separate next stages.
