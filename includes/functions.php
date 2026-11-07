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

// Inline SVG icon (stroke style, inherits colour and size from CSS).
function icon(string $name): string
{
    static $paths = [
        'terminal' => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'code'     => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'key'      => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.8-9.8"/><path d="m17 6 3 3"/><path d="m14 9 2 2"/>',
        'folder'   => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
        'repeat'   => '<path d="M8 3 4 7l4 4"/><path d="M4 7h16"/><path d="m16 21 4-4-4-4"/><path d="M20 17H4"/>',
        'upload'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'globe'    => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'shield'   => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/>',
        'book'     => '<path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/>',
        'check'    => '<circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/>',
        'target'   => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'star'     => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.3-6.2 3.3L7 14.2 2 9.3l6.9-1z"/>',
        'award'    => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14 7 22l5-3 5 3-1.5-8"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'zap'      => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
        'trophy'   => '<path d="M6 4h12v5a6 6 0 0 1-12 0z"/><path d="M6 6H3v1a4 4 0 0 0 4 4"/><path d="M18 6h3v1a4 4 0 0 1-4 4"/><path d="M12 15v4"/><path d="M8 21h8"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? $paths['shield']) . '</svg>';
}

// Icon + gradient class for a module card (purely visual, no DB column needed).
function module_art(string $slug, int $id = 0): array
{
    $icons = [
        'linux-basics' => 'terminal', 'sql-injection' => 'database', 'xss' => 'code', 'password-brute-force' => 'key',
        'directory-traversal' => 'folder', 'csrf' => 'repeat', 'command-injection' => 'terminal',
        'file-upload-attacks' => 'upload', 'network-scanning-basics' => 'globe',
    ];
    return ['icon' => icon($icons[$slug] ?? 'shield'), 'g' => 'g' . ((($id + 5) % 6) + 1)];
}

// ---- Master certificate (derived from the individual certificates; no DB change) ----

// True when the user holds a certificate for every published module.
function all_modules_done(PDO $pdo, int $userId): bool
{
    $total = (int)$pdo->query('SELECT COUNT(*) FROM modules WHERE is_published = 1')->fetchColumn();
    if ($total < 1) {
        return false;
    }
    $st = $pdo->prepare(
        'SELECT COUNT(DISTINCT c.module_id) FROM certificates c
         JOIN modules m ON m.id = c.module_id
         WHERE c.user_id = ? AND m.is_published = 1'
    );
    $st->execute([$userId]);
    return (int)$st->fetchColumn() >= $total;
}

// 16 hex chars: 6 = user id, 10 = HMAC tag. Verifiable without storing anything.
function master_code(int $userId): string
{
    $secret = hash('sha256', 'cyberquest-master|' . ($GLOBALS['password'] ?? ''));
    return strtoupper(sprintf('%06x', $userId) . substr(hash_hmac('sha256', 'master:' . $userId, $secret), 0, 10));
}

// Returns ['code','issued_at','full_name','titles'] for a valid master code, else null.
function master_cert_lookup(PDO $pdo, string $code): ?array
{
    if (!preg_match('/^[A-F0-9]{16}$/', $code)) {
        return null;
    }
    $userId = hexdec(substr($code, 0, 6));
    if ($userId < 1 || !hash_equals(master_code($userId), $code) || !all_modules_done($pdo, $userId)) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT u.full_name, MAX(c.issued_at) AS issued_at FROM users u
         JOIN certificates c ON c.user_id = u.id
         JOIN modules m ON m.id = c.module_id AND m.is_published = 1
         WHERE u.id = ?'
    );
    $st->execute([$userId]);
    $row = $st->fetch();
    if (!$row || !$row['full_name']) {
        return null;
    }
    $st = $pdo->query('SELECT title FROM modules WHERE is_published = 1 ORDER BY order_no');
    return ['code' => $code, 'issued_at' => $row['issued_at'], 'full_name' => $row['full_name'], 'titles' => $st->fetchAll(PDO::FETCH_COLUMN)];
}
