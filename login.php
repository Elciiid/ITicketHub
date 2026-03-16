<?php
// ITicketHub now uses the centralized LRNPH login system
// Redirect to the main login page
session_start();
$_SESSION['redirect_after_login'] = 'http://10.2.0.8/ITicketHub/index.php';
header("Location: http://10.2.0.8/lrnph/login.php");
exit();
?>