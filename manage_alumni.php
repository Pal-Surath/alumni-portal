<?php

// ==========================================
// ALUMNI PORTAL - MANAGE ALUMNI
// ==========================================

require_once "../functions.php";

require_admin();

$db = get_db_connection();

$status = trim($_GET["status"] ?? "");
$search = trim($_GET["q"] ?? "");

$allowed_statuses = [
    "pending",
    "approved",
    "rejected"
];

if (!in_array($status, $allowed_statuses, true)) {
    $status = "";
}

// ------------------------------------------
// Pagination
// ------------------------------------------

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$per_page = 15;
$offset = ($page - 1) * $per_page;

$total_records = 0;
$total_pages = 1;
$alumni_list = [];
$error = "";

// ------------------------------------------
// Count records
// ------------------------------------------

try {

    $count_sql = "
        SELECT COUNT(*) AS total
        FROM alumni a
        WHERE 1 = 1
    ";

    $count_types = "";
    $count_params = [];

    if ($status !== "") {

        $count_sql .= "
            AND a.approval_status = ?
        ";

        $count_types .= "s";
        $count_params[] = $status;
    }

    if ($search !== "") {

        $count_sql .= "
            AND (
                a.full_name LIKE ?
                OR a.email LIKE ?
                OR a.department LIKE ?
                OR a.current_company LIKE ?
                OR a.job_title LIKE ?
                OR a.location LIKE ?
            )
        ";

        $search_value = "%" . $search . "%";

        $count_types .= "ssssss";

        for ($i = 0; $i < 6; $i++) {
            $count_params[] = $search_value;
        }
    }

    $stmt = $db->prepare($count_sql);

    if (!$stmt) {
        throw new Exception("Unable to prepare count query.");
    }

    if (!empty($count_params)) {
        $stmt->bind_param(
            $count_types,
            ...$count_params
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $total_records = (int)$row["total"];

    $stmt->close();

    $total_pages = max(
        1,
        (int)ceil($total_records / $per_page)
    );

    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
    }

} catch (Throwable $e) {

    $error = APP_ENV === "development"
        ? $e->getMessage()
        : "Unable to count alumni records.";
}

// ------------------------------------------
// Get alumni records
// ------------------------------------------

try {

    $sql = "
        SELECT
            a.id,
            a.user_id,
            a.full_name,
            a.email,
            a.phone,
            a.department,
            a.graduation_year,
            a.current_company,
            a.job_title,
            a.location,
            a.approval_status,
            a.created_at
        FROM alumni a
        WHERE 1 = 1
    ";

    $types = "";
    $params = [];

    if ($status !== "") {

        $sql .= "
            AND a.approval_status = ?
        ";

        $types .= "s";
        $params[] = $status;
    }

    if ($search !== "") {

        $sql .= "
            AND (
                a.full_name LIKE ?
                OR a.email LIKE ?
                OR a.department LIKE ?
                OR a.current_company LIKE ?
                OR a.job_title LIKE ?
                OR a.location LIKE ?
            )
        ";

        $search_value = "%" . $search . "%";

        $types .= "ssssss";

        for ($i = 0; $i < 6; $i++) {
            $params[] = $search_value;
        }
    }

    $sql .= "
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $types .= "ii";

    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception("Unable to prepare alumni query.");
    }

    $stmt->bind_param(
        $types,
        ...$params
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $alumni_list[] = $row;
    }

    $stmt->close();

} catch (Throwable $e) {

    if ($error === "") {

        $error = APP_ENV === "development"
            ? $e->getMessage()
            : "Unable to load alumni records.";
    }
}

// ------------------------------------------
// Pagination URL
// ------------------------------------------

