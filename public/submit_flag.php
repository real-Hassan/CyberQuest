<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

function reply(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!is_logged_in()) {
    reply(401, ['ok' => false, 'message' => 'Please log in again.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token()) {
    reply(403, ['ok' => false, 'message' => 'Invalid request. Refresh the page and try again.']);
}

$userId      = (int)$_SESSION['user_id'];
$challengeId = (int)($_POST['challenge_id'] ?? 0);
$answer      = trim((string)($_POST['answer'] ?? ''));

if ($answer === '' || strlen($answer) > 255) {
    reply(422, ['ok' => false, 'message' => 'Enter an answer (max 255 characters).']);
}

$st = $pdo->prepare(
    'SELECT c.id, c.answer_hash, c.points, l.module_id
     FROM challenges c
     JOIN lessons l ON l.id = c.lesson_id
     JOIN modules m ON m.id = l.module_id
     WHERE c.id = ? AND m.is_published = 1'
);
$st->execute([$challengeId]);
$ch = $st->fetch();
if (!$ch) {
    reply(404, ['ok' => false, 'message' => 'Challenge not found.']);
}

$moduleId = (int)$ch['module_id'];
if (!is_enrolled($pdo, $userId, $moduleId)) {
    reply(403, ['ok' => false, 'message' => 'Enroll in the module first.']);
}

// Already solved: do not log again.
$st = $pdo->prepare('SELECT 1 FROM submissions WHERE user_id = ? AND challenge_id = ? AND is_correct = 1 LIMIT 1');
$st->execute([$userId, $challengeId]);
if ($st->fetchColumn()) {
    reply(200, ['ok' => true, 'correct' => true, 'message' => 'Already solved.', 'progress' => module_progress($pdo, $userId, $moduleId)]);
}

// Rate limit: at most 10 wrong answers per challenge per minute.
$st = $pdo->prepare(
    'SELECT COUNT(*) FROM submissions
     WHERE user_id = ? AND challenge_id = ? AND is_correct = 0
       AND submitted_at > (NOW() - INTERVAL 1 MINUTE)'
);
$st->execute([$userId, $challengeId]);
if ((int)$st->fetchColumn() >= 10) {
    reply(429, ['ok' => false, 'message' => 'Too many wrong attempts. Wait a minute and try again.']);
}

$correct = hash_equals($ch['answer_hash'], answer_hash($answer));

$pdo->prepare('INSERT INTO submissions (user_id, challenge_id, submitted_answer, is_correct) VALUES (?, ?, ?, ?)')
    ->execute([$userId, $challengeId, $answer, $correct ? 1 : 0]);

$completed = $correct ? finish_module_if_done($pdo, $userId, $moduleId) : false;

reply(200, [
    'ok'        => true,
    'correct'   => $correct,
    'points'    => $correct ? (int)$ch['points'] : 0,
    'completed' => $completed,
    'message'   => $correct
        ? ($completed ? 'Correct! Module complete. Your certificate is ready.' : 'Correct!')
        : 'Not quite. Try again or open the hint.',
    'progress'  => module_progress($pdo, $userId, $moduleId),
]);
