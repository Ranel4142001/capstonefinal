<?php
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to login page
$_SESSION['logout_success'] = "You have been successfully logged out.";
header("location: index.php");
exit;
?>  