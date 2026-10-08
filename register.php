<?php
require_once __DIR__ . "/functions.php";

if (is_logged_in()) {
    redirect("profile.php");
}

$name = "";
$email = "";
$phone = "";
$graduation_year = "";
$department = "";
$current_company = "";
$job_title = "";
$location = "";
$bio = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ------------------------------------------------------
    // CSRF PROTECTION
    // ------------------------------------------------------

    if (
        !isset($_POST["csrf_token"]) ||
        !verify_csrf_token($_POST["csrf_token"])
    ) {
        set_flash(
            "error",
            "Invalid security token. Please try again."
        );

    } else {

        // --------------------------------------------------
        // GET FORM DATA
        // --------------------------------------------------

        $name = clean_text($_POST["name"] ?? "");
        $email = clean_email($_POST["email"] ?? "");
        $phone = clean_text($_POST["phone"] ?? "");
        $graduation_year = clean_text(
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

        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        $errors = [];


        // --------------------------------------------------
        // VALIDATION
        // --------------------------------------------------

        if ($name === "") {
            $errors[] = "Full name is required.";
        } elseif (strlen($name) < 2) {
            $errors[] = "Full name must contain at least 2 characters.";
        }


        if (!valid_email($email)) {
            $errors[] = "Please enter a valid email address.";
        }


        if (!valid_password($password)) {
            $errors[] =
                "Password must contain at least 8 characters.";
        }


        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }


        if ($graduation_year !== "") {

            if (
                !ctype_digit($graduation_year) ||
                (int)$graduation_year < 1900 ||
                (int)$graduation_year > (current_year() + 10)
            ) {
                $errors[] = "Please enter a valid graduation year.";
            }
        }


        // --------------------------------------------------
        // CHECK EMAIL
        // --------------------------------------------------

        if (empty($errors)) {

            if (email_exists($email)) {
                $errors[] =
                    "An account with this email already exists.";
            }
        }


        // --------------------------------------------------
        // CREATE ACCOUNT
        // --------------------------------------------------

        if (empty($errors)) {

            try {

                $db = get_db_connection();

                $db->begin_transaction();


                // ------------------------------------------
                // INSERT USER
                // ------------------------------------------

                $hashed_password = hash_password($password);

                $stmt = $db->prepare("
                    INSERT INTO users
                    (
                        name,
                        email,
                        password,
                        role
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'alumni'
                    )
                ");

                if (!$stmt) {
                    throw new Exception(
                        "Unable to prepare user registration."
                    );
                }

                $stmt->bind_param(
                    "sss",
                    $name,
                    $email,
                    $hashed_password
                );

                if (!$stmt->execute()) {

                    $stmt->close();

                    throw new Exception(
                        "Unable to create user account."
                    );
                }

                $user_id = $stmt->insert_id;

                $stmt->close();


                // ------------------------------------------
                // INSERT ALUMNI PROFILE
                // ------------------------------------------

                $stmt = $db->prepare("
                    INSERT INTO alumni
                    (
                        user_id,
                        full_name,
                        email,
                        phone,
                        graduation_year,
                        department,
                        current_company,
                        job_title,
                        location,
                        bio,
                        approval_status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        NULLIF(?, ''),
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'pending'
                    )
                ");

                if (!$stmt) {
                    throw new Exception(
                        "Unable to prepare alumni profile."
                    );
                }

                $stmt->bind_param(
                    "isssssssss",
                    $user_id,
                    $name,
                    $email,
                    $phone,
                    $graduation_year,
                    $department,
                    $current_company,
                    $job_title,
                    $location,
                    $bio
                );

                if (!$stmt->execute()) {

                    $stmt->close();

                    throw new Exception(
                        "Unable to create alumni profile."
                    );
                }

                $stmt->close();


                // ------------------------------------------
                // COMMIT
                // ------------------------------------------

                $db->commit();


                set_flash(
                    "success",
                    "Registration successful! Your account is waiting for admin approval. You can log in after approval."
                );

                redirect("login.php");


            } catch (Throwable $e) {

                // Roll back if something failed
                if (isset($db)) {
                    try {
                        $db->rollback();
                    } catch (Throwable $rollbackError) {
                        // Ignore rollback errors
                    }
                }

                if (APP_ENV === "development") {

                    set_flash(
                        "error",
                        "Registration failed: " . $e->getMessage()
                    );

                } else {

                    set_flash(
                        "error",
                        "Registration failed. Please try again later."
                    );
                }
            }
        }


        // --------------------------------------------------
        // SHOW VALIDATION ERRORS
        // --------------------------------------------------

        if (!empty($errors)) {

            foreach ($errors as $error) {

                set_flash(
                    "error",
                    $error
                );

            }
        }
    }
}

$csrf_token = csrf_token();
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
        content="Register for the College Alumni Management Portal."
    >

    <title>
        Register | <?php echo e(SITE_NAME); ?>
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>


    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <header class="site-header">

        <nav class="navbar">

            <div class="container navbar-container">

                <a
                    href="index.php"
                    class="site-logo"
                >
                    <span class="logo-icon">🎓</span>

                    <span>
                        <?php echo e(SITE_NAME); ?>
                    </span>
                </a>


                <button
                    type="button"
                    class="menu-toggle"
                    aria-label="Open navigation menu"
                    aria-expanded="false"
                >
                    ☰
                </button>


                <div class="nav-menu">

                    <a href="index.php">
                        Home
                    </a>

                    <a href="alumni.php">
                        Alumni
                    </a>

                    <a href="search-alumni.php">
                        Search
                    </a>

                    <a href="login.php">
                        Login
                    </a>

                    <a
                        href="register.php"
                        class="active"
                    >
                        Register
                    </a>

                </div>

            </div>

        </nav>

    </header>


    <!-- =====================================================
         REGISTER SECTION
    ====================================================== -->

    <main>

        <section class="auth-section">

            <div class="container">

                <div class="auth-card">

                    <div class="auth-header">

                        <div class="auth-icon">
                            🎓
                        </div>

                        <h1>
                            Join the Alumni Community
                        </h1>

                        <p>
                            Create your alumni account and
                            connect with your college community.
                        </p>

                    </div>


                    <!-- =====================================
                         FLASH MESSAGES
                    ====================================== -->

                    <?php
                    $flash_messages = get_flash_messages();
                    ?>

                    <?php if (!empty($flash_messages)): ?>

                        <div class="flash-messages">

                            <?php foreach ($flash_messages as $message): ?>

                                <div
                                    class="alert alert-<?php echo e($message["type"]); ?>"
                                    data-auto-dismiss="7000"
                                >
                                    <?php
                                    echo e(
                                        $message["message"]
                                    );
                                    ?>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>


                    <!-- =====================================
                         REGISTRATION FORM
                    ====================================== -->

                    <form
                        method="POST"
                        action="register.php"
                        class="auth-form"
                        data-validate
                        data-password-match
                        data-loading
                        enctype="multipart/form-data"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo e($csrf_token); ?>"
                        >


                        <!-- BASIC INFORMATION -->

                        <div class="form-section">

                            <h2>
                                Basic Information
                            </h2>

                            <p class="form-section-description">
                                Enter your basic personal details.
                            </p>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="name">
                                        Full Name
                                        <span>*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        value="<?php echo e($name); ?>"
                                        placeholder="Enter your full name"
                                        maxlength="100"
                                        autocomplete="name"
                                        required
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="email">
                                        Email Address
                                        <span>*</span>
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?php echo e($email); ?>"
                                        placeholder="Enter your email"
                                        maxlength="150"
                                        autocomplete="email"
                                        required
                                    >

                                </div>

                            </div>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="phone">
                                        Phone Number
                                    </label>

                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        value="<?php echo e($phone); ?>"
                                        placeholder="Enter your phone number"
                                        maxlength="30"
                                        autocomplete="tel"
                                    >

                                </div>


                                <div class="form-group">

                                    <label for="location">
                                        Current Location
                                    </label>

                                    <input
                                        type="text"
                                        id="location"
                                        name="location"
                                        value="<?php echo e($location); ?>"
                                        placeholder="City, State, Country"
                                        maxlength="150"
                                        autocomplete="address-level2"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- ACADEMIC INFORMATION -->

                        <div class="form-section">

                            <h2>
                                Academic Information
                            </h2>

                            <p class="form-section-description">
                                Tell us about your college education.
                            </p>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="department">
                                        Department
                                    </label>

                                    <input
                                        type="text"
                                        id="department"
                                        name="department"
                                        value="<?php echo e($department); ?>"
                                        placeholder="e.g. Computer Science"
                                        maxlength="150"
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
                                        value="<?php echo e($graduation_year); ?>"
                                        placeholder="e.g. 2027"
                                        min="1900"
                                        max="<?php echo e(current_year() + 10); ?>"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- PROFESSIONAL INFORMATION -->

                        <div class="form-section">

                            <h2>
                                Professional Information
                            </h2>

                            <p class="form-section-description">
                                Add your current professional details.
                            </p>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="current_company">
                                        Current Company
                                    </label>

                                    <input
                                        type="text"
                                        id="current_company"
                                        name="current_company"
                                        value="<?php echo e($current_company); ?>"
                                        placeholder="Company / Organization"
                                        maxlength="150"
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
                                        value="<?php echo e($job_title); ?>"
                                        placeholder="e.g. Software Developer"
                                        maxlength="150"
                                    >

                                </div>

                            </div>


                            <div class="form-group">

                                <label for="bio">
                                    About You
                                </label>

                                <textarea
                                    id="bio"
                                    name="bio"
                                    rows="5"
                                    maxlength="1000"
                                    placeholder="Write a short introduction about yourself..."
                                ><?php echo e($bio); ?></textarea>

                            </div>

                        </div>


                        <!-- ACCOUNT SECURITY -->

                        <div class="form-section">

                            <h2>
                                Account Security
                            </h2>

                            <p class="form-section-description">
                                Create a secure password for your account.
                            </p>


                            <div class="form-row">

                                <div class="form-group">

                                    <label for="password">
                                        Password
                                        <span>*</span>
                                    </label>

                                    <div class="password-field">

                                        <input
                                            type="password"
                                            id="password"
                                            name="password"
                                            placeholder="Minimum 8 characters"
                                            minlength="8"
                                            autocomplete="new-password"
                                            required
                                        >

                                        <button
                                            type="button"
                                            class="password-toggle"
                                            data-target="password"
                                            aria-label="Show password"
                                        >
                                            Show
                                        </button>

                                    </div>

                                </div>


                                <div class="form-group">

                                    <label for="confirm_password">
                                        Confirm Password
                                        <span>*</span>
                                    </label>

                                    <div class="password-field">

                                        <input
                                            type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            placeholder="Enter password again"
                                            minlength="8"
                                            autocomplete="new-password"
                                            required
                                        >

                                        <button
                                            type="button"
                                            class="password-toggle"
                                            data-target="confirm_password"
                                            aria-label="Show password"
                                        >
                                            Show
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- APPROVAL NOTICE -->

                        <div class="registration-notice">

                            <strong>
                                Important:
                            </strong>

                            <p>
                                After registration, your profile
                                will remain pending until an
                                administrator reviews and approves
                                it.
                            </p>

                        </div>


                        <!-- SUBMIT -->

                        <button
                            type="submit"
                            class="btn btn-primary btn-full"
                            data-single-click
                        >
                            Create Alumni Account
                        </button>


                        <p class="auth-footer-text">

                            Already have an account?

                            <a href="login.php">
                                Login here
                            </a>

                        </p>

                    </form>

                </div>

            </div>

        </section>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="site-footer">

        <div class="container">

            <div class="footer-bottom">

                <p>
                    &copy;
                    <span data-current-year></span>
                    <?php echo e(SITE_NAME); ?>.
                    All rights reserved.
                </p>

                <p>
                    <?php echo e(UNIVERSITY_NAME); ?>
                </p>

            </div>

        </div>

    </footer>


    <script src="assets/js/script.js"></script>

</body>

</html>