function admin_alumni_page_url(
    $page_number,
    $status,
    $search
) {

    $params = [
        "page" => $page_number
    ];

    if ($status !== "") {
        $params["status"] = $status;
    }

    if ($search !== "") {
        $params["q"] = $search;
    }

    return "manage_alumni.php?" .
        http_build_query($params);
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
        content="Manage alumni accounts and approval status."
    >

    <title>
        Manage Alumni - <?php echo e(SITE_NAME); ?>
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
                        Manage Alumni
                    </h1>

                    <p>
                        Review, approve, edit and manage alumni accounts.
                    </p>

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


            <!-- FILTER PANEL -->

            <div class="admin-card">

                <form
                    method="GET"
                    action="manage_alumni.php"
                    class="search-form"
                >

                    <div class="search-main">

                        <label for="q">
                            Search Alumni
                        </label>

                        <input
                            type="text"
                            id="q"
                            name="q"
                            value="<?php echo e($search); ?>"
                            placeholder="Name, email, department, company..."
                        >

                    </div>


                    <div class="form-group">

                        <label for="status">
                            Approval Status
                        </label>

                        <select
                            id="status"
                            name="status"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option
                                value="pending"
                                <?php echo $status === "pending" ? "selected" : ""; ?>
                            >
                                Pending
                            </option>

                            <option
                                value="approved"
                                <?php echo $status === "approved" ? "selected" : ""; ?>
                            >
                                Approved
                            </option>

                            <option
                                value="rejected"
                                <?php echo $status === "rejected" ? "selected" : ""; ?>
                            >
                                Rejected
                            </option>

                        </select>

                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Filter
                        </button>

                        <a
                            href="manage_alumni.php"
                            class="btn btn-secondary"
                        >
                            Clear
                        </a>

                    </div>

                </form>

            </div>


            <!-- SUMMARY -->

            <div class="directory-toolbar">

                <div>

                    <h2>
                        Alumni Records
                    </h2>

                    <p>
                        <?php echo $total_records; ?>
                        record<?php echo $total_records === 1 ? "" : "s"; ?>
                        found.
                    </p>

                </div>

                <div>

                    <?php if ($status !== ""): ?>

                        <span class="status-badge status-<?php echo e($status); ?>">
                            <?php
                            echo e(
                                approval_status_label($status)
                            );
                            ?>
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ALUMNI TABLE -->

            <?php if (empty($alumni_list)): ?>

                <div class="empty-state">

                    <div class="empty-state-icon">
                        👥
                    </div>

                    <h3>
                        No alumni records found
                    </h3>

                    <p>
                        Try changing your search or filter.
                    </p>

                </div>

            <?php else: ?>

                <div class="admin-card">

                    <div class="table-wrapper">

                        <table class="admin-table">

                            <thead>

                                <tr>

                                    <th>
                                        Name
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Department
                                    </th>

                                    <th>
                                        Graduation
                                    </th>

                                    <th>
                                        Current Work
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Registered
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($alumni_list as $alumni): ?>

                                    <tr>

                                        <td>

                                            <strong>
                                                <?php
                                                echo e(
                                                    $alumni["full_name"]
                                                );
                                                ?>
                                            </strong>

                                        </td>


                                        <td>

                                            <a
                                                href="mailto:<?php echo e($alumni["email"]); ?>"
                                            >
                                                <?php
                                                echo e(
                                                    $alumni["email"]
                                                );
                                                ?>
                                            </a>

                                        </td>


                                        <td>
                                            <?php
                                            echo e(
                                                $alumni["department"]
                                                ?: "—"
                                            );
                                            ?>
                                        </td>


                                        <td>
                                            <?php
                                            echo e(
                                                $alumni["graduation_year"]
                                                ?: "—"
                                            );
                                            ?>
                                        </td>


                                        <td>

                                            <?php if (!empty($alumni["job_title"])): ?>

                                                <?php
                                                echo e(
                                                    $alumni["job_title"]
                                                );
                                                ?>

                                            <?php endif; ?>


                                            <?php if (
                                                !empty($alumni["current_company"])
                                            ): ?>

                                                <br>

                                                <small>
                                                    <?php
                                                    echo e(
                                                        $alumni["current_company"]
                                                    );
                                                    ?>
                                                </small>

                                            <?php endif; ?>


                                            <?php if (
                                                empty($alumni["job_title"]) &&
                                                empty($alumni["current_company"])
                                            ): ?>

                                                —

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <span
                                                class="status-badge status-<?php echo e($alumni["approval_status"]); ?>"
                                            >
                                                <?php
                                                echo e(
                                                    approval_status_label(
                                                        $alumni["approval_status"]
                                                    )
                                                );
                                                ?>
                                            </span>

                                        </td>


                                        <td>

                                            <?php
                                            echo e(
                                                date(
                                                    "d M Y",
                                                    strtotime(
                                                        $alumni["created_at"]
                                                    )
                                                )
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <a
                                                href="edit_alumni.php?id=<?php echo (int)$alumni["id"]; ?>"
                                                class="btn btn-small btn-primary"
                                            >
                                                Manage
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- PAGINATION -->

                <?php if ($total_pages > 1): ?>

                    <div class="pagination">

                        <?php if ($page > 1): ?>

                            <a
                                href="<?php echo e(
                                    admin_alumni_page_url(
                                        $page - 1,
                                        $status,
                                        $search
                                    )
                                ); ?>"
                                class="pagination-link"
                            >
                                ← Previous
                            </a>

                        <?php endif; ?>


                        <?php

                        $start_page = max(
                            1,
                            $page - 2
                        );

                        $end_page = min(
                            $total_pages,
                            $page + 2
                        );

                        ?>


                        <?php if ($start_page > 1): ?>

                            <a
                                href="<?php echo e(
                                    admin_alumni_page_url(
                                        1,
                                        $status,
                                        $search
                                    )
                                ); ?>"
                                class="pagination-link"
                            >
                                1
                            </a>

                            <?php if ($start_page > 2): ?>

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
                                    admin_alumni_page_url(
                                        $i,
                                        $status,
                                        $search
                                    )
                                ); ?>"
                                class="pagination-link <?php echo $i === $page ? "active" : ""; ?>"
                            >
                                <?php echo $i; ?>
                            </a>

                        <?php endfor; ?>


                        <?php if ($end_page < $total_pages): ?>

                            <?php if ($end_page < $total_pages - 1): ?>

                                <span class="pagination-dots">
                                    ...
                                </span>

                            <?php endif; ?>


                            <a
                                href="<?php echo e(
                                    admin_alumni_page_url(
                                        $total_pages,
                                        $status,
                                        $search
                                    )
                                ); ?>"
                                class="pagination-link"
                            >
                                <?php echo $total_pages; ?>
                            </a>

                        <?php endif; ?>


                        <?php if ($page < $total_pages): ?>

                            <a
                                href="<?php echo e(
                                    admin_alumni_page_url(
                                        $page + 1,
                                        $status,
                                        $search
                                    )
                                ); ?>"
                                class="pagination-link"
                            >
                                Next →
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

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

