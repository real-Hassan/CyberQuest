<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$userId = (int)$_SESSION['user_id'];

$st = $pdo->prepare(
    'SELECT c.certificate_code, c.issued_at, m.title
     FROM certificates c JOIN modules m ON m.id = c.module_id
     WHERE c.user_id = ? ORDER BY c.issued_at DESC'
);
$st->execute([$userId]);
$certs = $st->fetchAll();

$pageTitle = 'Certificates';
$pageSub   = 'Earned by solving every challenge in a module.';
$activeNav = 'certificates';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($certs): ?>
    <div class="lesson-list">
        <?php foreach ($certs as $c): ?>
            <div class="lesson-row">
                <div>
                    <strong><?= e($c['title']) ?></strong>
                    <small class="muted">Issued <?= e(date('d M Y', strtotime($c['issued_at']))) ?> · <?= e($c['certificate_code']) ?></small>
                </div>
                <a class="btn btn-sm" href="certificate.php?code=<?= e($c['certificate_code']) ?>">View</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">♜</div>
        <h3>No certificates yet</h3>
        <p>Solve every challenge in a module to earn one.</p>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
