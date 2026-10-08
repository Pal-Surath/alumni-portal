<?php
require_once __DIR__ . "/functions.php";

if (is_logged_in()) {
    redirect("profile.php");
}

$email = "";
$flash_messages = [];

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
            "Invalid security token. Please refresh the page and try again."
        );

    } else {

        $email = clean_email($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        $errors = [];


        // --------------------------------------------------
        // VALIDATION
        // --------------------------------------------------

        if (!valid_email($email)) {
            $errors[] = "Please enter a valid email address.";
        }

        if ($password === "") {
            $errors[] = "Please enter your password.";
        }


        // --------------------------------------------------
        // LOGIN
        // --------------------------------------------------

        if (empty($errors)) {

            try {

                $user = get_user_by_email($email);

                if (!$user) {

                    $errors[] =
                        "Invalid email address or password.";

                } else {

                    // --------------------------------------
                    // VERIFY PASSWORD
                    // --------------------------------------

                    if (!verify_password(
                        $password,
                        $user["password"]
                    )) {

                        $errors[] =
                            "Invalid email address or password.";

                    } else {

                        // ----------------------------------
                        // CHECK ALUMNI APPROVAL
                        // ----------------------------------

                        if (
                            $user["role"] === "alumni"
                        ) {

                            $alumni =
                                get_alumni_by_user_id(
                                    (int)$user["id"]
                                );

                            if (
                                $alumni &&
                                $alumni["approval_status"] !== "approved"
                            ) {

                                if (
                                    $alumni["approval_status"] === "pending"
                                ) {

                                    $errors[] =
                                        "Your account is waiting for administrator approval.";

                                } elseif (
                                    $alumni["approval_status"] === "rejected"
                                ) {

                                    $errors[] =
                                        "Your alumni profile has been rejected. Please contact the administrator.";

                                } else {

                                    $errors[] =
                                        "Your account is not currently active.";

                                }

                            }

                        }


                        // ----------------------------------
                        // LOGIN USER
                        // ----------------------------------

                        if (empty($errors)) {

                            login_user(
                                (int)$user["id"],
                                $user["role"]
                            );

                            // Prevent session fixation
                            session_regenerate_id(true);

                            set_flash(
                                "success",
                                "Welcome back, " .
                                $user["name"] .
                                "!"
                            );


                            // --------------------------------
                            // REDIRECT
                            // --------------------------------

                            if (
                                $user["role"] === "admin"
                            ) {

                                redirect(
                                    "admin/dashboard.php"
                                );

                            } else {

                                redirect(
                                    "profile.php"
                                );

                            }
                        }
                    }
                }

            } catch (Throwable $e) {

                if (APP_ENV === "development") {

                    $errors[] =
                        "Login error: " .
                        $e->getMessage();

                } else {

                    $errors[] =
                        "Unable to process login. Please try again later.";
                }
            }
        }


        // --------------------------------------------------
        // STORE ERRORS
        // --------------------------------------------------

        foreach ($errors as $error) {

            set_flash(
                "error",
                $error
            );

        }
    }
}

$csrf_token = csrf_token();
$flash_messages = get_flash_messages();

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
        content="Login to the College Alumni Management Portal."
    >

    <title>
        Login | <?php echo e(SITE_NAME); ?>
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

                    <span class="logo-icon">
                        🎓
                    </span>

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

                    <a
                        href="login.php"
                        class="active"
                    >
                        Login
                    </a>

                    <a href="register.php">
                        Register
                    </a>

                </div>

            </div>

        </nav>

    </header>


    <!-- =====================================================
         LOGIN SECTION
    ====================================================== -->

    <main>

        <section class="auth-section">

            <div class="container">

                <div class="auth-card auth-card-small">

                    <div class="auth-header">

                        <div class="auth-icon">
                            🔐
                        </div>

                        <h1>
                            Welcome Back
                        </h1>

                        <p>
                            Login to access your alumni account.
                        </p>

                    </div>


                    <!-- =====================================
                         FLASH MESSAGES
                    ====================================== -->

                    <?php if (!empty($flash_messages)): ?>

                        <div class="flash-messages">

                            <?php foreach (
                                $flash_messages as $message
                            ): ?>

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
                         LOGIN FORM
                    ====================================== -->

                    <form
                        method="POST"
                        action="login.php"
                        class="auth-form"
                        data-validate
                        data-loading
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo e($csrf_token); ?>"
                        >


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
                                autocomplete="email"
                                maxlength="150"
                                required
                            >

                        </div>


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
                                    placeholder="Enter your password"
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


                        <button
                            type="submit"
                            class="btn btn-primary btn-full"
                            data-single-click
                        >
                            Login
                        </button>


                        <div class="login-help">

                            <p>
                                Alumni accounts require administrator
                                approval before they can log in.
                            </p>

                        </div>


                        <p class="auth-footer-text">

                            Don't have an account?

                            <a href="register.php">
                                Register as Alumni
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

