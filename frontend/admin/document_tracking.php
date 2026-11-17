<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    header('Location: ../../index.php'); exit();
}
require_once __DIR__ . '/../includes/profile_bootstrap.php';
$activeAdminPage = 'document_tracking';
$adminName = htmlspecialchars($sharedProfileName, ENT_QUOTES, 'UTF-8');
$adminInitial = htmlspecialchars($sharedProfileInitials, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Document Tracking | Skwela College Enrollment System</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin-dashboard.css?v=20261117-final">
<link rel="stylesheet" href="../assets/css/profile-modal.css?v=20261117-final">
<link rel="stylesheet" href="../assets/css/sidebar-unified.css?v=20261117-final">
<style>
:root{--navy:#12366b;--blue:#1769aa;--ink:#172b4d;--muted:#718096;--line:#e5edf5;--bg:#f4f7fb}
*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Inter,Arial,sans-serif;color:var(--ink)}
.topbar{height:70px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 30px}
.brand{display:flex;align-items:center;gap:11px;color:var(--navy);text-decoration:none;font-weight:800}.brand-mark{width:38px;height:38px;border-radius:11px;background:var(--navy);color:#fff;display:grid;place-items:center;font-size:20px}
.top-actions{display:flex;gap:16px;color:#52647d;font-size:13px}.back{color:var(--blue);text-decoration:none;font-weight:700}
.document-page{max-width:1500px;margin:30px auto;padding:0 28px}.heading{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:22px}
.eyebrow{font-size:11px;font-weight:800;letter-spacing:1.3px;color:var(--blue);margin:0 0 8px}.heading h1{font-size:28px;margin:0 0 7px}.subtitle{margin:0;color:var(--muted);font-size:13px}
.btn{border:0;border-radius:9px;padding:11px 15px;background:var(--blue);color:#fff;font-weight:700;cursor:pointer}.btn:disabled{opacity:.6}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px}.stat,.panel{background:#fff;border:1px solid var(--line);border-radius:15px;box-shadow:0 5px 18px #14325a0a}.stat{padding:18px 20px}.stat span{display:block;color:var(--muted);font-size:12px;font-weight:600}.stat strong{display:block;font-size:27px;margin-top:8px;color:var(--navy)}
.panel{padding:22px}.panel-head{margin-bottom:18px}.panel-head h2{margin:0;font-size:17px}.controls{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px}.controls input,.controls select{height:42px;border:1px solid #dce6f0;border-radius:9px;padding:0 12px;font:inherit;font-size:13px;background:#fff}.controls input{min-width:260px;flex:1}
.meta{display:flex;justify-content:space-between;color:var(--muted);font-size:12px;margin-bottom:12px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:1080px}th{text-align:left;background:#f6f9fc;color:#63748b;font-size:10px;letter-spacing:.7px;text-transform:uppercase;padding:13px 12px}td{padding:13px 12px;border-bottom:1px solid #edf1f6;font-size:12px;vertical-align:top}
.student{font-weight:700;color:#213b60}.sub{display:block;color:#8996a8;font-size:11px;margin-top:4px}.pill{display:inline-block;border-radius:20px;padding:6px 10px;font-size:10px;font-weight:800;white-space:nowrap}
.pending{background:#fff4d8;color:#946200}.verified,.complete{background:#e4f7ed;color:#147544}.rejected,.incomplete,.missing{background:#fde8e8;color:#b42318}.conditional,.optional{background:#edf2f7;color:#596579}
.checklist{display:grid;gap:6px;min-width:245px}.check-row{display:flex;justify-content:space-between;align-items:center;gap:8px}.check-name{font-size:11px;color:#52647d}
.empty{text-align:center;padding:35px;color:#8492a6}.notice{display:none;padding:12px 14px;border-radius:9px;background:#fde8e8;color:#a32121;font-size:12px;margin-bottom:14px}
@media(max-width:850px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.topbar{padding:0 15px}.top-actions span{display:none}main{padding:0 13px;margin:20px auto}.heading{align-items:flex-start;flex-direction:column}.stats{grid-template-columns:1fr 1fr}.panel{padding:14px}}
.main-area{margin-left:255px;min-width:0}.topbar{height:72px;padding:0 32px}.page-content{max-width:1600px;margin:0 auto;padding:30px 34px 28px}.document-page{max-width:1500px;margin:0 auto;padding:0}.stats{grid-template-columns:repeat(4,minmax(0,1fr))}
@media(max-width:1100px){.main-area{margin-left:220px}.page-content{padding-left:24px;padding-right:24px}}
@media(max-width:700px){.main-area{margin-left:0}.page-content{padding:22px 14px}.stats{grid-template-columns:1fr}.heading{align-items:flex-start;flex-direction:column}}

.document-folder-list{display:grid;gap:10px;margin-top:12px}
.document-folder{border:1px solid #dfe8f3;border-radius:12px;background:#fff;overflow:hidden}
.document-folder>summary{display:flex;align-items:center;gap:11px;min-height:54px;padding:12px 15px;cursor:pointer;list-style:none;color:#1b365e}
.document-folder>summary::-webkit-details-marker{display:none}
.document-folder .folder-number{display:grid;place-items:center;width:25px;height:25px;border-radius:8px;background:#edf4ff;color:#2467b7;font-size:10px;font-weight:800}
.document-folder>summary strong{font-size:12px;color:#203b60}
.document-folder>summary small{margin-left:auto;color:#7b8da4;font-size:10px}
.document-folder .folder-chevron{color:#2467b7;font-size:17px;transition:transform .18s ease}
.document-folder[open]>summary{background:#f8fbff;border-bottom:1px solid #e8eef6}
.document-folder[open] .folder-chevron{transform:rotate(180deg)}
.document-folder-content{padding:12px}
.document-folder-nested{margin:8px 0 0 18px;border-color:#e7edf5}
.document-folder-nested>summary{min-height:48px;padding:9px 12px}
.document-folder-table-wrap{overflow-x:auto;border:1px solid #e8eef6;border-radius:9px}
.document-folder-table{width:100%;min-width:1020px;border-collapse:collapse}
.document-folder-table th{padding:12px;background:#f6f9fc;color:#63748b;font-size:10px;letter-spacing:.5px;text-align:left;text-transform:uppercase;white-space:nowrap}
.document-folder-table td{padding:13px 12px;border-top:1px solid #edf1f6;font-size:12px;vertical-align:top}
.document-folder-table tbody tr:hover{background:#f8fbff}
.document-folder-empty{padding:22px;text-align:center;color:#8492a6;font-size:12px}
@media(max-width:700px){.document-folder>summary{padding:10px;gap:8px}.document-folder-nested{margin-left:7px}.document-folder-content{padding:8px}}
</style>
</head>
<body>
<div class="app-shell">
<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
<main class="main-area">
<header class="topbar">
<button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle navigation">☰</button>
<div class="breadcrumb"><span>Workspace</span><b>/</b><strong>Document Tracking</strong></div>
<button type="button" class="shared-profile-trigger" onclick="openSharedProfile()" aria-label="Open administrator profile">
<?php if ($sharedProfileImageUrl !== ''): ?><img class="shared-profile-avatar" src="<?= htmlspecialchars($sharedProfileImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Administrator profile picture"><?php else: ?><span class="shared-profile-avatar"><?= $adminInitial ?></span><?php endif; ?>
<span class="shared-profile-trigger-name"><?= $adminName ?></span>
</button>
</header>
<div class="page-content">
<div class="document-page">
<section class="heading"><div><p class="eyebrow">STUDENT RECORDS</p><h1>Document Tracking</h1><p class="subtitle">Compare each enrollment application with the existing student document checklist.</p></div><button class="btn" id="refreshBtn">↻ Refresh Records</button></section>
<div id="notice" class="notice"></div>
<section class="stats">
<div class="stat"><span>Enrollment Applications</span><strong id="totalCount">—</strong></div>
<div class="stat"><span>Complete Applications</span><strong id="completeCount">—</strong></div>
<div class="stat"><span>Incomplete Applications</span><strong id="incompleteCount">—</strong></div>
<div class="stat"><span>Required Documents Missing / Rejected</span><strong id="attentionCount">—</strong></div>
</section>
<section class="panel">
<div class="panel-head"><h2>Enrollment Document Checklist</h2><p class="subtitle">Each checklist is calculated separately using its enrollment ID.</p></div>
<div class="controls"><input type="search" id="searchInput" placeholder="Search student, student number, course, enrollment ID..."><select id="statusFilter"><option value="all">All completeness statuses</option><option value="complete">Complete</option><option value="incomplete">Incomplete</option></select></div>
<div class="meta"><span id="recordCount">Loading enrollment applications...</span><span>Complete = all 3 required documents verified</span></div>
<div class="document-folder-list" id="enrollmentRows" aria-live="polite"><div class="empty">Loading enrollment and document records...</div></div>
</section>
</div>
</div>
</main>
</div>
<?php require __DIR__ . '/../includes/profile_modal.php'; ?>
<script src="../assets/js/profile-modal.js" defer></script>
<script>
const ENROLLMENT_API='../../backend/routes/enrollments.php';
const DOCUMENT_API='../../backend/routes/documents.php';
const REQUIREMENTS=[
{name:'Birth Certificate',type:'required'},
{name:'Form 138 / Report Card',type:'required'},
{name:'Good Moral Certificate',type:'required'},
{name:'Transcript of Records',type:'conditional'},
{name:'Certificate of Transfer',type:'conditional'},
{name:'Other Supporting Document',type:'optional'}
];
let enrollments=[],documents=[];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const normName=v=>String(v||'').trim().toLowerCase().replace(/\s+/g,' ');
const menuToggle=document.getElementById('menuToggle');if(menuToggle)menuToggle.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('open'));
function surnameParts(v){const a=String(v||'').trim().replace(/\s+/g,' ').split(' ').filter(Boolean);return a.length>1?[a[a.length-1].toLowerCase(),a.slice(0,-1).join(' ').toLowerCase()]:[(a[0]||'zzzz').toLowerCase(),''];}
function docStatus(v){v=String(v||'pending').toLowerCase().replace(/[\s-]+/g,'_');if(['verified','approved'].includes(v))return'verified';if(['rejected','revision_required','resubmit_required'].includes(v))return'rejected';return'pending'}
function stateFor(enrollmentId,req){
 const match=documents.filter(d=>Number(d.enrollment_id)===Number(enrollmentId)&&normName(d.document_name)===normName(req.name)).sort((a,b)=>String(b.uploaded_at||'').localeCompare(String(a.uploaded_at||'')))[0];
 if(!match)return req.type==='required'?{label:'Missing',css:'missing',attention:true,verified:false}:{label:req.type==='conditional'?'Conditional':'Optional',css:req.type,attention:false,verified:false};
 const status=docStatus(match.verification_status);
 if(status==='verified')return{label:'Verified',css:'verified',attention:false,verified:true};
 if(status==='rejected')return{label:'Resubmit Required',css:'rejected',attention:req.type==='required',verified:false};
 return{label:'Pending Verification',css:'pending',attention:false,verified:false};
}
function summaryFor(e){const list=REQUIREMENTS.map(r=>({...r,state:stateFor(e.enrollment_id,r)}));const req=list.filter(r=>r.type==='required');return{list,verified:req.filter(r=>r.state.verified).length,attention:req.filter(r=>r.state.attention).length,complete:req.every(r=>r.state.verified)}}
function nameOf(e){return e.full_name||[e.first_name,e.middle_name,e.last_name].filter(Boolean).join(' ')||'Unnamed Student'}
function displayStudentName(e){
 const first=[e.first_name,e.middle_name].filter(Boolean).join(' ').trim();
 if(e.last_name)return `${e.last_name}, ${first}`.replace(/,\s*$/,'');
 const parts=String(nameOf(e)).trim().replace(/\s+/g,' ').split(' ').filter(Boolean);
 return parts.length>1?`${parts.pop()}, ${parts.join(' ')}`:(parts[0]||'Unnamed Student');
}
function render(){
 const q=document.getElementById('searchInput').value.trim().toLowerCase(),f=document.getElementById('statusFilter').value;
 const rows=enrollments.map(e=>({e,s:summaryFor(e)})).filter(({e,s})=>{
  const hay=[nameOf(e),displayStudentName(e),e.student_number,e.email,e.enrollment_id,e.course_code,e.course_name,e.section_name,e.year_name].join(' ').toLowerCase();
  return(!q||hay.includes(q))&&(f==='all'||(f==='complete'?s.complete:!s.complete));
 }).sort((a,b)=>{const pa=surnameParts(nameOf(a.e)),pb=surnameParts(nameOf(b.e));return pa[0].localeCompare(pb[0],'en',{sensitivity:'base'})||pa[1].localeCompare(pb[1],'en',{sensitivity:'base'});});
 document.getElementById('recordCount').textContent=`${rows.length} of ${enrollments.length} enrollment application(s)`;
 const host=document.getElementById('enrollmentRows');
 if(!rows.length){host.innerHTML='<div class="document-folder-empty">No matching enrollment applications found.</div>';return}
 const courses=new Map();
 rows.forEach(item=>{
  const code=String(item.e.course_code||'Other Courses');
  if(!courses.has(code))courses.set(code,{code,name:item.e.course_name||'',sections:new Map()});
  const course=courses.get(code);
  const sectionKey=`${item.e.section_name||'Unassigned'}|${item.e.year_name||''}|${item.e.semester_name||''}`;
  if(!course.sections.has(sectionKey))course.sections.set(sectionKey,{name:item.e.section_name||'Unassigned Section',year:item.e.year_name||'',semester:item.e.semester_name||'',items:[]});
  course.sections.get(sectionKey).items.push(item);
 });
 const courseList=[...courses.values()].sort((a,b)=>a.code.localeCompare(b.code,'en',{numeric:true,sensitivity:'base'}));
 host.innerHTML=courseList.map((course,courseIndex)=>{
  const sectionList=[...course.sections.values()].sort((a,b)=>String(a.name).localeCompare(String(b.name),'en',{numeric:true,sensitivity:'base'})||String(a.year).localeCompare(String(b.year)));
  const sectionMarkup=sectionList.map((section,sectionIndex)=>{
   const body=section.items.map(({e,s},index)=>{
    const required=s.list.filter(x=>x.type==='required').map(x=>`<div class="check-row"><span class="check-name">${esc(x.name)}</span><span class="pill ${x.state.css}">${esc(x.state.label)}</span></div>`).join('');
    const other=s.list.filter(x=>x.type!=='required').map(x=>`<div class="check-row"><span class="check-name">${esc(x.name)}</span><span class="pill ${x.state.css}">${esc(x.state.label)}</span></div>`).join('');
    return `<tr><td>${index+1}</td><td><span class="student">${esc(displayStudentName(e))}</span><span class="sub">Student No.: ${esc(e.student_number||'Not assigned')}</span><span class="sub">${esc(e.email||'')}</span></td>
     <td><strong>#${esc(e.enrollment_id)}</strong><span class="sub">${esc(e.course_code||'')} ${esc(e.course_name||'')}</span><span class="sub">Section: ${esc(e.section_name||'—')} · ${esc(e.year_name||'')}</span></td>
     <td><div class="checklist">${required}</div><span class="sub">${s.verified} of 3 required documents verified</span></td>
     <td><div class="checklist">${other}</div></td>
     <td><span class="pill ${s.complete?'complete':'incomplete'}">${s.complete?'Complete':'Incomplete'}</span><span class="sub">${s.complete?'All required documents verified':`${s.attention} required document(s) missing or rejected`}</span></td></tr>`;
   }).join('');
   const table=`<div class="document-folder-table-wrap"><table class="document-folder-table"><thead><tr><th>#</th><th>Student (Last, First)</th><th>Enrollment / Course</th><th>Required Documents</th><th>Other Requirements</th><th>Overall Status</th></tr></thead><tbody>${body}</tbody></table></div>`;
   return `<details class="document-folder document-folder-nested"><summary><span class="folder-number">${sectionIndex+1}</span><strong>Section ${esc(section.name)}</strong><small>${section.items.length} application(s)</small><span class="folder-chevron">⌄</span></summary><div class="document-folder-content">${table}</div></details>`;
  }).join('');
  return `<details class="document-folder"><summary><span class="folder-number">${courseIndex+1}</span><strong>${esc(course.code)} — ${esc(course.name)}</strong><small>${sectionList.length} section(s) · ${course.sections.size?sectionList.reduce((n,x)=>n+x.items.length,0):0} application(s)</small><span class="folder-chevron">⌄</span></summary><div class="document-folder-content">${sectionMarkup}</div></details>`;
 }).join('');
}
function stats(){
 const sums=enrollments.map(summaryFor);
 document.getElementById('totalCount').textContent=enrollments.length;
 document.getElementById('completeCount').textContent=sums.filter(s=>s.complete).length;
 document.getElementById('incompleteCount').textContent=sums.filter(s=>!s.complete).length;
 document.getElementById('attentionCount').textContent=sums.reduce((n,s)=>n+s.attention,0);
}
async function getJSON(url){
 const res=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json'}});
 const data=await res.json();
 if(!res.ok||data.success!==true)throw new Error(data.message||`Request failed (${res.status})`);
 return data;
}
async function load(){
 const btn=document.getElementById('refreshBtn');btn.disabled=true;btn.textContent='Loading...';
 document.getElementById('enrollmentRows').innerHTML='<div class="document-folder-empty">Loading enrollment and document records...</div>';
 try{
  const [ed,dd]=await Promise.all([getJSON(ENROLLMENT_API),getJSON(DOCUMENT_API)]);
  enrollments=Array.isArray(ed.enrollments)?ed.enrollments:[];
  documents=Array.isArray(dd.documents)?dd.documents:[];
  stats();render();document.getElementById('notice').style.display='none';
 }catch(err){
  document.getElementById('enrollmentRows').innerHTML=`<div class="document-folder-empty">${esc(err.message)}</div>`;
  document.getElementById('recordCount').textContent='Unable to load records';
  const n=document.getElementById('notice');n.textContent='Could not load tracking data. Check Admin GET permission in enrollments.php and both API responses.';n.style.display='block';
 }finally{btn.disabled=false;btn.textContent='↻ Refresh Records'}
}
document.getElementById('refreshBtn').addEventListener('click',load);
document.getElementById('searchInput').addEventListener('input',render);
document.getElementById('statusFilter').addEventListener('change',render);
load();
</script>
</body></html>
