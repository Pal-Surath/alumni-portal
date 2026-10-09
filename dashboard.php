<?php

// ==========================================
// ALUMNI PORTAL - ADMIN DASHBOARD
// ==========================================

require_once "../functions.php";

require_admin();

$db = get_db_connection();

$total_alumni = 0;
$approved_alumni = 0;
$pending_alumni = 0;
$rejected_alumni = 0;
$total_users = 0;

$error = "";

// ------------------------------------------
// Get statistics
// ------------------------------------------

try {

    // Total users
    $result = $db->query("
        SELECT COUNT(*) AS total
        FROM users
        WHERE role = 'alumni'
    ");

    if ($result) {
        $row = $result->fetch_assoc();
        $total_alumni = (int)$row["total"];
    }


    // Approved alumni
    $result = $db->query("
        SELECT COUNT(*) AS total
        FROM alumni
        WHERE approval_status = 'approved'
    ");

    if ($result) {
        $row = $result->fetch_assoc();
        $approved_alumni = (int)$row["total"];
    }


    // Pending alumni
    $result = $db->query("
        SELECT COUNT(*) AS total
        FROM alumni
        WHERE approval_status = 'pending'
    ");

    if ($result) {
        $row = $result->fetch_assoc();
        $pending_alumni = (int)$row["total"];
    }


    // Rejected alumni
    $result = $db->query("
        SELECT COUNT(*) AS total
        FROM alumni
        WHERE approval_status = 'rejected'
    ");

    if ($result) {
        $row = $result->fetch_assoc();
        $rejected_alumni = (int)$row["total"];
    }


    // Total users including administrators
    $result = $db->query("
        SELECT COUNT(*) AS total
        FROM users
    ");

    if ($result) {
        $row = $result->fetch_assoc();
        $total_users = (int)$row["total"];
    }

} catch (Throwable $e) {

    $error = APP_ENV === "development"
        ? $e->getMessage()
        : "Unable to load dashboard statistics.";
}


// ------------------------------------------
// Get recent alumni registrations
// ------------------------------------------

$recent_alumni = [];

try {

    $stmt = $db->prepare("
        SELECT
            a.id,
            a.full_name,
            a.email,
            a.department,
            a.graduation_year,
            a.approval_status,
            a.created_at
        FROM alumni a
        ORDER BY a.created_at DESC
        LIMIT 8
    ");

    if ($stmt) {

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $recent_alumni[] = $row;
        }

        $stmt->close();
    }

} catch (Throwable $e) {

    if ($error === "") {
        $error = APP_ENV === "development"
            ? $e->getMessage()
            : "Unable to load recent alumni.";
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
        content="Administrator dashboard for the Alumni Portal."
    >

    <title>
        Admin Dashboard - <?php echo e(SITE_NAME); ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<!-- ==========================================
     ADMIN NAVBAR
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
                    href="dashboard.php"
                    class="active"
                >
                    Dashboard
                </a>

                <a href="manage_alumni.php">
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
     ADMIN CONTENT
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
                        Dashboard
                    </h1>

                    <p>
                        Manage alumni registrations and monitor
                        the Alumni Portal.
                    </p>

                </div>

                <div class="admin-actions">

                    <a
                        href="manage_alumni.php"
                        class="btn btn-primary"
                    >
                        Manage Alumni
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


            <!-- STATISTICS -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon">
                        👥
                    </div>

                    <div class="stat-content">

                        <span class="stat-label">
                            Total Alumni
                        </span>

                        <strong>
                            <?php echo $total_alumni; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ✓
                    </div>

                    <div class="stat-content">

                        <span class="stat-label">
                            Approved
                        </span>

                        <strong>
                            <?php echo $approved_alumni; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ⏳
                    </div>

                    <div class="stat-content">

                        <span class="stat-label">
                            Pending
                        </span>

                        <strong>
                            <?php echo $pending_alumni; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ✕
                    </div>

                    <div class="stat-content">

                        <span class="stat-label">
                            Rejected
                        </span>

                        <strong>
                            <?php echo $rejected_alumni; ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🔐
                    </div>

                    <div class="stat-content">

                        <span class="stat-label">
                            Total Accounts
                        </span>

                        <strong>
                            <?php echo $total_users; ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- QUICK ACTIONS -->

            <div class="admin-content-grid">

                <div class="admin-card">

                    <div class="admin-card-header">

                        <div>

                            <h2>
                                Quick Actions
                            </h2>

                            <p>
                                Common administrator tasks.
                            </p>

                        </div>

                    </div>


                    <div class="quick-actions">

                        <a
                            href="manage_alumni.php?status=pending"
                            class="quick-action"
                        >

                            <span class="quick-action-icon">
                                ⏳
                            </span>

                            <span>
                                Review Pending Alumni
                            </span>

                        </a>


                        <a
                            href="manage_alumni.php"
                            class="quick-action"
                        >

                            <span class="quick-action-icon">
                                👥
                            </span>

                            <span>
                                Manage All Alumni
                            </span>

                        </a>


                        <a
                            href="../alumni.php"
                            class="quick-action"
                        >

                            <span class="quick-action-icon">
                                🌐
                            </span>

                            <span>
                                View Public Directory
                            </span>

                        </a>


                        <a
                            href="../search-alumni.php"
                            class="quick-action"
                        >

                            <span class="quick-action-icon">
                                🔍
                            </span>

                            <span>
                                Search Alumni
                            </span>

                        </a>

                    </div>

                </div>


                <!-- SYSTEM INFORMATION -->

                <div class="admin-card">

                    <div class="admin-card-header">

                        <div>

                            <h2>
                                System Information
                            </h2>

                            <p>
                                Current portal information.
                            </p>

                        </div>

                    </div>


                    <div class="info-list">

                        <div class="info-list-item">

                            <span>
                                Portal
                            </span>

                            <strong>
                                <?php echo e(SITE_NAME); ?>
                            </strong>

                        </div>


                        <div class="info-list-item">

                            <span>
                                Version
                            </span>

                            <strong>
                                <?php echo e(SITE_VERSION); ?>
                            </strong>

                        </div>


                        <div class="info-list-item">

                            <span>
                                University
                            </span>

                            <strong>
                                <?php echo e(UNIVERSITY_NAME); ?>
                            </strong>

                        </div>


                        <div class="info-list-item">

                            <span>
                                Department
                            </span>

                            <strong>
                                <?php echo e(DEPARTMENT_NAME); ?>
                            </strong>

                        </div>


                        <div class="info-list-item">

                            <span>
                                Current Year
                            </span>

                            <strong>
                                <?php echo current_year(); ?>
                            </strong>

                        </div>

                    </div>

                </div>

            </div>


            <!-- RECENT ALUMNI -->

            <div class="admin-card">

                <div class="admin-card-header">

                    <div>

                        <h2>
                            Recent Alumni Registrations
                        </h2>

                        <p>
                            Latest alumni accounts submitted
                            through the registration system.
                        </p>

                    </div>

                    <a
                        href="manage_alumni.php"
                        class="btn btn-secondary btn-small"
                    >
                        View All
                    </a>

                </div>


                <?php if (empty($recent_alumni)): ?>

                    <div class="empty-state">

                        <div class="empty-state-icon">
                            👥
                        </div>

                        <h3>
                            No alumni registrations yet
                        </h3>

                        <p>
                            New registrations will appear here.
                        </p>

                    </div>

                <?php else: ?>

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

                                <?php foreach ($recent_alumni as $alumni): ?>

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
                                            <?php
                                            echo e(
                                                $alumni["email"]
                                            );
                                            ?>
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
                                                class="btn btn-small btn-secondary"
                                            >
                                                View
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

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

