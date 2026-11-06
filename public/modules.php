<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$userId = (int)$_SESSION['user_id'];

$st = $pdo->prepare(
    'SELECT m.id, m.title, m.description, m.difficulty, m.estimated_minutes, m.is_published,
            e.status, p.solved_challenges, p.total_challenges,
            (SELECT COUNT(*) FROM lessons l WHERE l.module_id = m.id) AS lesson_count
     FROM modules m
     LEFT JOIN enrollments e ON e.module_id = m.id AND e.user_id = ?
     LEFT JOIN v_module_progress p ON p.module_id = m.id AND p.user_id = ?
     ORDER BY m.is_published DESC, m.order_no, m.id'
);
$st->execute([$userId, $userId]);
$modules = $st->fetchAll();

$pageTitle = 'Modules';
$pageSub   = 'Every module has a lesson, a simulation and challenges.';
$activeNav = 'modules';
require __DIR__ . '/../includes/header.php';
?>
<div class="module-grid">
    <?php foreach ($modules as $m): ?>
        <div class="module-card <?= $m['is_published'] ? '' : 'is-soon' ?>">
            <span class="difficulty"><?= e($m['difficulty']) ?></span>
            <h3><?= e($m['title']) ?></h3>
            <p><?= e($m['description']) ?></p>

            <?php if (!$m['is_published']): ?>
                <div class="module-status">Coming soon</div>
            <?php else:
                $pct = percent($m['solved_challenges'], $m['total_challenges']); ?>
                <div class="module-status">
                    <?= (int)$m['estimated_minutes'] ?> min ·
                    <?= $m['status'] ? (int)$m['solved_challenges'] . '/' . (int)$m['total_challenges'] . ' solved' : 'Not started' ?>
                </div>
                <?php if ($m['status']): ?>
                    <div class="progress"><div style="width:<?= $pct ?>%"></div></div>
                <?php endif; ?>
                <a href="module.php?id=<?= (int)$m['id'] ?>" class="module-btn">
                    <?= $m['status'] ? 'Continue' : 'View module' ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
