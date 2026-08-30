<?php
include_once __DIR__ . "/../../../includes/header.php";
include_once __DIR__ . "/../../../includes/sidebar.php";
try{$pdo=(new Database())->getConnection();$stmt=$pdo->query("SELECT s.*, COUNT(DISTINCT cs.course_id) AS course_count FROM ld_skill s LEFT JOIN ld_course_skill cs ON cs.skill_id=s.id GROUP BY s.id ORDER BY s.name ASC");$skills=$stmt->fetchAll(PDO::FETCH_ASSOC);}catch(\Throwable $e){$skills=[];}
?>
<div class="module-content">
<div class="toolbar"><h1 class="toolbar-title"><i class="fas fa-star" style="color:var(--primary);margin-right:0.5rem;"></i>Skills</h1><div class="toolbar-search"><input type="search" id="skill-search" placeholder="Search skills..." /></div></div>
<div class="mode-card">
<?php if(empty($skills)):?><div style="text-align:center;padding:3rem;color:#999;"><h3>No skills yet</h3></div><?php else:?>
<div id="skills-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
<?php foreach($skills as $s):?>
<div class="content-card-item" data-search="<?=htmlspecialchars(strtolower($s["name"]))?>">
<div class="content-card-thumb" style="background:linear-gradient(135deg,rgba(32,0,130,0.1),rgba(32,0,130,0.05));color:var(--primary);font-size:1.5rem;font-weight:700;"><?=strtoupper(substr($s["name"],0,2))?></div>
<div class="content-card-body"><h3 class="content-card-title"><?=htmlspecialchars($s["name"])?></h3><div class="content-card-meta"><span class="pill"><?=$s["course_count"]?> course<?=$s["course_count"]!=1?"s":""?></span></div></div>
</div>
<?php endforeach;?>
</div><?php endif;?>
</div></div>
<script>document.getElementById("skill-search").addEventListener("input",function(){var q=this.value.toLowerCase();document.querySelectorAll("[data-search]").forEach(function(c){c.style.display=c.dataset.search.indexOf(q)!==-1?"":"none";});});</script>