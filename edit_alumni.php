<?php

// ==========================================
// ALUMNI PORTAL - EDIT ALUMNI
// ==========================================

require_once "../functions.php";

require_admin();

$db = get_db_connection();

$alumni_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($alumni_id <= 0) {
    set_flash("error", "Invalid alumni record.");
    redirect("manage_alumni.php");
}

$error = "";
$success = "";

// ------------------------------------------
// Get alumni record
// ------------------------------------------

$alumni = null;

try {

    $stmt = $db->prepare("
        SELECT
            a.id,
            a.user_id,
            a.full_name,
            a.email,
            a.phone,
            a.graduation_year,
            a.department,
            a.current_company,
            a.job_title,
            a.location,
            a.bio,
            a.profile_image,
            a.approval_status,
            a.created_at,
            u.name AS user_name,
            u.email AS user_email
        FROM alumni a
        INNER JOIN users u ON u.id = a.user_id
        WHERE a.id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception("Unable to prepare alumni query.");
    }

    $stmt->bind_param("i", $alumni_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $alumni = $result->fetch_assoc();

    $stmt->close();

    if (!$alumni) {
        set_flash("error", "Alumni record not found.");
        redirect("manage_alumni.php");
    }

} catch (Throwable $e) {

    $error = APP_ENV === "development"
        ? $e->getMessage()
        : "Unable to load alumni record.";
}


// ------------------------------------------
// Handle update
// ------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST" && $alumni) {

    try {

        // CSRF verification
        if (!verify_csrf_token($_POST["csrf_token"] ?? "")) {
            throw new Exception(
                "Invalid security token. Please try again."
            );
        }

        $full_name = clean_text($_POST["full_name"] ?? "");
        $email = clean_email($_POST["email"] ?? "");
        $phone = clean_text($_POST["phone"] ?? "");
        $graduation_year = trim(
            $_POST["graduation_year"] ?? ""
        );
        $department = clean_text(
            $_POST["department"] ?? ""
        );
        $current_company = clean_text(
            $_POST["current_company"] ?? ""
        );
        $job_title = clean_text(
            $_POST["job_title"] ?? ""
        );
        $location = clean_text(
            $_POST["location"] ?? ""
        );
        $bio = clean_text(
            $_POST["bio"] ?? ""
        );
        $approval_status = trim(
            $_POST["approval_status"] ?? ""
        );

        // ----------------------------------
        // Validation
        // ----------------------------------

        if ($full_name === "") {
            throw new Exception(
                "Full name is required."
            );
        }

        if (strlen($full_name) < 2) {
            throw new Exception(
                "Full name must contain at least 2 characters."
            );
        }

        if (!valid_email($email)) {
            throw new Exception(
                "Please enter a valid email address."
            );
        }

        if (
            $graduation_year !== "" &&
            !ctype_digit($graduation_year)
        ) {
            throw new Exception(
                "Graduation year must be a valid year."
            );
        }

        if (
            $graduation_year !== "" &&
            (
                (int)$graduation_year < 1950 ||
                (int)$graduation_year > ((int)date("Y") + 10)
            )
        ) {
            throw new Exception(
                "Please enter a valid graduation year."
            );
        }

        $allowed_statuses = [
            STATUS_PENDING,
            STATUS_APPROVED,
            STATUS_REJECTED
        ];

        if (!in_array(
            $approval_status,
            $allowed_statuses,
            true
        )) {
            throw new Exception(
                "Invalid approval status."
            );
        }

        // ----------------------------------
        // Check duplicate email
        // ----------------------------------

        $stmt = $db->prepare("
            SELECT id
            FROM users
            WHERE email = ?
              AND id != ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to check email address."
            );
        }

        $stmt->bind_param(
            "si",
            $email,
            $alumni["user_id"]
        );

        $stmt->execute();

        $duplicate_result = $stmt->get_result();

        if ($duplicate_result->num_rows > 0) {

            $stmt->close();

            throw new Exception(
                "Another account already uses this email address."
            );
        }

        $stmt->close();


        // ----------------------------------
        // Begin transaction
        // ----------------------------------

        $db->begin_transaction();


        // ----------------------------------
        // Update users table
        // ----------------------------------

        $stmt = $db->prepare("
            UPDATE users
            SET
                name = ?,
                email = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to prepare user update."
            );
        }

        $stmt->bind_param(
            "ssi",
            $full_name,
            $email,
            $alumni["user_id"]
        );

        if (!$stmt->execute()) {
            $stmt->close();

            throw new Exception(
                "Unable to update user account."
            );
        }

        $stmt->close();


        // ----------------------------------
        // Update alumni table
        // ----------------------------------

        $year_value = null;

        if ($graduation_year !== "") {
            $year_value = (int)$graduation_year;
        }

        $stmt = $db->prepare("
            UPDATE alumni
            SET
                full_name = ?,
                email = ?,
                phone = ?,
                graduation_year = ?,
                department = ?,
                current_company = ?,
                job_title = ?,
                location = ?,
                bio = ?,
                approval_status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception(
                "Unable to prepare alumni update."
            );
        }

        $stmt->bind_param(
            "sssissssssi",
            $full_name,
            $email,
            $phone,
            $year_value,
            $department,
            $current_company,
            $job_title,
            $location,
            $bio,
            $approval_status,
            $alumni_id
        );

        if (!$stmt->execute()) {
            $stmt->close();

            throw new Exception(
                "Unable to update alumni record."
            );
        }

        $stmt->close();


        // ----------------------------------
        // Commit changes
        // ----------------------------------

        $db->commit();

        set_flash(
            "success",
            "Alumni record updated successfully."
        );

        redirect(
            "edit_alumni.php?id=" . $alumni_id
        );

    } catch (Throwable $e) {

        // Rollback if transaction is active
        try {
            $db->rollback();
        } catch (Throwable $rollback_error) {
            // Ignore rollback errors
        }

        $error = APP_ENV === "development"
            ? $e->getMessage()
            : "Unable to update alumni record.";
    }
}


