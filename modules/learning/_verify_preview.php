<?php
$width = max(320, min(1600, (int) ($_GET['w'] ?? 390)));
$height = max(500, min(2400, (int) ($_GET['h'] ?? 1200)));
$page = (string) ($_GET['page'] ?? 'admin/analytics');
?><!doctype html>
<html>
<head><meta charset="utf-8"><title>Preview</title>
<style>html,body{margin:0;padding:0;background:#d1d5db}iframe{display:block;width:<?= $width ?>px;height:<?= $height ?>px;border:0;background:#fff}</style>
</head>
<body><iframe id="preview" src="index.php?page=<?= htmlspecialchars(rawurlencode($page), ENT_QUOTES, 'UTF-8') ?>"></iframe></body>
</html>
