<?php

// ==========================================
// ALUMNI PORTAL - COMMON FUNCTIONS
// ==========================================

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/db.php";

// ------------------------------------------
// HTML escaping
// ------------------------------------------
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// ------------------------------------------
// Redirect
// ------------------------------------------
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

// ------------------------------------------
// Login status
// ------------------------------------------
function is_logged_in()
{
    return isset($_SESSION["user_id"]);
}

// ------------------------------------------
// Current user ID
// ------------------------------------------
function current_user_id()
{
    return $_SESSION["user_id"] ?? null;
}

// ------------------------------------------
// Current user role
// ------------------------------------------
function current_user_role()
{
    return $_SESSION["role"] ?? null;
}

// ------------------------------------------
// Check admin
// ------------------------------------------
function is_admin()
{
    return is_logged_in() && current_user_role() === "admin";
}

// ------------------------------------------
// Require login
// ------------------------------------------
function require_login()
{
    if (!is_logged_in()) {
        set_flash("error", "Please login to continue.");
        redirect("login.php");
    }
}

// ------------------------------------------
// Require admin
// ------------------------------------------
function require_admin()
{
    if (!is_logged_in() || current_user_role() !== "admin") {

        if (basename($_SERVER["PHP_SELF"]) !== "login.php") {
            set_flash("error", "Administrator access required.");
        }

        redirect("login.php");
    }
}

// ==========================================
// FLASH MESSAGES
// ==========================================

// Add flash message
function set_flash($type, $message)
{
    if (!isset($_SESSION["flash_messages"])) {
        $_SESSION["flash_messages"] = [];
    }

    $_SESSION["flash_messages"][] = [
        "type" => $type,
        "message" => $message
    ];
}

// Get flash messages
function get_flash_messages()
{
    if (!isset($_SESSION["flash_messages"])) {
        return [];
    }

    $messages = $_SESSION["flash_messages"];

    unset($_SESSION["flash_messages"]);

    return $messages;
}

// ==========================================
// Validation
// ==========================================

function valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function clean_email($email)
{
    return strtolower(trim($email));
}

function clean_text($text)
{
    return trim($text);
}

function valid_password($password)
{
    return strlen($password) >= 8;
}

// ==========================================
// Password functions
// ==========================================

function hash_password($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash)
{
    return password_verify($password, $hash);
}

// ==========================================
// CSRF PROTECTION
// ==========================================

function csrf_token()
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' .
        e(csrf_token()) .
        '">';
}

function verify_csrf_token($token)
{
    return isset($_SESSION["csrf_token"]) &&
        hash_equals($_SESSION["csrf_token"], $token);
}

// ==========================================
// Image validation
// ==========================================

function validate_profile_image($file)
{
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return [
            "valid" => false,
            "error" => "No file uploaded."
        ];
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        return [
            "valid" => false,
            "error" => "Image upload failed."
        ];
    }

    if ($file["size"] > UPLOAD_MAX_SIZE) {
        return [
            "valid" => false,
            "error" => "Image size must not exceed 5 MB."
        ];
    }

    $mime = mime_content_type($file["tmp_name"]);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        return [
            "valid" => false,
            "error" => "Only JPG, PNG and WEBP images are allowed."
        ];
    }

    return [
        "valid" => true,
        "mime" => $mime
    ];
}

// ------------------------------------------
// Random image filename
// ------------------------------------------
function random_image_filename($extension)
{
    return bin2hex(random_bytes(16)) . "." . $extension;
}

// ------------------------------------------
// Image extension
// ------------------------------------------
function image_extension_from_mime($mime)
{
    $extensions = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    return $extensions[$mime] ?? "jpg";
}

// ------------------------------------------
// Delete profile image
// ------------------------------------------
function delete_profile_image($filename)
{
    if (empty($filename)) {
        return;
    }

    $path = PROFILE_UPLOAD_DIR . basename($filename);

    if (is_file($path)) {
        @unlink($path);
    }
}

// ------------------------------------------
// Ensure upload directory exists
// ------------------------------------------
function ensure_profile_image_directory()
{
    if (!is_dir(PROFILE_UPLOAD_DIR)) {
        mkdir(PROFILE_UPLOAD_DIR, 0755, true);
    }
}

// ------------------------------------------
// Profile image URL
// ------------------------------------------
function profile_image_url($filename)
{
    if (empty($filename)) {
        return null;
    }

    return rtrim(APP_URL, "/") .
        "/assets/images/profiles/" .
        rawurlencode(basename($filename));
}

// ==========================================
// USER DATABASE FUNCTIONS
// ==========================================

// Get user by ID
function get_user_by_id($id)
{
    $conn = get_db_connection();

    $stmt = $conn->prepare(
        "SELECT id, name, email, role, created_at, updated_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    return $user ?: null;
}

// Get user by email
function get_user_by_email($email)
{
    $conn = get_db_connection();

    $stmt = $conn->prepare(
        "SELECT id, name, email, password, role, created_at, updated_at
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    return $user ?: null;
}

// Check email existence
function email_exists($email, $exclude_user_id = null)
{
    $conn = get_db_connection();

    if ($exclude_user_id !== null) {

        $stmt = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             AND id != ?
             LIMIT 1"
        );

        $stmt->bind_param("si", $email, $exclude_user_id);

    } else {

        $stmt = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;

    $stmt->close();

    return $exists;
}

// ==========================================
// ALUMNI DATABASE FUNCTIONS
// ==========================================

// Get alumni by user ID
function get_alumni_by_user_id($user_id)
{
    $conn = get_db_connection();

    $stmt = $conn->prepare(
        "SELECT *
         FROM alumni
         WHERE user_id = ?
         LIMIT 1"
    );

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $alumni = $result->fetch_assoc();

    $stmt->close();

    return $alumni ?: null;
}

// Get alumni by alumni ID
function get_alumni_by_id($id)
{
    $conn = get_db_connection();

    $stmt = $conn->prepare(
        "SELECT *
         FROM alumni
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $alumni = $result->fetch_assoc();

    $stmt->close();

    return $alumni ?: null;
}

// Count alumni
function count_alumni($status = null)
{
    $conn = get_db_connection();

    if ($status !== null) {

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM alumni
             WHERE approval_status = ?"
        );

        $stmt->bind_param("s", $status);

    } else {

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM alumni"
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int)($row["total"] ?? 0);
}

// ==========================================
// LOGIN / LOGOUT
// ==========================================

function login_user($user)
{
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["user_name"] = $user["name"];
    $_SESSION["user_email"] = $user["email"];
}

function logout_user()
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

function current_user()
{
    if (!is_logged_in()) {
        return null;
    }

    return get_user_by_id(current_user_id());
}

// ==========================================
// APPROVAL STATUS
// ==========================================

function approval_status_label($status)
{
    switch ($status) {

        case "approved":
            return "Approved";

        case "rejected":
            return "Rejected";

        case "pending":
            return "Pending";

        default:
            return ucfirst($status);
    }
}

function approval_status_class($status)
{
    switch ($status) {

        case "approved":
            return "status-approved";

        case "rejected":
            return "status-rejected";

        case "pending":
            return "status-pending";

        default:
            return "";
    }
}

// ==========================================
// PAGINATION
// ==========================================

function total_pages($total_records, $per_page)
{
    if ($per_page <= 0) {
        return 1;
    }

    return max(1, (int)ceil($total_records / $per_page));
}

function pagination_offset($page, $per_page)
{
    return max(0, ($page - 1) * $per_page);
}

// ==========================================
// CURRENT YEAR
// ==========================================

function current_year()
{
    return date("Y");
}
?>

