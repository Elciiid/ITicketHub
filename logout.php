<?php
/**
 * logout.php
 */
include 'includes/db.php';

session_unset();
session_destroy();

header("Location: login.php");
exit();
?>