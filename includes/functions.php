<?php
// Shared helpers for CyberQuest.

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Answers are compared as SHA-256 of the trimmed, lowercased text.
function answer_hash(string $answer): string
{
    return hash('sha256', strtolower(trim($answer)));
}

// Set a flash message (with $msg) or read-and-clear it (without).
function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['m' => $msg, 't' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function is_enrolled(PDO $pdo, int $userId, int $moduleId): bool
{
    $st = $pdo->prepare('SELECT 1 FROM enrollments WHERE user_id = ? AND module_id = ?');
    $st->execute([$userId, $moduleId]);
    return (bool)$st->fetchColumn();
}

function module_progress(PDO $pdo, int $userId, int $moduleId): array
{
    $st = $pdo->prepare(
        'SELECT total_challenges, solved_challenges
         FROM v_module_progress WHERE user_id = ? AND module_id = ?'
    );
    $st->execute([$userId, $moduleId]);
    $row = $st->fetch();
    return $row ?: ['total_challenges' => 0, 'solved_challenges' => 0];
}

function percent($solved, $total): int
{
    return $total > 0 ? (int)round($solved * 100 / $total) : 0;
}

// Marks the module completed and issues a certificate once every challenge is solved.
function finish_module_if_done(PDO $pdo, int $userId, int $moduleId): bool
{
    $p = module_progress($pdo, $userId, $moduleId);
    if ($p['total_challenges'] < 1 || $p['solved_challenges'] < $p['total_challenges']) {
        return false;
    }

    $pdo->prepare(
        "UPDATE enrollments SET status = 'completed', completed_at = NOW()
         WHERE user_id = ? AND module_id = ? AND status <> 'completed'"
    )->execute([$userId, $moduleId]);

    $check = $pdo->prepare('SELECT 1 FROM certificates WHERE user_id = ? AND module_id = ?');
    for ($i = 0; $i < 5; $i++) {
        $check->execute([$userId, $moduleId]);
        if ($check->fetchColumn()) {
            break;
        }
        $code = strtoupper(bin2hex(random_bytes(8)));
        $pdo->prepare(
            'INSERT IGNORE INTO certificates (user_id, module_id, certificate_code) VALUES (?, ?, ?)'
        )->execute([$userId, $moduleId, $code]);
    }
    return true;
}

// Emoji + gradient class for a module card (purely visual, no DB column needed).
function module_art(string $slug, int $id = 0): array
{
    $icons = [
        'linux-basics' => '🐧', 'sql-injection' => '🗄️', 'xss' => '📝', 'password-brute-force' => '🔑',
        'directory-traversal' => '📂', 'csrf' => '🎭', 'command-injection' => '💻',
        'file-upload-attacks' => '📤', 'network-scanning-basics' => '📡',
    ];
    return ['icon' => $icons[$slug] ?? '🛡️', 'g' => 'g' . ((($id + 5) % 6) + 1)];
}
