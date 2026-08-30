<?php
include_once __DIR__ . "/../../../includes/header.php";
include_once __DIR__ . "/../../../includes/sidebar.php";
$status=$_GET["status"]??"pending";
?>
<div class="module-content">
<div class="toolbar"><h1 class="toolbar-title"><i class="fas fa-shield-alt" style="color:var(--primary);margin-right:0.5rem;"></i>Moderation</h1></div>
<div class="toolbar-tabs" style="margin-bottom:1.5rem;">
<a href="?page=admin/moderation&status=pending" class="toolbar-tab <?=$status==="pending"?"active":""?>">Pending</a>
<a href="?page=admin/moderation&status=reviewed" class="toolbar-tab <?=$status==="reviewed"?"active":""?>">Reviewed</a>
</div>
<div class="mode-card"><div id="reports-list" style="min-height:200px;"><div style="text-align:center;padding:2rem;color:#999;">Loading...</div></div></div>
</div>