// ------------------------------------------
// Reload record after update
// ------------------------------------------

if ($alumni) {

    try {

        $stmt = $db->prepare("
            SELECT
                a.id,
                a.user_id,
                a.full_name,
                a.email,
                a.phone,
                a.graduation_year,
                a.department,
                a.current_company,
                a.job_title,
                a.location,
                a.bio,
                a.profile_image,
                a.approval_status,
                a.created_at
            FROM alumni a
            WHERE a.id = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $alumni_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $updated_alumni = $result->fetch_assoc();

            if ($updated_alumni) {
                $alumni = $updated_alumni;
            }

            $stmt->close();
        }

    } catch (Throwable $e) {

        if ($error === "") {

            $error = APP_ENV === "development"
                ? $e->getMessage()
                : "Unable to refresh alumni record.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Edit and manage an alumni record."
    >

    <title>
        Edit Alumni - <?php echo e(SITE_NAME); ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<!-- ==========================================
     NAVBAR
========================================== -->

<header class="site-header">

    <nav class="navbar">

        <div class="container navbar-inner">

            <a
                href="../index.php"
                class="brand"
            >
                <?php echo e(SITE_NAME); ?>
            </a>

            <button
                type="button"
                class="menu-toggle"
                aria-label="Toggle navigation"
                aria-expanded="false"
            >
                ☰
            </button>

            <div class="nav-menu">

                <a href="../index.php">
                    Home
                </a>

                <a href="../alumni.php">
                    Alumni
                </a>

                <a href="../search-alumni.php">
                    Search
                </a>

                <a href="dashboard.php">
                    Dashboard
                </a>

                <a
                    href="manage_alumni.php"
                    class="active"
                >
                    Manage Alumni
                </a>

                <a href="logout.php">
                    Logout
                </a>

            </div>

        </div>

    </nav>

</header>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<main>

    <section class="admin-section">

        <div class="container">

            <!-- PAGE HEADER -->

            <div class="admin-page-header">

                <div>

                    <span class="eyebrow">
                        Administration
                    </span>

                    <h1>
                        Edit Alumni
                    </h1>

                    <p>
                        Update alumni information and approval status.
                    </p>

                </div>

                <div class="admin-actions">

                    <a
                        href="manage_alumni.php"
                        class="btn btn-secondary"
                    >
                        ← Back to Alumni
                    </a>

                </div>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="alert alert-error">
                    <?php echo e($error); ?>
                </div>

            <?php endif; ?>


            <!-- FLASH MESSAGES -->

            <?php
            $flash_messages = get_flash_messages();
            ?>

            <?php if (!empty($flash_messages)): ?>

                <?php foreach ($flash_messages as $flash): ?>

                    <div
                        class="alert alert-<?php echo e($flash["type"]); ?>"
                        data-auto-dismiss
                    >
                        <?php echo e($flash["message"]); ?>
                    </div>

                <?php endforeach; ?>

            <?php endif; ?>


            <?php if ($alumni): ?>

                <!-- PROFILE SUMMARY -->

                <div class="admin-card">

                    <div class="profile-header">

                        <div class="profile-avatar">

                            <?php if (
                                !empty($alumni["profile_image"])
                            ): ?>

                                <img
                                    src="<?php
                                    echo e(
                                        profile_image_url(
                                            $alumni["profile_image"]
                                        )
                                    );
                                    ?>"
                                    alt="<?php
                                    echo e(
                                        $alumni["full_name"]
                                    );
                                    ?>"
                                >

                            <?php else: ?>

                                <span>
                                    <?php
                                    echo e(
                                        strtoupper(
                                            substr(
                                                $alumni["full_name"],
                                                0,
                                                1
                                            )
                                        )
                                    );
                                    ?>
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="profile-header-info">

                            <h2>
                                <?php
                                echo e(
                                    $alumni["full_name"]
                                );
                                ?>
                            </h2>

                            <p>
                                <?php
                                echo e(
                                    $alumni["email"]
                                );
                                ?>
                            </p>

                            <span
                                class="status-badge status-<?php
                                echo e(
                                    $alumni["approval_status"]
                                );
                                ?>"
                            >
                                <?php
                                echo e(
                                    approval_status_label(
                                        $alumni["approval_status"]
                                    )
                                );
                                ?>
                            </span>

                        </div>

                    </div>

                </div>


                <!-- EDIT FORM -->

                <div class="admin-card">

                    <div class="admin-card-header">

                        <div>

                            <h2>
                                Alumni Information
                            </h2>

                            <p>
                                Modify the information below.
                            </p>

                        </div>

                    </div>


                    <form
                        method="POST"
                        action="edit_alumni.php?id=<?php echo $alumni_id; ?>"
                        data-validate
                        data-loading
                    >

                        <?php echo csrf_field(); ?>


                        <!-- PERSONAL INFORMATION -->

                        <div class="form-section">

                            <h3>
                                Personal Information
                            </h3>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="full_name">
                                        Full Name *
                                    </label>

                                    <input
                                        type="text"
                                        id="full_name"
                                        name="full_name"
                                        value="<?php
                                        echo e(
                                            $alumni["full_name"]
                                        );
                                        ?>"
                                        required
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="email">
                                        Email Address *
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?php
                                        echo e(
                                            $alumni["email"]
                                        );
                                        ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="phone">
                                        Phone
                                    </label>

                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        value="<?php
                                        echo e(
                                            $alumni["phone"]
                                        );
                                        ?>"
                                        placeholder="+91..."
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="location">
                                        Location
                                    </label>

                                    <input
                                        type="text"
                                        id="location"
                                        name="location"
                                        value="<?php
                                        echo e(
                                            $alumni["location"]
                                        );
                                        ?>"
                                        placeholder="City, State, Country"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- ACADEMIC INFORMATION -->

                        <div class="form-section">

                            <h3>
                                Academic Information
                            </h3>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="department">
                                        Department
                                    </label>

                                    <input
                                        type="text"
                                        id="department"
                                        name="department"
                                        value="<?php
                                        echo e(
                                            $alumni["department"]
                                        );
                                        ?>"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="graduation_year">
                                        Graduation Year
                                    </label>

                                    <input
                                        type="number"
                                        id="graduation_year"
                                        name="graduation_year"
                                        value="<?php
                                        echo e(
                                            $alumni["graduation_year"]
                                        );
                                        ?>"
                                        min="1950"
                                        max="<?php
                                        echo ((int)date("Y") + 10);
                                        ?>"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- PROFESSIONAL INFORMATION -->

                        <div class="form-section">

                            <h3>
                                Professional Information
                            </h3>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="current_company">
                                        Current Company
                                    </label>

                                    <input
                                        type="text"
                                        id="current_company"
                                        name="current_company"
                                        value="<?php
                                        echo e(
                                            $alumni["current_company"]
                                        );
                                        ?>"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="job_title">
                                        Job Title
                                    </label>

                                    <input
                                        type="text"
                                        id="job_title"
                                        name="job_title"
                                        value="<?php
                                        echo e(
                                            $alumni["job_title"]
                                        );
                                        ?>"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- BIO -->

                        <div class="form-section">

                            <h3>
                                Biography
                            </h3>

                            <div class="form-group">

                                <label for="bio">
                                    Bio
                                </label>

                                <textarea
                                    id="bio"
                                    name="bio"
                                    rows="6"
                                    placeholder="Write a short biography..."
                                ><?php
                                echo e(
                                    $alumni["bio"]
                                );
                                ?></textarea>

                            </div>

                        </div>


                        <!-- APPROVAL -->

                        <div class="form-section">

                            <h3>
                                Approval Status
                            </h3>

                            <div class="form-group">

                                <label for="approval_status">
                                    Account Status *
                                </label>

                                <select
                                    id="approval_status"
                                    name="approval_status"
                                    required
                                >

                                    <option
                                        value="pending"
                                        <?php
                                        echo $alumni["approval_status"] === "pending"
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        Pending
                                    </option>

                                    <option
                                        value="approved"
                                        <?php
                                        echo $alumni["approval_status"] === "approved"
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        Approved
                                    </option>

                                    <option
                                        value="rejected"
                                        <?php
                                        echo $alumni["approval_status"] === "rejected"
                                            ? "selected"
                                            : "";
                                        ?>
                                    >
                                        Rejected
                                    </option>

                                </select>

                                <small class="form-help">
                                    Approved alumni can log in and appear
                                    in the public alumni directory.
                                </small>

                            </div>

                        </div>


                        <!-- FORM ACTIONS -->

                        <div class="form-actions">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Save Changes
                            </button>

                            <a
                                href="manage_alumni.php"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </a>

                            <a
                                href="delete_alumni.php?id=<?php echo $alumni_id; ?>"
                                class="btn btn-danger confirm-action"
                                data-confirm="Are you sure you want to delete this alumni account? This action cannot be undone."
                            >
                                Delete Alumni
                            </a>

                        </div>

                    </form>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>


<!-- ==========================================
     FOOTER
========================================== -->

<footer class="site-footer">

    <div class="container">

        <p>
            &copy;
            <span data-current-year></span>
            <?php echo e(SITE_NAME); ?>.
            All rights reserved.
        </p>

        <p>
            <?php echo e(UNIVERSITY_NAME); ?>
            |
            <?php echo e(DEPARTMENT_NAME); ?>
        </p>

    </div>

</footer>


<script src="../assets/js/script.js"></script>

</body>

</html>

