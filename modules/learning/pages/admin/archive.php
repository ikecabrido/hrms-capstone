<?php
include_once __DIR__ . "/../../includes/header.php";
include_once __DIR__ . "/../../includes/sidebar.php";
$type=$_GET["type"]??"all";
?>
<div class="module-content">
<div class="toolbar"><h1 class="toolbar-title"><i class="fas fa-archive" style="color:var(--primary);margin-right:0.5rem;"></i>Archive</h1></div>
<div class="toolbar-tabs" style="margin-bottom:1.5rem;">
<a href="?page=admin/archive&type=all" class="toolbar-tab <?=$type==="all"?"active":""?>">All</a>
<a href="?page=admin/archive&type=course" class="toolbar-tab <?=$type==="course"?"active":""?>">Courses</a>
<a href="?page=admin/archive&type=module" class="toolbar-tab <?=$type==="module"?"active":""?>">Modules</a>
</div>
<div class="mode-card"><div id="archive-list" style="min-height:200px;"><div style="text-align:center;padding:2rem;color:#999;">Loading...</div></div></div>
</div>