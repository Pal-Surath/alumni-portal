<?php

// ==========================================
// ALUMNI PORTAL - SEARCH ALUMNI
// ==========================================

require_once "functions.php";

$db = get_db_connection();

$search = trim($_GET["q"] ?? "");
$department = trim($_GET["department"] ?? "");
$graduation_year = trim($_GET["graduation_year"] ?? "");
$location = trim($_GET["location"] ?? "");

$results = [];
$error = "";

// ------------------------------------------
// Load filter options
// ------------------------------------------

$departments = [];
$years = [];

try {
    $department_result = $db->query("
        SELECT DISTINCT department
        FROM alumni
        WHERE approval_status = 'approved'
          AND department IS NOT NULL
          AND department != ''
        ORDER BY department ASC
    ");

    if ($department_result) {
        while ($row = $department_result->fetch_assoc()) {
            $departments[] = $row["department"];
        }
    }

    $year_result = $db->query("
        SELECT DISTINCT graduation_year
        FROM alumni
        WHERE approval_status = 'approved'
          AND graduation_year IS NOT NULL
        ORDER BY graduation_year DESC
    ");

    if ($year_result) {
        while ($row = $year_result->fetch_assoc()) {
            $years[] = $row["graduation_year"];
        }
    }
} catch (Throwable $e) {
    $error = APP_ENV === "development"
        ? $e->getMessage()
        : "Unable to load search filters.";
}

// ------------------------------------------
// Search alumni
// ------------------------------------------

try {

    $sql = "
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
            a.profile_image
        FROM alumni a
        WHERE a.approval_status = 'approved'
    ";

    $types = "";
    $params = [];

    // General search
    if ($search !== "") {

        $sql .= "
            AND (
                a.full_name LIKE ?
                OR a.email LIKE ?
                OR a.current_company LIKE ?
                OR a.job_title LIKE ?
                OR a.location LIKE ?
                OR a.department LIKE ?
            )
        ";

        $search_value = "%" . $search . "%";

        $types .= "ssssss";

        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
        $params[] = $search_value;
    }

    // Department filter
    if ($department !== "") {

        $sql .= " AND a.department = ? ";

        $types .= "s";
        $params[] = $department;
    }

    // Graduation year filter
    if ($graduation_year !== "" && ctype_digit($graduation_year)) {

        $sql .= " AND a.graduation_year = ? ";

        $types .= "i";
        $params[] = (int)$graduation_year;
    }

    // Location filter
    if ($location !== "") {

        $sql .= " AND a.location LIKE ? ";

        $types .= "s";
        $params[] = "%" . $location . "%";
    }

    $sql .= " ORDER BY a.full_name ASC ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception("Unable to prepare search query.");
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }

    $stmt->close();

} catch (Throwable $e) {

    $error = APP_ENV === "development"
        ? $e->getMessage()
        : "Unable to search alumni.";
}

// ------------------------------------------
// Helper functions
// ------------------------------------------

function search_alumni_initials($name)
{
    $name = trim($name);

    if ($name === "") {
        return "A";
    }

    $parts = preg_split('/\s+/', $name);

    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 1));
    }

    return strtoupper(
        substr($parts[0], 0, 1) .
        substr($parts[count($parts) - 1], 0, 1)
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
        content="Search approved alumni in the Alumni Portal."
    >

    <title>Search Alumni - <?php echo e(SITE_NAME); ?></title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<!-- ==========================================
     NAVBAR
========================================== -->

<header class="site-header">

    <nav class="navbar">

        <div class="container navbar-inner">

            <a href="index.php" class="brand">
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

                <a href="index.php">
                    Home
                </a>

                <a href="alumni.php">
                    Alumni
                </a>

                <a href="search-alumni.php" class="active">
                    Search
                </a>

                <?php if (is_logged_in()): ?>

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
                        class="nav-button"
                    >
                        Register
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </nav>

</header>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<main>

    <section class="page-header">

        <div class="container">

            <h1>
                Search Alumni
            </h1>

            <p>
                Find alumni by name, department, graduation year,
                company, job title, or location.
            </p>

        </div>

    </section>


    <section class="section">

        <div class="container">

            <!-- SEARCH FORM -->

            <div class="card search-panel">

                <form
                    method="GET"
                    action="search-alumni.php"
                    class="search-form"
                >

                    <div class="search-main">

                        <label for="q">
                            Search
                        </label>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="<?php echo e($search); ?>"
                            placeholder="Name, company, job title, location..."
                        >

                    </div>


                    <div class="form-row">

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

                                <?php foreach ($departments as $item): ?>

                                    <option
                                        value="<?php echo e($item); ?>"
                                        <?php echo $department === $item ? "selected" : ""; ?>
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

                                <?php foreach ($years as $year): ?>

                                    <option
                                        value="<?php echo e($year); ?>"
                                        <?php echo (string)$graduation_year === (string)$year ? "selected" : ""; ?>
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

                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Search Alumni
                        </button>

                        <a
                            href="search-alumni.php"
                            class="btn btn-secondary"
                        >
                            Clear
                        </a>

                    </div>

                </form>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="alert alert-error">
                    <?php echo e($error); ?>
                </div>

            <?php endif; ?>


            <!-- RESULTS -->

            <div class="directory-toolbar">

                <div>

                    <h2>
                        Search Results
                    </h2>

                    <p>
                        <?php echo count($results); ?>
                        alumni found.
                    </p>

                </div>

            </div>


            <?php if (empty($results)): ?>

                <div class="empty-state">

                    <div class="empty-state-icon">
                        🔍
                    </div>

                    <h3>
                        No alumni found
                    </h3>

                    <p>
                        Try changing your search terms or filters.
                    </p>

                </div>

            <?php else: ?>

                <div class="alumni-grid">

                    <?php foreach ($results as $alumni): ?>

                        <article class="alumni-card">

                            <div class="alumni-avatar">

                                <?php if (!empty($alumni["profile_image"])): ?>

                                    <img
                                        src="<?php echo e(profile_image_url($alumni["profile_image"])); ?>"
                                        alt="<?php echo e($alumni["full_name"]); ?>"
                                    >

                                <?php else: ?>

                                    <span>
                                        <?php
                                        echo e(
                                            search_alumni_initials(
                                                $alumni["full_name"]
                                            )
                                        );
                                        ?>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="alumni-content">

                                <h3>
                                    <?php echo e($alumni["full_name"]); ?>
                                </h3>


                                <?php if (!empty($alumni["job_title"])): ?>

                                    <p class="alumni-role">
                                        <?php echo e($alumni["job_title"]); ?>
                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($alumni["current_company"])): ?>

                                    <p>
                                        <strong>Company:</strong>
                                        <?php echo e($alumni["current_company"]); ?>
                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($alumni["department"])): ?>

                                    <p>
                                        <strong>Department:</strong>
                                        <?php echo e($alumni["department"]); ?>
                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($alumni["graduation_year"])): ?>

                                    <p>
                                        <strong>Graduation:</strong>
                                        <?php echo e($alumni["graduation_year"]); ?>
                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($alumni["location"])): ?>

                                    <p>
                                        <strong>Location:</strong>
                                        <?php echo e($alumni["location"]); ?>
                                    </p>

                                <?php endif; ?>


                                <?php if (!empty($alumni["bio"])): ?>

                                    <p class="alumni-bio">
                                        <?php
                                        $short_bio = trim($alumni["bio"]);

                                        if (strlen($short_bio) > 120) {
                                            $short_bio =
                                                substr($short_bio, 0, 120) . "...";
                                        }

                                        echo e($short_bio);
                                        ?>
                                    </p>

                                <?php endif; ?>


                                <div class="alumni-actions">

                                    <a
                                        href="mailto:<?php echo e($alumni["email"]); ?>"
                                        class="btn btn-small btn-primary"
                                    >
                                        Contact
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

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


<script src="assets/js/script.js"></script>

</body>

</html>

