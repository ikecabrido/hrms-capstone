<?php
session_start();
require_once "../../../auth/auth_check.php";

// User is authenticated here
?>

<!DOCTYPE html>
<html>
<head>
    <title>API Test From Authenticated Session</title>
</head>
<body>
    <h1>Testing API from Authenticated Session</h1>
    
    <button onclick="testAPI()">Test API</button>
    
    <pre id="result"></pre>
    
    <script>
    async function testAPI() {
        try {
            const response = await fetch('/capstone_hr_management_system/workforce/api/wfa/get_performance_actions.php?employee_id=40', {
                method: 'GET',
                credentials: 'include'
            });
            
            const resultEl = document.getElementById('result');
            resultEl.textContent = 'Status: ' + response.status + '\n';
            resultEl.textContent += 'Content-Type: ' + response.headers.get('content-type') + '\n\n';
            
            const text = await response.text();
            resultEl.textContent += text;
            
        } catch (error) {
            document.getElementById('result').textContent = 'Error: ' + error.message;
        }
    }
    </script>
</body>
</html>
