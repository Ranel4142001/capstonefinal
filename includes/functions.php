<?php
// includes/functions.php

/**
 * Establishes and returns a new MySQLi database connection.
 *
 * @return mysqli The database connection object.
 * @throws mysqli_sql_exception If the connection fails.
 */
function get_db_connection() {
    // Database connection parameters - adjust these to your actual database settings
    $servername = "localhost";
    $username = "root"; // !!! IMPORTANT: Replace with your database username
    $password = ""; // !!! IMPORTANT: Replace with your database password
    $dbname = "capstonefinal"; // Your database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        // Throw an exception for better error handling in calling scripts
        throw new mysqli_sql_exception("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

/**
 * Sanitizes input data to prevent common vulnerabilities like XSS.
 *
 * @param string $data The input string to sanitize.
 * @return string The sanitized string.
 */
function sanitize_input($data) {
    $data = trim($data); // Remove whitespace from the beginning and end of string
    $data = stripslashes($data); // Remove backslashes
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Convert special characters to HTML entities
    return $data;
}

// You can add more common utility functions here as your project grows,
// such as functions for password hashing, date formatting, etc.

?>
