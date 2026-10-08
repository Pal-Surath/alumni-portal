<?php

// ============================================================
// ALUMNI PORTAL - DATABASE CONNECTION
// Supports:
// 1. Railway MySQL
// 2. XAMPP Local MySQL
// ============================================================

require_once __DIR__ . "/config.php";


// ============================================================
// DETECT DATABASE ENVIRONMENT
// ============================================================
//
// Railway provides MYSQLHOST, MYSQLPORT, MYSQLUSER,
// MYSQLPASSWORD and MYSQLDATABASE.
//
// XAMPP normally uses:
// Host     = localhost
// Username = root
// Password =
// Database = alumni_db
//
// ============================================================

$railway_host = getenv("MYSQLHOST");
$railway_port = getenv("MYSQLPORT");
$railway_user = getenv("MYSQLUSER");
$railway_pass = getenv("MYSQLPASSWORD");
$railway_db   = getenv("MYSQLDATABASE");


// ============================================================
// USE RAILWAY DATABASE IF ENVIRONMENT VARIABLES EXIST
// ============================================================

if (
    !empty($railway_host) &&
    !empty($railway_user) &&
    !empty($railway_db)
) {

    $db_host = $railway_host;

    $db_port = !empty($railway_port)
        ? (int)$railway_port
        : 3306;

    $db_user = $railway_user;
    $db_pass = $railway_pass ?: "";
    $db_name = $railway_db;

}


// ============================================================
// OTHERWISE USE LOCAL XAMPP MYSQL
// ============================================================

else {

    $db_host = "localhost";
    $db_port = 3306;

    $db_user = "root";
    $db_pass = "";

    // Create this database in phpMyAdmin when testing locally.
    $db_name = "alumni_db";
}


// ============================================================
// CREATE MYSQL CONNECTION
// ============================================================

$conn = new mysqli(
    $db_host,
    $db_user,
    $db_pass,
    $db_name,
    $db_port
);


// ============================================================
// CHECK CONNECTION
// ============================================================

if ($conn->connect_errno) {

    // Do not expose sensitive database information
    // to normal website visitors.

    if (APP_ENV === "development") {

        die(
            "Database connection failed: " .
            $conn->connect_error
        );

    } else {

        die(
            "Database connection failed. Please try again later."
        );
    }
}


// ============================================================
// UTF-8 SUPPORT
// ============================================================

if (!$conn->set_charset("utf8mb4")) {

    if (APP_ENV === "development") {

        die(
            "Unable to configure database character set."
        );
    }
}


// ============================================================
// OPTIONAL DATABASE HELPER
// ============================================================

function get_db_connection()
{
    global $conn;

    return $conn;
}


// ============================================================
// END OF DATABASE CONNECTION
// ============================================================

