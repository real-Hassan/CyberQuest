<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$userId = (int)$_SESSION['user_id'];

$st = $pdo->prepare('SELECT COUNT(*) AS enrolled, SUM(status = \'completed\') AS done FROM enrollments WHERE user_id = ?');
$st->execute([$userId]);
$counts = $st->fetch();

$st = $pdo->prepare(
    'SELECT COUNT(*) AS solved, COALESCE(SUM(points), 0) AS pts FROM (
         SELECT DISTINCT s.challenge_id, c.points
         FROM submissions s JOIN challenges c ON c.id = s.challenge_id
         WHERE s.user_id = ? AND s.is_correct = 1
     ) t'
);
$st->execute([$userId]);
$stats = $st->fetch();

$st = $pdo->prepare(
    'SELECT m.id, m.title, m.description, m.difficulty, e.status,
            p.solved_challenges, p.total_challenges
     FROM enrollments e
     JOIN modules m ON m.id = e.module_id
     LEFT JOIN v_module_progress p ON p.user_id = e.user_id AND p.module_id = e.module_id
     WHERE e.user_id = ? ORDER BY e.started_at DESC'
);
$st->execute([$userId]);
$modules = $st->fetchAll();

$pageTitle = 'Dashboard';
$pageSub   = 'Welcome back to your cybersecurity journey.';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section class="welcome-section">
    <div>
        <h2>Welcome, <?= e($user['full_name']) ?>!</h2>
        <p>Pick a module, read the lesson, play with the simulation, then solve the challenges.</p>
    </div>
    <a href="modules.php" class="btn">Browse modules</a>
</section>

<section class="stats-grid">
    <div class="stat-card"><span>Enrolled Modules</span><h2><?= (int)$counts['enrolled'] ?></h2><small>Modules you started</small></div>
    <div class="stat-card"><span>Completed Modules</span><h2><?= (int)$counts['done'] ?></h2><small>Certificates earned</small></div>
    <div class="stat-card"><span>Solved Challenges</span><h2><?= (int)$stats['solved'] ?></h2><small>Correct answers</small></div>
    <div class="stat-card"><span>Total Points</span><h2><?= (int)$stats['pts'] ?></h2><small>Points earned</small></div>
</section>

<section class="learning-section">
    <div class="section-heading"><div><h2>My Learning</h2><p>Continue where you left off.</p></div></div>
    <?php if ($modules): ?>
        <div class="module-grid">
            <?php foreach ($modules as $m):
                $pct = percent($m['solved_challenges'], $m['total_challenges']); ?>
                <div class="module-card">
                    <span class="difficulty"><?= e($m['difficulty']) ?></span>
                    <h3><?= e($m['title']) ?></h3>
                    <p><?= e($m['description']) ?></p>
                    <div class="progress"><div style="width:<?= $pct ?>%"></div></div>
                    <div class="module-status"><?= (int)$m['solved_challenges'] ?>/<?= (int)$m['total_challenges'] ?> challenges · <?= e(ucfirst(str_replace('_', ' ', $m['status']))) ?></div>
                    <a href="module.php?id=<?= (int)$m['id'] ?>" class="module-btn">Open Module</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">⌘</div>
            <h3>No modules yet</h3>
            <p>Enroll in a module from the <a href="modules.php">modules page</a> to start learning.</p>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
