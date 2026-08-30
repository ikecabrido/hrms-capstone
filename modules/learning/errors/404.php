<?php
/**
 * 404 — Not Found
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Found — Learning & Development</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #200082; --surface: #fff; --text: #f0f0ff; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { background: var(--surface); border-radius: 20px; padding: 3rem; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 10px 40px rgba(32,0,130,0.08); }
        .error-code { font-size: 6rem; font-weight: 800; color: var(--primary); line-height: 1; margin-bottom: 0.5rem; }
        .error-title { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; margin-bottom: 0.75rem; }
        .error-msg { color: #666; font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem; }
        .error-btn { display: inline-block; padding: 0.75rem 2rem; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; }
        .error-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(32,0,130,0.3); }
        .error-icon { font-size: 3rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">&#128270;</div>
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        <p class="error-msg">The page you're looking for doesn't exist or may have been moved. Check the URL or navigate from the sidebar.</p>
        <a href="javascript:history.back()" class="error-btn">Go Back</a>
        &nbsp;
        <a href="/itsar/modules/learning/index.php" class="error-btn" style="background:#666;">Home</a>
    </div>
</body>
</html>
