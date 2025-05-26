<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
// Optional: Role-based access control here
// if ($_SESSION['role'] !== 'admin' && basename($_SERVER['PHP_SELF']) === 'user_management.php') {
//     header("location: dashboard.php"); // Or an access denied page
//     exit;
// }
?>