<?php

// ============================================================
// ALUMNI PORTAL - CONFIGURATION
// ============================================================

// Website name
define("SITE_NAME", "Alumni Portal");

// Website version
define("SITE_VERSION", "1.0.0");

// Application environment
// Change to "production" when the website is live on Railway.
define("APP_ENV", getenv("APP_ENV") ?: "development");

// ============================================================
// SECURITY SETTINGS
// ============================================================

// Session name
if (session_status() === PHP_SESSION_NONE) {

    // Use secure cookie settings when running on HTTPS.
    $is_https = (
        isset($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off"
    );

    session_name("ALUMNI_PORTAL_SESSION");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $is_https,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


// ============================================================
// TIMEZONE
// ============================================================

date_default_timezone_set("Asia/Kolkata");


// ============================================================
// APPLICATION URL
// ============================================================

// Local XAMPP URL:
// http://localhost/alumni-portal
//
// Railway:
// We will set APP_URL through Railway environment variables.
//
// If APP_URL is not configured, the application will try to
// detect the current host automatically.

$default_protocol = (
    isset($_SERVER["HTTPS"]) &&
    $_SERVER["HTTPS"] !== "off"
) ? "https" : "http";

$default_host = $_SERVER["HTTP_HOST"] ?? "localhost";

define(
    "APP_URL",
    rtrim(
        getenv("APP_URL") ?: $default_protocol . "://" . $default_host,
        "/"
    )
);


// ============================================================
// DATABASE CONFIGURATION
// ============================================================
//
// IMPORTANT:
//
// We do NOT put the Railway database password directly here.
//
// Railway provides these environment variables:
//
// MYSQLHOST
// MYSQLPORT
// MYSQLUSER
// MYSQLPASSWORD
// MYSQLDATABASE
//
// Our db.php file will read them.
//
// For local XAMPP development, we will use localhost/root/
// configuration automatically.
//
// ============================================================


// ============================================================
// FILE UPLOAD SETTINGS
// ============================================================

define("MAX_PROFILE_IMAGE_SIZE", 5 * 1024 * 1024); // 5 MB

define(
    "PROFILE_IMAGE_DIRECTORY",
    __DIR__ . "/assets/images/profiles/"
);


// ============================================================
// ALLOWED PROFILE IMAGE TYPES
// ============================================================

$allowed_image_types = [
    "image/jpeg",
    "image/png",
    "image/webp"
];


// ============================================================
// ALLOWED FILE EXTENSIONS
// ============================================================

$allowed_image_extensions = [
    "jpg",
    "jpeg",
    "png",
    "webp"
];


// ============================================================
// ALUMNI APPROVAL STATUS
// ============================================================

define("STATUS_PENDING", "pending");
define("STATUS_APPROVED", "approved");
define("STATUS_REJECTED", "rejected");


// ============================================================
// USER ROLES
// ============================================================

define("ROLE_ALUMNI", "alumni");
define("ROLE_ADMIN", "admin");


// ============================================================
// PAGINATION
// ============================================================

define("ALUMNI_PER_PAGE", 12);


// ============================================================
// DEVELOPMENT ERROR SETTINGS
// ============================================================
//
// Development:
// Errors are displayed to help us debug.
//
// Production:
// Errors are hidden from visitors.
//
// Railway will eventually use:
// APP_ENV=production
//
// ============================================================

if (APP_ENV === "development") {

    error_reporting(E_ALL);
    ini_set("display_errors", "1");

} else {

    error_reporting(0);
    ini_set("display_errors", "0");
    ini_set("log_errors", "1");
}


// ============================================================
// APPLICATION INFORMATION
// ============================================================

define(
    "DEPARTMENT_NAME",
    "Department of Computer & System Sciences"
);

define(
    "UNIVERSITY_NAME",
    "Visva-Bharati University"
);


// ============================================================
// END OF CONFIGURATION
// ============================================================