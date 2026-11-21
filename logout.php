<?php
require_once 'includes/db_connect.php';
$_SESSION = [];
session_destroy();
header("Location: login.php");
exit;
?>
