<?php
session_start(); // Start the session

// Destroy the session and all associated data
session_unset();
session_destroy();

// Redirect to the login page
header("Location: http://10.2.0.8/lrnph/login.php");
exit();