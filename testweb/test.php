<?php
// admin/test.php
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Admin ID: " . ($_SESSION['admin_id'] ?? 'NOT SET') . "<br>";
echo "Current file: " . basename($_SERVER['PHP_SELF']) . "<br>";
?>