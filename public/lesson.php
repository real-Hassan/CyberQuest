<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$userId   = (int)$_SESSION['user_id'];
$lessonId = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare(
    'SELECT l.*, m.title AS module_title, m.id AS module_id
     FROM lessons l JOIN modules m ON m.id = l.module_id
     WHERE l.id = ? AND m.is_published = 1'
);
$st->execute([$lessonId]);
$lesson = $st->fetch();
if (!$lesson) {
    flash('Lesson not found.', 'bad');
    header('Location: modules.php');
    exit;
}
if (!is_enrolled($pdo, $userId, (int)$lesson['module_id'])) {
    flash('Enroll in the module to open its lessons.', 'bad');
    header('Location: module.php?id=' . (int)$lesson['module_id']);
    exit;
}

$st = $pdo->prepare(
    'SELECT c.id, c.challenge_type, c.question, c.options, c.hint, c.points,
            EXISTS(SELECT 1 FROM submissions s
                    WHERE s.user_id = ? AND s.challenge_id = c.id AND s.is_correct = 1) AS solved
     FROM challenges c WHERE c.lesson_id = ? ORDER BY c.order_no, c.id'
);
$st->execute([$userId, $lessonId]);
$challenges = $st->fetchAll();

$progress = module_progress($pdo, $userId, (int)$lesson['module_id']);

$pageTitle = $lesson['title'];
$pageSub   = $lesson['module_title'];
$activeNav = 'modules';
require __DIR__ . '/../includes/header.php';
?>
<p><a class="muted" href="module.php?id=<?= (int)$lesson['module_id'] ?>">← Back to <?= e($lesson['module_title']) ?></a></p>

<section class="card lesson-content">
    <?= $lesson['content'] /* trusted, admin-authored HTML */ ?>
</section>

<?php if ($lesson['sim_key']): ?>
    <section class="card">
        <h2 class="card-title">Interactive simulation</h2>
        <p class="muted">Everything runs in your browser. Nothing here touches a real server.</p>
        <div class="sim-window"><div class="sim-bar"><i></i><i></i><i></i></div><div class="sim" data-sim="<?= e($lesson['sim_key']) ?>"></div></div>
    </section>
    <script src="assets/js/sims.js" defer></script>
<?php endif; ?>

<section class="card">
    <div class="section-heading">
        <div><h2 class="card-title">Challenges</h2>
        <p class="muted" id="module-progress"><?= (int)$progress['solved_challenges'] ?>/<?= (int)$progress['total_challenges'] ?> module challenges solved</p></div>
    </div>

    <?php foreach ($challenges as $i => $c): ?>
        <form class="challenge challenge-form <?= $c['solved'] ? 'is-solved' : '' ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="challenge_id" value="<?= (int)$c['id'] ?>">

            <div class="challenge-head">
                <strong>Q<?= $i + 1 ?>. <?= e($c['question']) ?></strong>
                <span class="badge"><?= (int)$c['points'] ?> pts</span>
            </div>

            <?php if ($c['challenge_type'] === 'mcq'): ?>
                <?php foreach (json_decode($c['options'], true) ?: [] as $idx => $opt): ?>
                    <label class="choice">
                        <input type="radio" name="answer" value="<?= (int)$idx ?>" required>
                        <span><?= e($opt) ?></span>
                    </label>
                <?php endforeach; ?>
            <?php else: ?>
                <input class="text-answer" type="text" name="answer" maxlength="255"
                       placeholder="Your answer" autocomplete="off" spellcheck="false" required>
            <?php endif; ?>

            <div class="challenge-actions">
                <button class="btn btn-sm" type="submit"><?= $c['solved'] ? 'Solved ✓' : 'Submit' ?></button>
                <?php if ($c['hint']): ?>
                    <details class="hint"><summary>Show hint</summary><?= e($c['hint']) ?></details>
                <?php endif; ?>
            </div>
            <div class="feedback" role="status"><?= $c['solved'] ? 'Correct. Already solved.' : '' ?></div>
        </form>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
