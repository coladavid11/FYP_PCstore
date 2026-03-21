<?php
// Store database server address (usually localhost for XAMPP)
define('DB_HOST', 'localhost');

// Store database username (default XAMPP username is root)
define('DB_USER', 'root');

// Store database password (XAMPP default password is empty)
define('DB_PASS', '');

// Store the name of the database used by this system
define('DB_NAME', 'carrental');

// Create a database connection using PDO
// PDO is used because it is more secure and supports prepared statements
try {
    $dbh = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
    );
} 
catch (PDOException $e) {
    // If database connection fails, stop the system and show error message
    exit("Error: " . $e->getMessage());
}
?>
