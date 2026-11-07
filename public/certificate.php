<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$userId = (int)$_SESSION['user_id'];
$code   = strtoupper(trim((string)($_GET['code'] ?? '')));

$st = $pdo->prepare(
    'SELECT c.certificate_code, c.issued_at, m.title, u.full_name
     FROM certificates c
     JOIN modules m ON m.id = c.module_id
     JOIN users u ON u.id = c.user_id
     WHERE c.certificate_code = ? AND c.user_id = ?'
);
$st->execute([$code, $userId]);
$cert = $st->fetch();
if (!$cert) {
    http_response_code(404);
    exit('Certificate not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate | CyberQuest</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="assets/js/theme.js"></script>
</head>
<body class="cert-body">
    <div class="cert">
        <div class="cert-brand">CyberQuest</div>
        <p>This certifies that</p>
        <h1><?= e($cert['full_name']) ?></h1>
        <p>has completed every challenge in the module</p>
        <h2><?= e($cert['title']) ?></h2>
        <p class="cert-meta">Issued <?= e(date('d F Y', strtotime($cert['issued_at']))) ?></p>
        <p class="cert-meta">Certificate ID: <strong><?= e($cert['certificate_code']) ?></strong></p>
        <p class="cert-meta">Verify at verify.php</p>
    </div>
    <p class="cert-actions">
        <button class="btn" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-secondary" href="certificates.php">Back</a>
    </p>
</body>
</html>
