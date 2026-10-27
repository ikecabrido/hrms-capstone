<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>L&amp;D System Flowchart</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#f0f2f5;font-family:'Segoe UI',system-ui,sans-serif;color:#1a1a2e;padding:20px;font-size:13px}
h1{text-align:center;font-size:18px;color:#320082;margin-bottom:2px}
.sub{text-align:center;font-size:11px;color:#888;margin-bottom:20px}
.wrap{max-width:900px;margin:0 auto}

.oval{background:#eee;border:2px solid #555;border-radius:40px;padding:8px 24px;font-weight:700;font-size:12px;display:inline-block;text-align:center}
.diamond{display:inline-block;background:#fef9c3;border:2px solid #ca8a04;clip-path:polygon(50% 0%,100% 50%,50% 100%,0% 50%);padding:18px 30px;margin:4px 0;min-width:90px}
.diamond span{font-weight:700;font-size:10px;text-align:center;line-height:1.2}
.rect{background:#fff;border:2px solid;border-radius:8px;padding:6px 14px;font-weight:600;font-size:11px;display:inline-block;text-align:center}
.rect.rp{border-color:#320082}.rect.ra{border-color:#4338ca;background:#eef2ff;color:#4338ca}.rect.ri{border-color:#16a34a;background:#f0fdf4;color:#16a34a}.rect.rl{border-color:#ea580c;background:#fff7ed;color:#ea580c}.rect.rx{border-color:#ef4444;color:#ef4444}
.io{border:2px solid #9333ea;border-radius:6px;padding:5px 12px;font-weight:600;font-size:10px;display:inline-block;text-align:center;background:#f3e8ff;color:#7c3aed}
.io.ia{border-color:#4338ca;background:#eef2ff;color:#4338ca}.io.ii{border-color:#16a34a;background:#f0fdf4;color:#16a34a}.io.il{border-color:#ea580c;background:#fff7ed;color:#ea580c}
.pill{display:inline-block;padding:3px 8px;border-radius:999px;font-size:9px;font-weight:700;margin:2px;border:1px solid}
.pill.pa{background:#eef2ff;color:#4338ca;border-color:#4338ca}
.pill.pi{background:#f0fdf4;color:#16a34a;border-color:#16a34a}
.pill.pl{background:#fff7ed;color:#ea580c;border-color:#ea580c}
.pill.pr{background:#fef2f2;color:#dc2626;border-color:#dc2626}
.pill.pg{background:#f0fdf4;color:#16a34a;border-color:#16a34a}
.pill.ps{background:#eef2ff;color:#6366f1;border-color:#6366f1}
.pill.pp{background:#f3e8ff;color:#7c3aed;border-color:#7c3aed}

.arrow{display:flex;flex-direction:column;align-items:center;padding:2px 0}
.arrow::before{content:'';width:2px;height:12px;background:#555}
.arrow::after{content:'▼';font-size:7px;color:#555;margin-top:-3px}
.arrow.aa::before{background:#4338ca}.arrow.aa::after{color:#4338ca}
.arrow.ai::before{background:#16a34a}.arrow.ai::after{color:#16a34a}
.arrow.al::before{background:#ea580c}.arrow.al::after{color:#ea580c}
.lbl{font-size:9px;color:#888;font-weight:600;text-align:center;padding:1px 0}
.lbl.lg{color:#16a34a;font-weight:700}
.lbl.lr{color:#ef4444;font-weight:700}
.row{display:flex;gap:4px;flex-wrap:wrap;justify-content:center;margin:2px 0}
.c{text-align:center}

/* section boxes */
.sec{border:2px dashed;border-radius:12px;padding:12px;margin:12px 0}
.sec-a{border-color:#4338ca;background:rgba(67,56,202,0.02)}
.sec-i{border-color:#16a34a;background:rgba(22,163,74,0.02)}
.sec-l{border-color:#ea580c;background:rgba(234,88,12,0.02)}
.sec-t{border-color:#6366f1;background:rgba(99,102,241,0.02)}
.sec-d{border-color:#320082;background:rgba(32,0,130,0.02)}
.sec h3{font-size:13px;font-weight:800;margin-bottom:8px;text-align:center}
.sec-a h3{color:#4338ca}.sec-i h3{color:#16a34a}.sec-l h3{color:#ea580c}.sec-t h3{color:#6366f1}.sec-d h3{color:#320082}

.db-grid{display:flex;gap:8px;flex-wrap:wrap;justify-content:center}
.db-card{flex:1;min-width:130px;max-width:180px;border-radius:8px;padding:8px;border:1.5px solid;font-size:10px}
.db-card h4{font-size:9px;font-weight:700;margin-bottom:3px;text-transform:uppercase}
.db-card li{font-size:9px;color:#555;padding:1px 0;font-family:monospace;list-style:none}
.db-el{background:#eef2ff;border-color:#4338ca}.db-el h4{color:#4338ca}
.db-ep{background:rgba(32,0,130,0.04);border-color:#320082}.db-ep h4{color:#320082}
.db-tr{background:#f0fdf4;border-color:#16a34a}.db-tr h4{color:#16a34a}
.db-sk{background:#fff7ed;border-color:#d97706}.db-sk h4{color:#d97706}
.db-us{background:#f8f8f8;border-color:#555}.db-us h4{color:#555}
.db-ct{background:#f3e8ff;border-color:#9333ea}.db-ct h4{color:#9333ea}
.desc{font-size:9px;color:#999;margin:0}
</style>
</head>
<body>
<div class="wrap">

<h1>🎓 Learning &amp; Development — System Flowchart</h1>
<p class="sub">Complete workflow for Admin, Instructor, and Learner roles</p>

<!-- LOGIN -->
<div class="c">
    <div class="oval">LOGIN</div>
    <div class="arrow"></div>
    <div class="diamond"><span>VALIDATED<br>USER</span></div>
</div>
<div style="display:flex;align-items:center;justify-content:center;gap:6px;margin:4px 0">
    <span class="lbl lr">IF FAILED →</span>
    <div class="rect rx">LOGIN FAILED</div>
    <span class="lbl">← Retry</span>
</div>
<div class="c">
    <div class="lbl lg">IF SUCCESS ↓</div>
    <div class="arrow"></div>
    <div class="diamond"><span>CHECK<br>ROLE</span></div>
    <div class="arrow"></div>
</div>

<div class="row">
    <span class="pill pa">🛡️ IF ADMIN</span>
    <span class="pill pi">👨‍🏫 IF INSTRUCTOR</span>
    <span class="pill pl">📚 IF LEARNER</span>
</div>

<!-- ═══ ADMIN ═══ -->
<div class="sec sec-a">
    <h3>🛡️ Admin Panel</h3>
    <div class="c"><div class="rect ra">ACCESS ADMIN PANEL</div></div>
    <div class="arrow aa"></div>
    <div class="c"><div class="io ia">INSERT USER (Learner / Instructor)</div></div>
    <div class="arrow aa"></div>
    <div class="c"><div class="rect ra">USER MANAGEMENT</div><p class="desc">Monitor, Edit, Archive Users</p></div>
    <div class="arrow aa"></div>
    <div class="c"><div class="rect ra">USER MODERATION</div><p class="desc">Review Reported Items</p></div>
    <div class="arrow aa"></div>
    <div class="row">
        <span class="pill pr">⚠ Violation → Archive</span>
        <span class="pill pg">✓ Not Violation → No Action</span>
    </div>
    <div class="arrow aa"></div>
    <div class="c"><div class="rect ra">SYSTEM ANALYTICS</div><p class="desc">Reports, Charts, Insights</p></div>
    <div class="arrow aa"></div>
    <div class="row">
        <span class="pill pa">Settings</span><span class="pill pa">Notifications</span><span class="pill pa">Calendar</span><span class="pill pa">Audit Log</span><span class="pill pa">Archive</span><span class="pill pa">Gradebook</span>
    </div>
</div>

<!-- ═══ INSTRUCTOR ═══ -->
<div class="sec sec-i">
    <h3>👨‍🏫 Instructor Panel</h3>
    <div class="c"><div class="rect ri">ACCESS INSTRUCTOR PANEL</div></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="rect ri">E-LEARNING MANAGEMENT</div></div>
    <div class="row"><span class="pill pi">Course</span><span class="pill pi">Learning Path</span></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="io ii">INPUT LEARNING MATERIALS</div></div>
    <div class="row"><span class="pill pi">Modules</span><span class="pill pi">Lessons</span><span class="pill pi">Quizzes</span><span class="pill pi">Evaluation</span></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="rect ri">TRAINING MANAGEMENT</div></div>
    <div class="row"><span class="pill pi">Online Training</span><span class="pill pi">Programs</span></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="rect ri">MANAGE LEARNERS</div><p class="desc">Enroll, Track, Assign</p></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="rect ri">PROGRESS DASHBOARD</div></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="rect ri">GRADE BOOK</div><p class="desc">Scores, Pass/Fail, Reviews</p></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="rect ri">LEARNER TIMELINE</div></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="rect ri">MONITOR SKILL GAP</div><p class="desc">Per-Learner Gap Rates</p></div>
    <div class="arrow ai"></div>
    <div class="c"><div class="rect ri">CERTIFICATIONS</div><p class="desc">Add, Edit, Extend Validity</p></div>
    <div class="arrow ai"></div>
    <div class="row"><span class="pill pi">Calendar</span><span class="pill pi">Notifications</span></div>
</div>

<!-- ═══ LEARNER ═══ -->
<div class="sec sec-l">
    <h3>📚 Learner Panel</h3>
    <div class="c"><div class="rect rl">ACCESS LEARNER PANEL</div></div>
    <div class="arrow al"></div>
    <div class="c"><div class="rect rl">CATALOG</div><p class="desc">View Available Learning Items</p></div>
    <div class="arrow al"></div>
    <div class="c"><div class="io il">ENROLL TO LEARNING ITEMS</div></div>
    <div class="arrow al"></div>
    <div class="c"><div class="rect rl">STUDY</div><p class="desc">Read Lessons, Watch Content</p></div>
    <div class="arrow al"></div>
    <div class="c"><div class="rect rl">ANSWER QUIZ / EVALUATION</div><p class="desc">Tests, Assessments</p></div>
    <div class="arrow al"></div>
    <div class="c"><div class="io il">SUBMIT RESULTS</div></div>
    <div class="arrow al"></div>
    <div class="c"><div class="rect rl">COURSE TRACKING</div><p class="desc">Progress, Grades, Status</p></div>
    <div class="arrow al"></div>
    <div class="c"><div class="rect rl">SKILL GAP ANALYSIS</div><p class="desc">Gaps, Acquired Skills, Progress</p></div>
    <div class="arrow al"></div>
    <div class="c"><div class="rect rl">RESULTS &amp; CERTIFICATES</div><p class="desc">Grades, Cert View, Download</p></div>
    <div class="arrow al"></div>
    <div class="c"><div class="rect rl">GRADE BOOK</div><p class="desc">Scores, Pass/Fail, Reviews</p></div>
    <div class="arrow al"></div>
    <div class="row"><span class="pill pl">Notes</span><span class="pill pl">Calendar</span><span class="pill pl">Notifications</span><span class="pill pl">Profile</span></div>
</div>

<!-- ═══ SYSTEM SERVICES ═══ -->
<div class="sec sec-t">
    <h3>⚙️ SYSTEM SERVICES (Background / Cron Jobs)</h3>
    <div class="row">
        <span class="pill ps">📋 Enrollment Notifications</span>
        <span class="pill ps">🔔 Online Training Reminders</span>
        <span class="pill ps">⏰ Certificate Expiry Alerts</span>
        <span class="pill ps">📊 Skill Gap Auto-Analysis</span>
        <span class="pill ps">🔥 Study Streak Tracking</span>
    </div>
</div>

<!-- ═══ DATABASE TABLES ═══ -->
<div class="sec sec-d">
    <h3>🗄️ DATABASE TABLES (ld_* prefix)</h3>
    <div class="db-grid">
        <div class="db-card db-el"><h4>E-Learning</h4><ul><li>ld_course</li><li>ld_module</li><li>ld_lesson</li><li>ld_quiz</li><li>ld_evaluation</li><li>ld_quiz_attempt</li><li>ld_quiz_session</li></ul></div>
        <div class="db-card db-ep"><h4>Enrollment &amp; Progress</h4><ul><li>ld_enrollment</li><li>ld_progress</li><li>ld_grade</li><li>ld_certificate</li><li>ld_certificate_template</li><li>ld_skill_snapshot</li></ul></div>
        <div class="db-card db-tr"><h4>Training</h4><ul><li>ld_program</li><li>ld_learning_path</li><li>ld_learning_path_item</li><li>ld_video_conference</li><li>ld_conference_attendance</li><li>ld_calendar_event</li></ul></div>
        <div class="db-card db-sk"><h4>Skills</h4><ul><li>ld_skill</li><li>ld_course_skill</li><li>ld_module_skill</li></ul></div>
        <div class="db-card db-us"><h4>Users &amp; System</h4><ul><li>em_employees</li><li>ld_notification</li><li>ld_moderation_report</li><li>ld_settings</li><li>ld_audit_log</li><li>ld_announcement</li></ul></div>
        <div class="db-card db-ct"><h4>Certification Rules</h4><ul><li>All modules completed</li><li>All quizzes passed (≥70%)</li><li>Evaluation passed</li><li>All required skills acquired</li><li>→ Certificate auto-issued</li></ul></div>
    </div>
</div>

<div class="c" style="margin-top:16px">
    <div class="oval">END OF SESSION</div>
</div>

</div>
</body>
</html>