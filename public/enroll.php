<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
    http_response_code(403);
    exit('Invalid request.');
}

$userId   = (int)$_SESSION['user_id'];
$moduleId = (int)($_POST['module_id'] ?? 0);

$st = $pdo->prepare('SELECT 1 FROM modules WHERE id = ? AND is_published = 1');
$st->execute([$moduleId]);
if (!$st->fetchColumn()) {
    flash('Module not found.', 'bad');
    header('Location: modules.php');
    exit;
}

$pdo->prepare('INSERT IGNORE INTO enrollments (user_id, module_id) VALUES (?, ?)')
    ->execute([$userId, $moduleId]);

flash('You are enrolled. Open the lesson to begin.');
header('Location: module.php?id=' . $moduleId);
exit;
