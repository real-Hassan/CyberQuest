
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Protect dashboard.

require_login();

$userId = $_SESSION["user_id"];

// Fetch user information.

$stmt = $pdo->prepare(
    "SELECT full_name, username, email
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([$userId]);

$user = $stmt->fetch();

// Handle deleted or unavailable account.

if (!$user) {

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");
    exit;

}

// Count enrolled modules.

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM enrollments
     WHERE user_id = ?"
);

$stmt->execute([$userId]);

$totalModules = $stmt->fetchColumn();


// Count completed modules.

$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM enrollments
     WHERE user_id = ?
     AND status = 'completed'"
);

$stmt->execute([$userId]);

$completedModules = $stmt->fetchColumn();


// Calculate solved challenges and earned points.

$stmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS solved_count,
        COALESCE(SUM(points), 0) AS total_points

     FROM (
        SELECT DISTINCT
            s.challenge_id,
            c.points

        FROM submissions s

        JOIN challenges c
        ON s.challenge_id = c.id

        WHERE s.user_id = ?
        AND s.is_correct = 1

     ) AS solved"
);

$stmt->execute([$userId]);

$stats = $stmt->fetch();

$solvedChallenges = $stats["solved_count"];
$totalPoints = $stats["total_points"];


// Fetch enrolled modules.

$stmt = $pdo->prepare(
    "SELECT
        m.id,
        m.title,
        m.description,
        m.difficulty,
        e.status

     FROM enrollments e

     JOIN modules m
     ON e.module_id = m.id

     WHERE e.user_id = ?

     ORDER BY e.started_at DESC"
);

$stmt->execute([$userId]);

$modules = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard | CyberQuest</title>

    <link rel="stylesheet"
          href="assets/css/styles.css">

</head>

<body class="dashboard-body">

    <div class="dashboard-wrapper">

        <!-- Sidebar -->

        <aside class="dashboard-sidebar">

            <div class="logo">
                <span>Cyber</span>Quest
            </div>

            <nav class="dashboard-nav">

                <a href="index.php" class="active">
                    <span>⌂</span> Dashboard
                </a>

                <a href="index.php">
                    <span>▤</span> My Learning
                </a>

                <a href="index.php">
                    <span>⚑</span> Challenges
                </a>

                <a href="index.php">
                    <span>♜</span> Certificates
                </a>

            </nav>

            <div class="sidebar-bottom">

                <div class="sidebar-user">

                    <div class="avatar">
                        <?= strtoupper(substr($user["username"], 0, 1)) ?>
                    </div>

                    <div>
                        <strong>
                            <?= htmlspecialchars($user["username"]) ?>
                        </strong>

                        <small>Student</small>
                    </div>

                </div>

                <form action="logout.php" method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(csrf_token()) ?>"
                    >

                    <button type="submit" class="logout-btn">
                        Logout
                    </button>

                </form>

            </div>

        </aside>


        <!-- Main Content -->

        <main class="dashboard-main">

            <header class="dashboard-header">

                <div>
                    <h1>Dashboard</h1>

                    <p>
                        Welcome back to your cybersecurity journey.
                    </p>
                </div>

                <div class="header-user">
                    <?= htmlspecialchars($user["username"]) ?>
                </div>

            </header>


            <!-- Welcome Section -->

            <section class="welcome-section">

                <div>

                    <h2>
                        Welcome, <?= htmlspecialchars($user["full_name"]) ?>!
                    </h2>

                    <p>
                        Ready to sharpen your cybersecurity skills?
                    </p>

                </div>

                <div class="welcome-icon">
                    ⌘
                </div>

            </section>


            <!-- Statistics -->

            <section class="stats-grid">

                <div class="stat-card">

                    <span>Enrolled Modules</span>

                    <h2><?= (int)$totalModules ?></h2>

                    <small>Your learning journey</small>

                </div>

                <div class="stat-card">

                    <span>Completed Modules</span>

                    <h2><?= (int)$completedModules ?></h2>

                    <small>Successfully completed</small>

                </div>

                <div class="stat-card">

                    <span>Solved Challenges</span>

                    <h2><?= (int)$solvedChallenges ?></h2>

                    <small>Correct submissions</small>

                </div>

                <div class="stat-card">

                    <span>Total Points</span>

                    <h2><?= (int)$totalPoints ?></h2>

                    <small>Your earned score</small>

                </div>

            </section>


            <!-- Learning Section -->

            <section class="learning-section">

                <div class="section-heading">

                    <div>

                        <h2>My Learning</h2>

                        <p>Continue where you left off.</p>

                    </div>

                </div>


                <?php if (count($modules) > 0): ?>

                    <div class="module-grid">

                        <?php foreach ($modules as $module): ?>

                            <div class="module-card">

                                <span class="difficulty">
                                    <?= htmlspecialchars($module["difficulty"]) ?>
                                </span>

                                <h3>
                                    <?= htmlspecialchars($module["title"]) ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars($module["description"] ?? "") ?>
                                </p>

                                <div class="module-status">

                                    <?= htmlspecialchars(
                                        ucfirst(str_replace(
                                            "_",
                                            " ",
                                            $module["status"]
                                        ))
                                    ) ?>

                                </div>

                                <a href="module.php?id=<?= (int)$module["id"] ?>"
                                   class="module-btn">

                                    Open Module

                                </a>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">⌘</div>

                        <h3>No modules yet</h3>

                        <p>
                            Your learning modules will appear here
                            once you enroll in them.
                        </p>

                    </div>

                <?php endif; ?>

            </section>


            <!-- Profile Information -->

            <section class="profile-section">

                <h2>Account Information</h2>

                <div class="profile-details">

                    <p>
                        <strong>Full Name:</strong>
                        <?= htmlspecialchars($user["full_name"]) ?>
                    </p>

                    <p>
                        <strong>Username:</strong>
                        <?= htmlspecialchars($user["username"]) ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?= htmlspecialchars($user["email"]) ?>
                    </p>

                    <p>
                        <strong>Role:</strong>
                        Student
                    </p>

                </div>

            </section>


            <footer class="dashboard-footer">

                CYBERQUEST © 2026

                <span>Learn. Explore. Exploit. Secure.</span>

            </footer>

        </main>

    </div>

</body>

</html>
