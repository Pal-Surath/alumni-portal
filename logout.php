<?php

require_once __DIR__ . "/functions.php";

// Log out the current user
logout_user();

// Create a new session for the flash message
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

set_flash(
    "success",
    "You have been logged out successfully."
);

// Redirect to login page
redirect("login.php");

?>

