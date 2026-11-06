<?php
// Public page: anyone can check a certificate code.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$code   = strtoupper(trim((string)($_GET['code'] ?? '')));
$result = null;
$checked = $code !== '';

if ($checked && preg_match('/^[A-F0-9]{16}$/', $code)) {
    $st = $pdo->prepare(
        'SELECT c.issued_at, m.title, u.full_name
         FROM certificates c
         JOIN modules m ON m.id = c.module_id
         JOIN users u ON u.id = c.user_id
         WHERE c.certificate_code = ?'
    );
    $st->execute([$code]);
    $result = $st->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate | CyberQuest</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="logo"><span>Cyber</span>Quest</div>
        <h2>Verify a certificate</h2>
        <p class="subtitle">Enter the 16-character certificate ID.</p>

        <form method="GET" action="verify.php">
            <div class="form-group">
                <label for="code">Certificate ID</label>
                <input id="code" name="code" maxlength="16" value="<?= e($code) ?>" autocomplete="off" required>
            </div>
            <button type="submit" class="login-btn">Verify</button>
        </form>

        <?php if ($checked && $result): ?>
            <div class="flash flash-ok verify-result">
                Valid certificate.<br>
                <strong><?= e($result['full_name']) ?></strong> completed
                <strong><?= e($result['title']) ?></strong> on
                <?= e(date('d F Y', strtotime($result['issued_at']))) ?>.
            </div>
        <?php elseif ($checked): ?>
            <div class="error-message">No certificate found for that ID.</div>
        <?php endif; ?>

        <p class="register-link"><a href="index.php">Back to CyberQuest</a></p>
    </div>
</div>
</body>
</html>
