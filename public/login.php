<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

redirect_if_logged_in();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $error = "Invalid request. Please try again.";

    } else {

        $identity = trim($_POST["identity"] ?? "");
        $password = $_POST["password"] ?? "";

        if ($identity === "" || $password === "") {

            $error = "Please fill in all fields.";

        } else {

            $sql = "SELECT id, username, email, full_name,
                           password_hash, role
                    FROM users
                    WHERE username = :identity
                       OR email = :identity
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                "identity" => $identity
            ]);

            $user = $stmt->fetch();

            if (
                $user &&
                password_verify(
                    $password,
                    $user["password_hash"]
                )
            ) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];

                // Redirect to dashboard.

                header("Location: index.php");
                exit;

            } else {

                $error = "Invalid username/email or password.";

            }

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | CyberQuest</title>

    <link rel="stylesheet"
          href="assets/css/styles.css">

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="assets/js/theme.js"></script>
</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                <span>Cyber</span>Quest
            </div>

            <p class="tagline">
                Learn. Explore. Exploit. Secure.
            </p>

            <h2>Welcome Back</h2>

            <p class="subtitle">
                Sign in to continue your cybersecurity journey.
            </p>

            <?php if ($error !== ""): ?>

                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="login.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token()) ?>"
                >

                <div class="form-group">

                    <label for="identity">
                        Username or Email
                    </label>

                    <input
                        type="text"
                        id="identity"
                        name="identity"
                        placeholder="Enter your username or email"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>

                <button type="submit" class="login-btn">
                    Login to CyberQuest
                </button>

            </form>

            <p class="register-link">
                Don't have an account?
                <a href="register.php">Create one</a>
            </p>

            <div class="footer-text">
                CYBERQUEST © 2026
            </div>

        </div>

    </div>

</body>

</html>
