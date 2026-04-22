<?php
session_start();
require_once __DIR__ . "/includes/conn.php";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$email = "";
$error = "";
$success = "";

if (isset($_GET["registered"])) {
    // After registration we land here with a success message instead of logging in automatically.
    $success = "Account created successfully. Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["email"])) {
        $email = trim($_POST["email"]);
    }

    $password = "";
    if (isset($_POST["password"])) {
        $password = $_POST["password"];
    }

    if ($email === "" || $password === "") {
        $error = "Please enter both email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $sql = "SELECT user_id, user_name, user_email, user_pass FROM tbl_users WHERE user_email = ? LIMIT 1";
        $loginStatement = mysqli_prepare($conn, $sql);

        if (!$loginStatement) {
            die("Query preparation failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($loginStatement, "s", $email);
        mysqli_stmt_execute($loginStatement);
        $result = mysqli_stmt_get_result($loginStatement);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($loginStatement);

        // Passwords are stored hashed, so we compare with password_verify rather than plain text.
        if ($user && password_verify($password, $user["user_pass"])) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["user_name"] = $user["user_name"];
            $_SESSION["user_email"] = $user["user_email"];

            header("Location: index.php");
            exit;
        }

        $error = "Invalid email or password.";
    }
}

echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '    <meta charset="UTF-8">';
echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '    <title>Log In</title>';
echo '    <link rel="stylesheet" href="styles.css">';
echo '</head>';
echo '<body>';
echo '<header>';
echo '    <div class="header-section">';
echo '        <nav>';
echo '            <ul class="nav">';
echo '                <li><a href="index.php">Home</a></li>';
echo '                <li><a href="Products.php">Products</a></li>';
echo '                <li><a href="cart.php">Cart</a></li>';
echo '            </ul>';
echo '        </nav>';
echo '        <div class="header-left">';
echo '            <button class="hamburger">&#9776;</button>';
echo '        </div>';
echo '        <div class="header-center">';
echo '            <h1>Log In</h1>';
echo '        </div>';
echo '        <div class="header-right">';
echo '            <a href="index.php">';
echo '                <img class="logo" src="logo_reverse.png" alt="Uclan Logo">';
echo '            </a>';
echo '        </div>';
echo '    </div>';
echo '</header>';
echo '<main>';
echo '    <div class="prod offer auth-box">';
echo '        <h2>Access Your Account</h2>';
if ($success !== '') {
    echo '        <p class="form-success">' . htmlspecialchars($success) . '</p>';
}
if ($error !== '') {
    echo '        <p class="form-feedback">' . htmlspecialchars($error) . '</p>';
}
echo '        <form action="login.php" method="POST" class="login-form">';
echo '            <p>';
echo '                <label for="email">Email:</label><br>';
echo '                <input type="email" id="email" name="email" value="' . htmlspecialchars($email) . '" required>';
echo '            </p>';
echo '            <p>';
echo '                <label for="password">Password:</label><br>';
echo '                <input type="password" id="password" name="password" required>';
echo '            </p>';
echo '            <p>';
echo '                <button type="submit">Log In</button>';
echo '            </p>';
echo '        </form>';
echo '        <p class="auth-link-row"><small>Not already registered? <a href="register.php">Register here</a></small></p>';
echo '    </div>';
echo '</main>';
echo '<footer>';
echo '    <div class="footer-sec2">';
echo '        <h4>Contact Us</h4>';
echo '        <p><a href="https://maps.app.goo.gl/TbRMAgcJkC2mjEpm7">12 – 14 University Avenue Pyla, 7080 Larnaka, Cyprus</a></p>';
echo '        <p><a href="mailto:info@uclancyprus.ac.cy">Email: info@uclancyprus.ac.cy</a></p>';
echo '        <p><a href="tel:+357246940000">Tel: +357 24694000</a></p>';
echo '    </div>';
echo '    <div class="footer-sec3">';
echo '        <p>&copy; 2025 Uclan WebSite. All rights reserved.</p>';
echo '    </div>';
echo '</footer>';
echo '<script src="script.js?v=20260418"></script>';
echo '</body>';
echo '</html>';
