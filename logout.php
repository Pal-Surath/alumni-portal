<?php

require_once "../functions.php";

// Log out the administrator
logout_user();

// Start a new session so we can show the flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_flash(
    "success",
    "You have been logged out of the administrator account."
);

// Return to admin login
redirect("login.php");

?>
