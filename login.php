<?php

// ==========================================
// ALUMNI PORTAL - ADMIN LOGIN
// ==========================================

require_once "../functions.php";

// If already logged in as admin, go to dashboard
if (is_logged_in() && is_admin()) {
    redirect("dashboard.php");
}

// If logged in as normal alumni, send them to profile
if (is_logged_in() && !is_admin()) {
    redirect("../profile.php");
}

$error = "";

// ------------------------------------------
// Handle login
// ------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        // CSRF protection
        if (!verify_csrf_token($_POST["csrf_token"] ?? "")) {
            throw new Exception("Invalid security token. Please try again.");
        }

        $email = clean_email($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        // Validation
        if ($email === "") {
            throw new Exception("Please enter your email address.");
        }

        if (!valid_email($email)) {
            throw new Exception("Please enter a valid email address.");
        }

        if ($password === "") {
            throw new Exception("Please enter your password.");
        }

        // Find user
        $user = get_user_by_email($email);

        // Only administrators can use this login
        if (!$user || $user["role"] !== ROLE_ADMIN) {
            throw new Exception("Invalid administrator email or password.");
        }

        // Verify password
        if (!verify_password($password, $user["password"])) {
            throw new Exception("Invalid administrator email or password.");
        }

        // Login admin
        login_user($user);

        // Regenerate session ID for security
        session_regenerate_id(true);

        set_flash(
            "success",
            "Welcome back, Administrator."
        );

        redirect("dashboard.php");

    } catch (Throwable $e) {

        if (APP_ENV === "development") {
            $error = $e->getMessage();
        } else {
            $error = "Unable to complete administrator login.";
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
        content="Administrator login for the Alumni Portal."
    >

    <title>
        Admin Login - <?php echo e(SITE_NAME); ?>
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

                <a
                    href="login.php"
                    class="active"
                >
                    Admin Login
                </a>

            </div>

        </div>

    </nav>

</header>


<!-- ==========================================
     ADMIN LOGIN
========================================== -->

<main>

    <section class="auth-section">

        <div class="container">

            <div class="auth-card">

                <div class="auth-header">

                    <div class="auth-icon">
                        🔐
                    </div>

                    <h1>
                        Administrator Login
                    </h1>

                    <p>
                        Sign in to manage the Alumni Portal.
                    </p>

                </div>


                <!-- ERROR MESSAGE -->

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


                <form
                    method="POST"
                    action="login.php"
                    data-validate
                    data-loading
                >

                    <?php echo csrf_field(); ?>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label for="email">
                            Administrator Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter administrator email"
                            value="<?php echo e($_POST["email"] ?? ""); ?>"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="password-field">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter administrator password"
                                autocomplete="current-password"
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


                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="btn btn-primary btn-block"
                    >
                        Login as Administrator
                    </button>

                </form>


                <div class="auth-footer">

                    <p>
                        <a href="../login.php">
                            Alumni Login
                        </a>
                    </p>

                    <p>
                        <a href="../index.php">
                            ← Back to Home
                        </a>
                    </p>

                </div>

            </div>

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

