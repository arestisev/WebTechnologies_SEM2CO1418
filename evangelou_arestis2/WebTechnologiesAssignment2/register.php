<?php
session_start();
require_once __DIR__ . "/includes/conn.php";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$name = "";
$email = "";
$address = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["name"])) {
        $name = trim($_POST["name"]);
    }

    if (isset($_POST["email"])) {
        $email = trim($_POST["email"]);
    }

    if (isset($_POST["address"])) {
        $address = trim($_POST["address"]);
    }

    $password = "";
    if (isset($_POST["password"])) {
        $password = $_POST["password"];
    }

    if ($name === "" || $email === "" || $address === "" || $password === "") {
        $error = "Please complete all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must include at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must include at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must include at least one number.";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must include at least one symbol.";
    } else {
        $checkSql = "SELECT user_id FROM tbl_users WHERE user_email = ? LIMIT 1";
        $checkStmt = mysqli_prepare($conn, $checkSql);

        if (!$checkStmt) {
            die("Query preparation failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            // We keep email unique so login stays predictable and each account is clearly one person.
            $error = "An account with that email already exists.";
        } else {
            // New passwords go into the database hashed, never as raw text.
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $insertSql = "INSERT INTO tbl_users (user_name, user_email, user_pass, user_address) VALUES (?, ?, ?, ?)";
            $insertStmt = mysqli_prepare($conn, $insertSql);

            if (!$insertStmt) {
                die("Query preparation failed: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($insertStmt, "ssss", $name, $email, $hashedPassword, $address);

            if (mysqli_stmt_execute($insertStmt)) {
                header("Location: login.php?registered=1");
                exit;
            }

            $error = "Unable to create account.";
            mysqli_stmt_close($insertStmt);
        }

        mysqli_stmt_close($checkStmt);
    }
}

echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '    <meta charset="UTF-8">';
echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '    <title>Register</title>';
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
echo '            <h1>Register</h1>';
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
echo '        <h2>Create Your Account</h2>';
if ($error !== '') {
    echo '        <p class="form-feedback">' . htmlspecialchars($error) . '</p>';
}
echo '        <form action="register.php" method="POST" class="login-form">';
echo '            <p>';
echo '                <label for="name">Name:</label><br>';
echo '                <input type="text" id="name" name="name" value="' . htmlspecialchars($name) . '" required>';
echo '            </p>';
echo '            <p>';
echo '                <label for="email">Email:</label><br>';
echo '                <input type="email" id="email" name="email" value="' . htmlspecialchars($email) . '" required>';
echo '            </p>';
echo '            <p>';
echo '                <label for="address">Address:</label><br>';
echo '                <input type="text" id="address" name="address" value="' . htmlspecialchars($address) . '" required>';
echo '            </p>';
echo '            <p>';
echo '                <label for="password">Password:</label><br>';
echo '                <input type="password" id="password" name="password" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters, including uppercase, lowercase, a number, and a symbol." required>';
echo '            </p>';
echo '            <p>';
echo '                <button type="submit">Register</button>';
echo '            </p>';
echo '        </form>';
echo '        <p class="auth-link-row"><small>Already registered? <a href="login.php">Log In here</a></small></p>';
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
