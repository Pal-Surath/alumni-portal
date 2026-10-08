<?php

require_once __DIR__ . "/functions.php";

// ----------------------------------------------------------
// LOGIN REQUIRED
// ----------------------------------------------------------

require_login();

$user_id = current_user_id();

if (!$user_id) {
    redirect("login.php");
}


// ----------------------------------------------------------
// GET USER DATA
// ----------------------------------------------------------

$user = get_user_by_id($user_id);
$alumni = get_alumni_by_user_id($user_id);

if (!$user || !$alumni) {

    set_flash(
        "error",
        "Unable to load your profile."
    );

    logout_user();

    redirect("login.php");
}


// ----------------------------------------------------------
// FLASH MESSAGES
// ----------------------------------------------------------

$flash_messages = get_flash_messages();


// ----------------------------------------------------------
// PROFILE IMAGE
// ----------------------------------------------------------

$image_url = profile_image_url(
    $alumni["profile_image"] ?? null
);


// ----------------------------------------------------------
// INITIALS
// ----------------------------------------------------------

$display_name =
    $alumni["full_name"]
    ?: $user["name"]
    ?: "Alumni";

$initials = "";

$name_parts = preg_split(
    "/\s+/",
    trim($display_name)
);

if (!empty($name_parts[0])) {

    $initials =
        strtoupper(
            substr(
                $name_parts[0],
                0,
                1
            )
        );

}

