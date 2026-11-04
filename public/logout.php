
<?php

require_once __DIR__ . '/../includes/auth.php';

// Logout must use POST.

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;

}

// Validate CSRF token.

if (!verify_csrf_token()) {

    http_response_code(403);
    exit("Invalid logout request.");

}

// Remove all session variables.

$_SESSION = [];

// Delete the session cookie.

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );

}

// Destroy session.

session_destroy();

// Return to login.

header("Location: login.php");
exit;

?>
