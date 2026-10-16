<?php
function formatResult($result) {
    if ($result === 'passed') {
        return "<span style='color:green;'>✔ Passed</span>";
    } elseif ($result === 'failed') {
        return "<span style='color:red;'>✖ Failed</span>";
    } else {
        return "<span style='color:orange;'>⏳ Pending</span>";
    }
}