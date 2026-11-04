
<?php

// Start a secure PHP session.

if (session_status() === PHP_SESSION_NONE) {

    ini_set("session.use_strict_mode", "1");

    $isHttps = (
        !empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off"
    );

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}


// Check whether a user is logged in.

function is_logged_in(): bool {

    return isset($_SESSION["user_id"]);

}


// Redirect unauthenticated users.

function require_login(): void {

    if (!is_logged_in()) {

        header("Location: login.php");
        exit;

    }

}


// Redirect already authenticated users.

function redirect_if_logged_in(): void {

    if (is_logged_in()) {

        header("Location: index.php");
        exit;

    }

}


// Generate a CSRF protection token.

function csrf_token(): string {

    if (empty($_SESSION["csrf_token"])) {

        $_SESSION["csrf_token"] = bin2hex(
            random_bytes(32)
        );

    }

    return $_SESSION["csrf_token"];

}


// Verify submitted CSRF token.

function verify_csrf_token(): bool {

    return (
        isset($_SESSION["csrf_token"]) &&
        isset($_POST["csrf_token"]) &&
        hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"]
        )
    );

}

?>
