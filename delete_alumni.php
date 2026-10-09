<?php

// ==========================================
// ALUMNI PORTAL - DELETE ALUMNI
// ==========================================

require_once "../functions.php";

require_admin();

$db = get_db_connection();

$alumni_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($alumni_id <= 0) {
    set_flash("error", "Invalid alumni record.");
    redirect("manage_alumni.php");
}

// ------------------------------------------
// Handle deletion
// ------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    set_flash(
        "error",
        "Invalid deletion request."
    );

    redirect(
        "edit_alumni.php?id=" . $alumni_id
    );
}

try {

    // CSRF protection
    if (!verify_csrf_token($_POST["csrf_token"] ?? "")) {
        throw new Exception(
            "Invalid security token. Please try again."
        );
    }

    // --------------------------------------
    // Get alumni record
    // --------------------------------------

    $stmt = $db->prepare("
        SELECT
            id,
            user_id,
            profile_image,
            full_name
        FROM alumni
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception(
            "Unable to prepare alumni query."
        );
    }

    $stmt->bind_param(
        "i",
        $alumni_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $alumni = $result->fetch_assoc();

    $stmt->close();

    if (!$alumni) {
        throw new Exception(
            "Alumni record not found."
        );
    }

    $user_id = (int)$alumni["user_id"];
    $profile_image = $alumni["profile_image"];


    // --------------------------------------
    // Begin transaction
    // --------------------------------------

    $db->begin_transaction();


    // --------------------------------------
    // Delete alumni record
    // --------------------------------------

    $stmt = $db->prepare("
        DELETE FROM alumni
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new Exception(
            "Unable to prepare alumni deletion."
        );
    }

    $stmt->bind_param(
        "i",
        $alumni_id
    );

    if (!$stmt->execute()) {

        $stmt->close();

        throw new Exception(
            "Unable to delete alumni record."
        );
    }

    $stmt->close();


    // --------------------------------------
    // Delete user account
    // --------------------------------------

    $stmt = $db->prepare("
        DELETE FROM users
        WHERE id = ?
          AND role = 'alumni'
    ");

    if (!$stmt) {
        throw new Exception(
            "Unable to prepare account deletion."
        );
    }

    $stmt->bind_param(
        "i",
        $user_id
    );

    if (!$stmt->execute()) {

        $stmt->close();

        throw new Exception(
            "Unable to delete user account."
        );
    }

    $stmt->close();


    // --------------------------------------
    // Commit transaction
    // --------------------------------------

    $db->commit();


    // --------------------------------------
    // Delete profile image
    // --------------------------------------

    if (!empty($profile_image)) {
        delete_profile_image($profile_image);
    }


    set_flash(
        "success",
        "Alumni account for " .
        $alumni["full_name"] .
        " was deleted successfully."
    );

    redirect("manage_alumni.php");

} catch (Throwable $e) {

    try {
        $db->rollback();
    } catch (Throwable $rollback_error) {
        // Ignore rollback errors
    }

    $message = APP_ENV === "development"
        ? $e->getMessage()
        : "Unable to delete alumni account.";

    set_flash(
        "error",
        $message
    );

    redirect(
        "edit_alumni.php?id=" . $alumni_id
    );
}

?>

