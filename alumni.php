<?php

require_once __DIR__ . "/functions.php";


// ----------------------------------------------------------
// GET CURRENT USER
// ----------------------------------------------------------

$current_user = current_user();


// ----------------------------------------------------------
// PAGINATION
// ----------------------------------------------------------

$page = isset($_GET["page"])
    ? max(1, (int)$_GET["page"])
    : 1;

$per_page = defined("PAGINATION_LIMIT")
    ? PAGINATION_LIMIT
    : 12;

$offset = ($page - 1) * $per_page;


// ----------------------------------------------------------
// SEARCH / FILTER VALUES
// ----------------------------------------------------------

$search = clean_text($_GET["search"] ?? "");
$department = clean_text($_GET["department"] ?? "");
$graduation_year = clean_text(
    $_GET["graduation_year"] ?? ""
);
$location = clean_text($_GET["location"] ?? "");


// ----------------------------------------------------------
// ALUMNI DATA
// ----------------------------------------------------------

$alumni_list = [];
$total_results = 0;
$total_pages = 1;
$departments = [];
$years = [];

try {

    $db = get_db_connection();


    // ------------------------------------------------------
    // LOAD DEPARTMENTS FOR FILTER
    // ------------------------------------------------------

    $department_result = $db->query("
        SELECT DISTINCT department
        FROM alumni
        WHERE approval_status = 'approved'
        AND department IS NOT NULL
        AND department != ''
        ORDER BY department ASC
    ");

    if ($department_result) {

        while (
            $row = $department_result->fetch_assoc()
        ) {

            $departments[] = $row["department"];

        }

    }


    // ------------------------------------------------------
    // LOAD GRADUATION YEARS FOR FILTER
    // ------------------------------------------------------

    $year_result = $db->query("
        SELECT DISTINCT graduation_year
        FROM alumni
        WHERE approval_status = 'approved'
        AND graduation_year IS NOT NULL
        ORDER BY graduation_year DESC
    ");

    if ($year_result) {

        while (
            $row = $year_result->fetch_assoc()
        ) {

            $years[] = $row["graduation_year"];

        }

    }


    // ------------------------------------------------------
    // BUILD SEARCH CONDITIONS
    // ------------------------------------------------------

    $conditions = [
        "a.approval_status = 'approved'"
    ];

    $params = [];
    $types = "";


    if ($search !== "") {

        $conditions[] = "
            (
                a.full_name LIKE ?
                OR a.email LIKE ?
                OR a.current_company LIKE ?
                OR a.job_title LIKE ?
                OR a.location LIKE ?
                OR a.department LIKE ?
            )
        ";

        $search_value = "%" . $search . "%";

        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;

        $types .= "ssssss";
    }


    if ($department !== "") {

        $conditions[] = "a.department = ?";

        $params[] = $department;

        $types .= "s";
    }


    if ($graduation_year !== "") {

        $conditions[] = "a.graduation_year = ?";

        $params[] = (int)$graduation_year;

        $types .= "i";
    }


    if ($location !== "") {

        $conditions[] = "a.location LIKE ?";

        $params[] = "%" . $location . "%";

        $types .= "s";
    }


    $where_clause =
        implode(
            " AND ",
            $conditions
        );


    // ------------------------------------------------------
    // COUNT RESULTS
    // ------------------------------------------------------

    $count_sql = "
        SELECT COUNT(*) AS total
        FROM alumni a
        WHERE $where_clause
    ";

    $stmt = $db->prepare($count_sql);

    if (!$stmt) {
        throw new Exception(
            "Unable to prepare alumni count query."
        );
    }


    if (!empty($params)) {

        $stmt->bind_param(
            $types,
            ...$params
        );

    }


    $stmt->execute();

    $count_result = $stmt->get_result();

    $count_row = $count_result->fetch_assoc();

    $total_results =
        (int)($count_row["total"] ?? 0);

    $stmt->close();


    // ------------------------------------------------------
    // CALCULATE TOTAL PAGES
    // ------------------------------------------------------

    $total_pages = max(
        1,
        (int)ceil(
            $total_results / $per_page
        )
    );


    // Prevent invalid page numbers
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
    }


    // ------------------------------------------------------
    // GET ALUMNI
    // ------------------------------------------------------

    $sql = "
        SELECT
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
            a.profile_image
        FROM alumni a
        WHERE $where_clause
        ORDER BY a.full_name ASC
        LIMIT ? OFFSET ?
    ";


    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Unable to prepare alumni query."
        );
    }


    // LIMIT and OFFSET are integers
    $params[] = $per_page;
    $params[] = $offset;

    $types .= "ii";


    $stmt->bind_param(
        $types,
        ...$params
    );


    $stmt->execute();

    $result = $stmt->get_result();


    while (
        $row = $result->fetch_assoc()
    ) {

        $alumni_list[] = $row;

    }


    $stmt->close();


} catch (Throwable $e) {

    if (APP_ENV === "development") {

        set_flash(
            "error",
            "Unable to load alumni directory: " .
            $e->getMessage()
        );

    } else {

        set_flash(
            "error",
            "Unable to load the alumni directory right now."
        );

    }

}


