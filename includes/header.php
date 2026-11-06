<?php
// Shared page header + sidebar. Include AFTER auth.php, db.php, functions.php and require_login().
$userId = (int)$_SESSION['user_id'];

$st = $pdo->prepare('SELECT username, full_name, email, role FROM users WHERE id = ?');
$st->execute([$userId]);
$user = $st->fetch();
if (!$user) {
    header('Location: login.php');
    exit;
}

$pageTitle = $pageTitle ?? 'CyberQuest';
$pageSub   = $pageSub ?? '';
$activeNav = $activeNav ?? '';
$navItems  = [
    'dashboard'    => ['index.php', '⌂', 'Dashboard'],
    'modules'      => ['modules.php', '▤', 'Modules'],
    'certificates' => ['certificates.php', '♜', 'Certificates'],
];
$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | CyberQuest</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="dashboard-body">
<div class="dashboard-wrapper">

    <aside class="dashboard-sidebar">
        <div class="logo"><span>Cyber</span>Quest</div>

        <nav class="dashboard-nav">
            <?php foreach ($navItems as $key => [$href, $icon, $label]): ?>
                <a href="<?= $href ?>" class="<?= $activeNav === $key ? 'active' : '' ?>">
                    <span><?= $icon ?></span> <?= $label ?>
                </a>
            <?php endforeach; ?>
            <a href="verify.php"><span>✓</span> Verify Certificate</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <div class="avatar"><?= e(strtoupper(substr($user['username'], 0, 1))) ?></div>
                <div>
                    <strong><?= e($user['username']) ?></strong>
                    <small><?= e(ucfirst($user['role'])) ?></small>
                </div>
            </div>
            <form action="logout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-header">
            <div>
                <h1><?= e($pageTitle) ?></h1>
                <?php if ($pageSub !== ''): ?><p><?= e($pageSub) ?></p><?php endif; ?>
            </div>
            <div class="header-user"><?= e($user['username']) ?></div>
        </header>

        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['t']) ?>"><?= e($flash['m']) ?></div>
        <?php endif; ?>
