
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';

redirect_if_logged_in();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token.

    if (!verify_csrf_token()) {

        $error = "Invalid request. Please try again.";

    } else {

        // Collect form data.

        $fullName = trim($_POST["full_name"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");

        $password = $_POST["password"] ?? "";
        $confirmPassword = $_POST["confirm_password"] ?? "";


        // Validate fields.

        if (
            $fullName === "" ||
            $username === "" ||
            $email === "" ||
            $password === "" ||
            $confirmPassword === ""
        ) {

            $error = "All fields are required.";

        } elseif (strlen($fullName) > 100) {

            $error = "Full name is too long.";

        } elseif (!preg_match(
            "/^[a-zA-Z0-9_]{3,30}$/",
            $username
        )) {

            $error = "Username must be 3-30 characters and contain only letters, numbers and underscores.";

        } elseif (
            strlen($email) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {

            $error = "Please enter a valid email address.";

        } elseif (strlen($password) < 8) {

            $error = "Password must contain at least 8 characters.";

        } elseif ($password !== $confirmPassword) {

            $error = "Passwords do not match.";

        } else {

            // Check for existing username or email.

            $check = $pdo->prepare(
                "SELECT id FROM users
                 WHERE username = ? OR email = ?
                 LIMIT 1"
            );

            $check->execute([$username, $email]);

            if ($check->fetch()) {

                $error = "Username or email is already registered.";

            } else {

                // Hash the password.

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                try {

                    // Insert new user.

                    $sql = "INSERT INTO users
                            (username, email, password_hash, full_name)
                            VALUES (?, ?, ?, ?)";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        $username,
                        $email,
                        $passwordHash,
                        $fullName
                    ]);

                    // Automatically log the user in.

                    $userId = $pdo->lastInsertId();

                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $userId;
                    $_SESSION["username"] = $username;
                    $_SESSION["full_name"] = $fullName;
                    $_SESSION["role"] = "student";

                    header("Location: index.php");
                    exit;

                } catch (PDOException $e) {

                    if ($e->getCode() === "23000") {

                        $error = "Username or email is already registered.";

                    } else {

                        error_log($e->getMessage());

                        $error = "Registration failed. Please try again.";

                    }

                }

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

    <title>Register | CyberQuest</title>

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
                Your journey into cybersecurity starts here.
            </p>

            <h2>Create Account</h2>

            <p class="subtitle">
                Join CyberQuest and start learning.
            </p>

            <?php if ($error !== ""): ?>

                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="register.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token()) ?>"
                >

                <div class="form-group">

                    <label for="full_name">Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="100"
                        placeholder="Your full name"
                        value="<?= htmlspecialchars($_POST["full_name"] ?? "") ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="username">Username</label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        maxlength="30"
                        placeholder="Choose a username"
                        value="<?= htmlspecialchars($_POST["username"] ?? "") ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="100"
                        placeholder="you@example.com"
                        value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Enter password again"
                        required
                    >

                </div>

                <button type="submit" class="login-btn">
                    Create Account
                </button>

            </form>

            <p class="register-link">
                Already have an account?
                <a href="login.php">Login</a>
            </p>

            <div class="footer-text">
                CYBERQUEST © 2026
            </div>

        </div>

    </div>

</body>

</html>