// ----------------------------------------------------------
// FLASH MESSAGES
// ----------------------------------------------------------

$flash_messages = get_flash_messages();


// ----------------------------------------------------------
// PROFILE IMAGE HELPER
// ----------------------------------------------------------

function alumni_initials($name)
{
    $name = trim($name);

    if ($name === "") {
        return "A";
    }

    $parts = preg_split(
        "/\s+/",
        $name
    );

    $initials = "";

    if (!empty($parts[0])) {

        $initials .= strtoupper(
            substr(
                $parts[0],
                0,
                1
            )
        );

    }

    if (
        count($parts) > 1 &&
        !empty(
            $parts[count($parts) - 1]
        )
    ) {

        $initials .= strtoupper(
            substr(
                $parts[count($parts) - 1],
                0,
                1
            )
        );

    }

    return $initials ?: "A";
}


// ----------------------------------------------------------
// BUILD QUERY STRING FOR PAGINATION
// ----------------------------------------------------------

function alumni_page_url($page_number)
{
    $query = [];

    if (
        isset($_GET["search"]) &&
        $_GET["search"] !== ""
    ) {
        $query["search"] = $_GET["search"];
    }

    if (
        isset($_GET["department"]) &&
        $_GET["department"] !== ""
    ) {
        $query["department"] = $_GET["department"];
    }

    if (
        isset($_GET["graduation_year"]) &&
        $_GET["graduation_year"] !== ""
    ) {
        $query["graduation_year"] =
            $_GET["graduation_year"];
    }

    if (
        isset($_GET["location"]) &&
        $_GET["location"] !== ""
    ) {
        $query["location"] = $_GET["location"];
    }

    $query["page"] = $page_number;

    return "alumni.php?" .
        http_build_query($query);
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
        content="Browse the approved alumni directory."
    >

    <meta
        name="keywords"
        content="alumni directory, college alumni, university alumni"
    >

    <title>
        Alumni Directory | <?php echo e(SITE_NAME); ?>
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

                    <a
                        href="alumni.php"
                        class="active"
                    >
                        Alumni
                    </a>

                    <a href="search-alumni.php">
                        Search
                    </a>


                    <?php if ($current_user): ?>

                        <a href="profile.php">
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

                    <?php else: ?>

                        <a href="login.php">
                            Login
                        </a>

                        <a
                            href="register.php"
                            class="nav-register"
                        >
                            Register
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </nav>

    </header>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main>

        <section class="section">

            <div class="container">


                <!-- =========================================
                     PAGE HEADER
                ========================================== -->

                <div class="section-heading">

                    <span class="section-label">
                        ALUMNI NETWORK
                    </span>

                    <h1>
                        Alumni Directory
                    </h1>

                    <p>
                        Discover and connect with approved
                        alumni from our university community.
                    </p>

                </div>


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
                     SEARCH / FILTER
                ========================================== -->

                <div class="search-panel">

                    <form
                        method="GET"
                        action="alumni.php"
                        class="search-form"
                    >

                        <div class="form-group search-main">

                            <label for="search">
                                Search Alumni
                            </label>

                            <input
                                type="search"
                                id="search"
                                name="search"
                                value="<?php echo e($search); ?>"
                                placeholder="Name, company, job title, department..."
                                data-search-input
                            >

                        </div>


                        <div class="form-group">

                            <label for="department">
                                Department
                            </label>

                            <select
                                id="department"
                                name="department"
                            >

                                <option value="">
                                    All Departments
                                </option>

                                <?php foreach (
                                    $departments as $item
                                ): ?>

                                    <option
                                        value="<?php echo e($item); ?>"
                                        <?php echo $department === $item
                                            ? "selected"
                                            : ""; ?>
                                    >
                                        <?php echo e($item); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="graduation_year">
                                Graduation Year
                            </label>

                            <select
                                id="graduation_year"
                                name="graduation_year"
                            >

                                <option value="">
                                    All Years
                                </option>

                                <?php foreach (
                                    $years as $year
                                ): ?>

                                    <option
                                        value="<?php echo e($year); ?>"
                                        <?php echo (string)$graduation_year ===
                                            (string)$year
                                            ? "selected"
                                            : ""; ?>
                                    >
                                        <?php echo e($year); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="location">
                                Location
                            </label>

                            <input
                                type="text"
                                id="location"
                                name="location"
                                value="<?php echo e($location); ?>"
                                placeholder="City or country"
                            >

                        </div>


                        <div class="search-actions">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Search
                            </button>

                            <a
                                href="alumni.php"
                                class="btn btn-outline"
                            >
                                Clear
                            </a>

                        </div>

                    </form>

                </div>


                <!-- =========================================
                     RESULT COUNT
                ========================================== -->

                <div class="directory-toolbar">

                    <p>

                        <strong>
                            <?php echo e($total_results); ?>
                        </strong>

                        approved
                        <?php
                        echo $total_results === 1
                            ? "alumnus"
                            : "alumni";
                        ?>

                        found

                    </p>

                    <?php if ($total_pages > 1): ?>

                        <p>
                            Page
                            <strong>
                                <?php echo e($page); ?>
                            </strong>
                            of
                            <strong>
                                <?php echo e($total_pages); ?>
                            </strong>
                        </p>

                    <?php endif; ?>

                </div>


                <!-- =========================================
                     ALUMNI CARDS
                ========================================== -->

                <?php if (!empty($alumni_list)): ?>

                    <div class="alumni-grid">

                        <?php foreach (
                            $alumni_list as $alumni
                        ): ?>

                            <?php
                            $image_url =
                                profile_image_url(
                                    $alumni[
                                        "profile_image"
                                    ] ?? null
                                );

                            $initials =
                                alumni_initials(
                                    $alumni[
                                        "full_name"
                                    ]
                                );
                            ?>

                            <article class="alumni-card">

                                <div class="alumni-card-image">

                                    <?php if ($image_url): ?>

                                        <img
                                            src="<?php echo e($image_url); ?>"
                                            alt="<?php echo e($alumni["full_name"]); ?>"
                                            loading="lazy"
                                        >

                                    <?php else: ?>

                                        <div class="profile-placeholder">
                                            <?php
                                            echo e(
                                                $initials
                                            );
                                            ?>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div class="alumni-card-content">

                                    <h2>
                                        <?php
                                        echo e(
                                            $alumni[
                                                "full_name"
                                            ]
                                        );
                                        ?>
                                    </h2>


                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "job_title"
                                            ]
                                        )
                                    ): ?>

                                        <p class="alumni-job">

                                            <?php
                                            echo e(
                                                $alumni[
                                                    "job_title"
                                                ]
                                            );
                                            ?>

                                        </p>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "current_company"
                                            ]
                                        )
                                    ): ?>

                                        <p>
                                            🏢
                                            <?php
                                            echo e(
                                                $alumni[
                                                    "current_company"
                                                ]
                                            );
                                            ?>
                                        </p>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "department"
                                            ]
                                        )
                                    ): ?>

                                        <p>
                                            🎓
                                            <?php
                                            echo e(
                                                $alumni[
                                                    "department"
                                                ]
                                            );
                                            ?>
                                        </p>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "graduation_year"
                                            ]
                                        )
                                    ): ?>

                                        <p>
                                            📅 Class of
                                            <?php
                                            echo e(
                                                $alumni[
                                                    "graduation_year"
                                                ]
                                            );
                                            ?>
                                        </p>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "location"
                                            ]
                                        )
                                    ): ?>

                                        <p>
                                            📍
                                            <?php
                                            echo e(
                                                $alumni[
                                                    "location"
                                                ]
                                            );
                                            ?>
                                        </p>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "bio"
                                            ]
                                        )
                                    ): ?>

                                        <p class="alumni-bio">

                                            <?php
                                            $short_bio =
                                                $alumni[
                                                    "bio"
                                                ];

                                            if (
                                                strlen(
                                                    $short_bio
                                                ) > 140
                                            ) {

                                                $short_bio =
                                                    substr(
                                                        $short_bio,
                                                        0,
                                                        140
                                                    ) .
                                                    "...";

                                            }

                                            echo e(
                                                $short_bio
                                            );
                                            ?>

                                        </p>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $alumni[
                                                "email"
                                            ]
                                        )
                                    ): ?>

                                        <a
                                            href="mailto:<?php echo e($alumni["email"]); ?>"
                                            class="btn btn-small btn-outline"
                                        >
                                            Contact
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>


                    <!-- =====================================
                         PAGINATION
                    ====================================== -->

                    <?php if ($total_pages > 1): ?>

                        <nav
                            class="pagination"
                            aria-label="Alumni directory pagination"
                        >

                            <?php if ($page > 1): ?>

                                <a
                                    href="<?php echo e(
                                        alumni_page_url(
                                            $page - 1
                                        )
                                    ); ?>"
                                    class="pagination-link"
                                >
                                    ← Previous
                                </a>

                            <?php endif; ?>


                            <?php

                            $start_page =
                                max(
                                    1,
                                    $page - 2
                                );

                            $end_page =
                                min(
                                    $total_pages,
                                    $page + 2
                                );

                            ?>


                            <?php if ($start_page > 1): ?>

                                <a
                                    href="<?php echo e(
                                        alumni_page_url(1)
                                    ); ?>"
                                    class="pagination-link"
                                >
                                    1
                                </a>

                                <?php if (
                                    $start_page > 2
                                ): ?>

                                    <span class="pagination-dots">
                                        ...
                                    </span>

                                <?php endif; ?>

                            <?php endif; ?>


                            <?php for (
                                $i = $start_page;
                                $i <= $end_page;
                                $i++
                            ): ?>

                                <a
                                    href="<?php echo e(
                                        alumni_page_url($i)
                                    ); ?>"
                                    class="pagination-link
                                        <?php echo $i === $page
                                            ? "active"
                                            : ""; ?>"
                                >
                                    <?php echo e($i); ?>
                                </a>

                            <?php endfor; ?>


                            <?php if (
                                $end_page < $total_pages
                            ): ?>

                                <?php if (
                                    $end_page < $total_pages - 1
                                ): ?>

                                    <span class="pagination-dots">
                                        ...
                                    </span>

                                <?php endif; ?>

                                <a
                                    href="<?php echo e(
                                        alumni_page_url(
                                            $total_pages
                                        )
                                    ); ?>"
                                    class="pagination-link"
                                >
                                    <?php
                                    echo e(
                                        $total_pages
                                    );
                                    ?>
                                </a>

                            <?php endif; ?>


                            <?php if (
                                $page < $total_pages
                            ): ?>

                                <a
                                    href="<?php echo e(
                                        alumni_page_url(
                                            $page + 1
                                        )
                                    ); ?>"
                                    class="pagination-link"
                                >
                                    Next →
                                </a>

                            <?php endif; ?>

                        </nav>

                    <?php endif; ?>


                <?php else: ?>


                    <!-- =====================================
                         EMPTY RESULT
                    ====================================== -->

                    <div class="empty-state">

                        <div class="empty-state-icon">
                            🔎
                        </div>

                        <h2>
                            No Alumni Found
                        </h2>

                        <p>
                            We couldn't find any approved
                            alumni matching your search.
                        </p>

                        <?php if (
                            $search !== "" ||
                            $department !== "" ||
                            $graduation_year !== "" ||
                            $location !== ""
                        ): ?>

                            <a
                                href="alumni.php"
                                class="btn btn-primary"
                            >
                                Clear Filters
                            </a>

                        <?php else: ?>

                            <a
                                href="register.php"
                                class="btn btn-primary"
                            >
                                Become an Alumni Member
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

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

