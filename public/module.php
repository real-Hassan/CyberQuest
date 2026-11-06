<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$userId   = (int)$_SESSION['user_id'];
$moduleId = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare('SELECT * FROM modules WHERE id = ? AND is_published = 1');
$st->execute([$moduleId]);
$module = $st->fetch();
if (!$module) {
    flash('Module not found.', 'bad');
    header('Location: modules.php');
    exit;
}

$st = $pdo->prepare('SELECT status FROM enrollments WHERE user_id = ? AND module_id = ?');
$st->execute([$userId, $moduleId]);
$enrollment = $st->fetch();

$st = $pdo->prepare(
    'SELECT l.id, l.title, l.order_no,
            (SELECT COUNT(*) FROM challenges c WHERE c.lesson_id = l.id) AS total,
            (SELECT COUNT(DISTINCT s.challenge_id) FROM submissions s
               JOIN challenges c ON c.id = s.challenge_id
              WHERE c.lesson_id = l.id AND s.user_id = ? AND s.is_correct = 1) AS solved
     FROM lessons l WHERE l.module_id = ? ORDER BY l.order_no'
);
$st->execute([$userId, $moduleId]);
$lessons = $st->fetchAll();

$progress = module_progress($pdo, $userId, $moduleId);
$pct = percent($progress['solved_challenges'], $progress['total_challenges']);

$cert = null;
if ($enrollment && $enrollment['status'] === 'completed') {
    $st = $pdo->prepare('SELECT certificate_code FROM certificates WHERE user_id = ? AND module_id = ?');
    $st->execute([$userId, $moduleId]);
    $cert = $st->fetchColumn();
}

$pageTitle = $module['title'];
$pageSub   = $module['difficulty'] . ' · about ' . (int)$module['estimated_minutes'] . ' minutes';
$activeNav = 'modules';
require __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <p class="muted"><?= e($module['description']) ?></p>

    <?php if ($enrollment): ?>
        <div class="progress progress-lg"><div style="width:<?= $pct ?>%"></div></div>
        <p class="muted"><?= (int)$progress['solved_challenges'] ?> of <?= (int)$progress['total_challenges'] ?> challenges solved (<?= $pct ?>%)</p>
        <?php if ($cert): ?>
            <a class="btn" href="certificate.php?code=<?= e($cert) ?>">View certificate</a>
        <?php endif; ?>
    <?php else: ?>
        <form action="enroll.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="module_id" value="<?= (int)$moduleId ?>">
            <button class="btn" type="submit">Enroll in this module</button>
        </form>
    <?php endif; ?>
</section>

<section class="learning-section">
    <div class="section-heading"><div><h2>Lessons</h2><p>More lessons for this module are coming soon.</p></div></div>
    <div class="lesson-list">
        <?php foreach ($lessons as $l): ?>
            <div class="lesson-row">
                <div>
                    <strong><?= (int)$l['order_no'] ?>. <?= e($l['title']) ?></strong>
                    <small class="muted"><?= (int)$l['solved'] ?>/<?= (int)$l['total'] ?> challenges solved</small>
                </div>
                <?php if ($enrollment): ?>
                    <a class="btn btn-sm" href="lesson.php?id=<?= (int)$l['id'] ?>">Open lesson</a>
                <?php else: ?>
                    <span class="badge">Enroll to open</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