if (
    count($name_parts) > 1 &&
    !empty($name_parts[count($name_parts) - 1])
) {

    $initials .=
        strtoupper(
            substr(
                $name_parts[count($name_parts) - 1],
                0,
                1
            )
        );

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
        content="View your alumni profile."
    >

    <title>
        My Profile | <?php echo e(SITE_NAME); ?>
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
                        href="profile.php"
                        class="active"
                    >
                        My Profile
                    </a>

                    <?php if (is_admin()): ?>

                        <a href="admin/dashboard.php">
                            Admin
                        </a>

                    <?php endif; ?>

                    <a href="logout.php">
                        Logout
                    </a>

                </div>

            </div>

        </nav>

    </header>


    <!-- =====================================================
         MAIN PROFILE
    ====================================================== -->

    <main>

        <section class="profile-section">

            <div class="container">


                <!-- =========================================
                     FLASH MESSAGES
                ========================================== -->

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


                <!-- =========================================
                     PROFILE HEADER
                ========================================== -->

                <div class="profile-header">

                    <div class="profile-avatar">

                        <?php if ($image_url): ?>

                            <img
                                src="<?php echo e($image_url); ?>"
                                alt="<?php echo e($display_name); ?>"
                            >

                        <?php else: ?>

                            <div class="profile-placeholder">
                                <?php echo e($initials); ?>
                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="profile-header-info">

                        <h1>
                            <?php
                            echo e($display_name);
                            ?>
                        </h1>


                        <?php if (
                            !empty($alumni["job_title"])
                        ): ?>

                            <p class="profile-job">

                                <?php
                                echo e(
                                    $alumni["job_title"]
                                );
                                ?>

                                <?php if (
                                    !empty(
                                        $alumni["current_company"]
                                    )
                                ): ?>

                                    at

                                    <?php
                                    echo e(
                                        $alumni[
                                            "current_company"
                                        ]
                                    );
                                    ?>

                                <?php endif; ?>

                            </p>

                        <?php endif; ?>


                        <?php if (
                            !empty($alumni["location"])
                        ): ?>

                            <p class="profile-location">
                                📍
                                <?php
                                echo e(
                                    $alumni["location"]
                                );
                                ?>
                            </p>

                        <?php endif; ?>


                        <span
                            class="status-badge status-<?php echo e(
                                strtolower(
                                    $alumni["approval_status"]
                                )
                            ); ?>"
                        >
                            <?php
                            echo e(
                                approval_status_label(
                                    $alumni[
                                        "approval_status"
                                    ]
                                )
                            );
                            ?>
                        </span>

                    </div>


                    <div class="profile-header-action">

                        <?php if (is_admin()): ?>

                            <a
                                href="admin/edit_alumni.php?id=<?php echo e($alumni["user_id"]); ?>"
                                class="btn btn-outline"
                            >
                                Edit Profile
                            </a>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =========================================
                     PROFILE INFORMATION
                ========================================== -->

                <div class="profile-grid">


                    <!-- =====================================
                         PERSONAL INFORMATION
                    ====================================== -->

                    <div class="profile-card">

                        <div class="profile-card-header">

                            <h2>
                                Personal Information
                            </h2>

                        </div>


                        <div class="profile-details">

                            <div class="profile-detail">

                                <span class="detail-label">
                                    Full Name
                                </span>

                                <span class="detail-value">
                                    <?php
                                    echo e(
                                        $alumni["full_name"]
                                    );
                                    ?>
                                </span>

                            </div>


                            <div class="profile-detail">

                                <span class="detail-label">
                                    Email
                                </span>

                                <span class="detail-value">

                                    <a
                                        href="mailto:<?php echo e($alumni["email"]); ?>"
                                    >
                                        <?php
                                        echo e(
                                            $alumni["email"]
                                        );
                                        ?>
                                    </a>

                                </span>

                            </div>


                            <?php if (
                                !empty($alumni["phone"])
                            ): ?>

                                <div class="profile-detail">

                                    <span class="detail-label">
                                        Phone
                                    </span>

                                    <span class="detail-value">

                                        <a
                                            href="tel:<?php echo e($alumni["phone"]); ?>"
                                        >
                                            <?php
                                            echo e(
                                                $alumni["phone"]
                                            );
                                            ?>
                                        </a>

                                    </span>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty($alumni["location"])
                            ): ?>

                                <div class="profile-detail">

                                    <span class="detail-label">
                                        Location
                                    </span>

                                    <span class="detail-value">
                                        <?php
                                        echo e(
                                            $alumni["location"]
                                        );
                                        ?>
                                    </span>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- =====================================
                         ACADEMIC INFORMATION
                    ====================================== -->

                    <div class="profile-card">

                        <div class="profile-card-header">

                            <h2>
                                Academic Information
                            </h2>

                        </div>


                        <div class="profile-details">

                            <div class="profile-detail">

                                <span class="detail-label">
                                    Department
                                </span>

                                <span class="detail-value">

                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "department"
                                            ]
                                        )
                                    ): ?>

                                        <?php
                                        echo e(
                                            $alumni[
                                                "department"
                                            ]
                                        );
                                        ?>

                                    <?php else: ?>

                                        Not provided

                                    <?php endif; ?>

                                </span>

                            </div>


                            <div class="profile-detail">

                                <span class="detail-label">
                                    Graduation Year
                                </span>

                                <span class="detail-value">

                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "graduation_year"
                                            ]
                                        )
                                    ): ?>

                                        <?php
                                        echo e(
                                            $alumni[
                                                "graduation_year"
                                            ]
                                        );
                                        ?>

                                    <?php else: ?>

                                        Not provided

                                    <?php endif; ?>

                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- =====================================
                         PROFESSIONAL INFORMATION
                    ====================================== -->

                    <div class="profile-card">

                        <div class="profile-card-header">

                            <h2>
                                Professional Information
                            </h2>

                        </div>


                        <div class="profile-details">

                            <div class="profile-detail">

                                <span class="detail-label">
                                    Current Company
                                </span>

                                <span class="detail-value">

                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "current_company"
                                            ]
                                        )
                                    ): ?>

                                        <?php
                                        echo e(
                                            $alumni[
                                                "current_company"
                                            ]
                                        );
                                        ?>

                                    <?php else: ?>

                                        Not provided

                                    <?php endif; ?>

                                </span>

                            </div>


                            <div class="profile-detail">

                                <span class="detail-label">
                                    Job Title
                                </span>

                                <span class="detail-value">

                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "job_title"
                                            ]
                                        )
                                    ): ?>

                                        <?php
                                        echo e(
                                            $alumni[
                                                "job_title"
                                            ]
                                        );
                                        ?>

                                    <?php else: ?>

                                        Not provided

                                    <?php endif; ?>

                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- =====================================
                         ABOUT
                    ====================================== -->

                    <div class="profile-card">

                        <div class="profile-card-header">

                            <h2>
                                About Me
                            </h2>

                        </div>


                        <div class="profile-bio">

                            <?php if (
                                !empty(
                                    $alumni["bio"]
                                )
                            ): ?>

                                <p>
                                    <?php
                                    echo nl2br(
                                        e(
                                            $alumni["bio"]
                                        )
                                    );
                                    ?>
                                </p>

                            <?php else: ?>

                                <p class="text-muted">
                                    No biography has been added
                                    yet.
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- =========================================
                     ACCOUNT INFORMATION
                ========================================== -->

                <div class="profile-card account-card">

                    <div class="profile-card-header">

                        <h2>
                            Account Information
                        </h2>

                    </div>


                    <div class="profile-details">

                        <div class="profile-detail">

                            <span class="detail-label">
                                Account Type
                            </span>

                            <span class="detail-value">

                                <?php if (
                                    $user["role"] === "admin"
                                ): ?>

                                    Administrator

                                <?php else: ?>

                                    Alumni

                                <?php endif; ?>

                            </span>

                        </div>


                        <div class="profile-detail">

                            <span class="detail-label">
                                Account Created
                            </span>

                            <span class="detail-value">

                                <?php
                                echo e(
                                    date(
                                        "d M Y",
                                        strtotime(
                                            $user["created_at"]
                                        )
                                    )
                                );
                                ?>

                            </span>

                        </div>


                        <div class="profile-detail">

                            <span class="detail-label">
                                Profile Status
                            </span>

                            <span class="detail-value">

                                <?php
                                echo e(
                                    approval_status_label(
                                        $alumni[
                                            "approval_status"
                                        ]
                                    )
                                );
                                ?>

                            </span>

                        </div>

                    </div>

                </div>


                <!-- =========================================
                     ACTIONS
                ========================================== -->

                <div class="profile-actions">

                    <a
                        href="alumni.php"
                        class="btn btn-primary"
                    >
                        Browse Alumni
                    </a>

                    <a
                        href="search-alumni.php"
                        class="btn btn-outline"
                    >
                        Search Alumni
                    </a>

                    <a
                        href="logout.php"
                        class="btn btn-danger"
                    >
                        Logout
                    </a>

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

