<?php
// Shared page header + sidebar. Include AFTER auth.php, db.php, functions.php and require_login().
$userId = (int)$_SESSION['user_id'];

$st = $pdo->prepare('SELECT username, full_name, email, role FROM users WHERE id = ?');
$st->execute([$userId]);
$user = $st->fetch();
if (!$user) {
    $_SESSION = []; session_destroy();
    header('Location: login.php');
    exit;
}

$pageTitle = $pageTitle ?? 'CyberQuest';
$pageSub   = $pageSub ?? '';
$activeNav = $activeNav ?? '';
$icons = [
    'home'  => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'grid'  => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'award' => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14 7 22l5-3 5 3-1.5-8"/>',
    'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
];
$navItems = [
    'dashboard'    => ['index.php', 'home', 'Dashboard'],
    'modules'      => ['modules.php', 'grid', 'Modules'],
    'certificates' => ['certificates.php', 'award', 'Certificates'],
    'verify'       => ['verify.php', 'shield', 'Verify'],
];
$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | CyberQuest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/theme.js"></script>
</head>
<body class="dashboard-body">
<div class="dashboard-wrapper">

    <aside class="dashboard-sidebar">
        <div class="logo"><span>Cyber</span>Quest</div>

        <nav class="dashboard-nav">
            <?php foreach ($navItems as $key => [$href, $icon, $label]): ?>
                <a href="<?= $href ?>" class="<?= $activeNav === $key ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24"><?= $icons[$icon] ?></svg><?= $label ?>
                </a>
            <?php endforeach; ?>
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
                <button type="submit" class="logout-btn">Log out</button>
            </form>
        </div>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-header">
            <div>
                <h1><?= e($pageTitle) ?></h1>
                <?php if ($pageSub !== ''): ?><p><?= e($pageSub) ?></p><?php endif; ?>
            </div>
            <div class="header-actions">
                <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle light or dark theme">
                    <svg class="i-moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>
                    <svg class="i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </button>
                <div class="header-user"><?= e($user['username']) ?></div>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['t']) ?>"><?= e($flash['m']) ?></div>
        <?php endif; ?>
