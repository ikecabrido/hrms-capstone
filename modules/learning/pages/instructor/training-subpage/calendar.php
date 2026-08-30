<?php
include_once __DIR__ . "/../../../includes/header.php";
include_once __DIR__ . "/../../../includes/sidebar.php";
$month=(int)($_GET["month"]??date("n"));
$year=(int)($_GET["year"]??date("Y"));
$firstDay=mktime(0,0,0,$month,1,$year);
$daysInMonth=date("t",$firstDay);
$startDow=date("w",$firstDay);
$today=date("Y-m-d");
try{$pdo=(new Database())->getConnection();$stmt=$pdo->prepare("SELECT * FROM ld_calendar_event WHERE MONTH(event_date)=:m AND YEAR(event_date)=:y ORDER BY event_date ASC");$stmt->execute(["m"=>$month,"y"=>$year]);$events=$stmt->fetchAll(PDO::FETCH_ASSOC);$evByDay=[];foreach($events as $e){$d=(int)date("j",strtotime($e["event_date"]));$evByDay[$d][]=$e;}}catch(\Throwable $e){$evByDay=[];}
$mn=["","January","February","March","April","May","June","July","August","September","October","November","December"];
?>
<div class="module-content">
<div class="toolbar">
<a href="?page=instructor/training-subpage/calendar&month=<?=($month==1?12:$month-1)?>&year=<?=($month==1?$year-1:$year)?>" class="toolbar-mode-toggle"><i class="fas fa-chevron-left"></i></a>
<h1 class="toolbar-title" style="flex:1;text-align:center;"><?=$mn[$month]?> <?=$year?></h1>
<a href="?page=instructor/training-subpage/calendar&month=<?=($month==12?1:$month+1)?>&year=<?=($month==12?$year+1:$year)?>" class="toolbar-mode-toggle"><i class="fas fa-chevron-right"></i></a>
</div>
<div class="mode-card" style="overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;min-width:700px;">
<thead><tr><?php foreach(["Sun","Mon","Tue","Wed","Thu","Fri","Sat"] as $d):?><th style="padding:0.75rem;text-align:center;font-size:0.85rem;color:var(--text-muted);border-bottom:2px solid rgba(32,0,130,0.1);"><?=$d?></th><?php endforeach;?></tr></thead>
<tbody><?php $day=1;for($r=0;$r<6;$r++):?><tr><?php for($c=0;$c<7;$c++):if(($r===0&&$c<$startDow)||$day>$daysInMonth):?><td style="padding:0.5rem;height:100px;"></td><?php else:$isT=($today===date("Y-m-d",mktime(0,0,0,$month,$day,$year)));?><td style="padding:0.5rem;height:100px;vertical-align:top;border:1px solid rgba(32,0,130,0.06);<?php if($isT):?>background:rgba(32,0,130,0.04);<?php endif;?>"><div style="font-size:0.85rem;font-weight:<?php if($isT):?>700<?php else:?>500<?php endif;?>;color:<?php if($isT):?>var(--primary)<?php else:?>inherit<?php endif;?>;margin-bottom:0.25rem;"><?=$day?></div><?php if(!empty($evByDay[$day])):foreach(array_slice($evByDay[$day],0,3) as $ev):?><div style="font-size:0.7rem;padding:0.15rem 0.3rem;background:rgba(32,0,130,0.08);border-radius:3px;margin-bottom:2px;"><?=htmlspecialchars($ev["title"])?></div><?php endforeach;endif;?></td><?php $day++;endif;endfor;?></tr><?php if($day>$daysInMonth)break;endfor;?></tbody></table>
</div></div>