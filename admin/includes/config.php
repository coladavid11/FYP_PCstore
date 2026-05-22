<?php

$host = "localhost";
$dbname = "pcstore";
$username = "root";
$password = "";

try {

    $dbh = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Error mode
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Default fetch mode
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    // Disable emulate prepares
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {

    die("Database connection failed.");

}

?>