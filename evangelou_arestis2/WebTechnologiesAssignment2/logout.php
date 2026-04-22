<?php
session_start();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    // Clear the session cookie too, otherwise some browsers keep the old session around longer than we want.
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

session_destroy();

header("Location: index.php");
exit;
