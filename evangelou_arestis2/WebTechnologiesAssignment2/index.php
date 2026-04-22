<?php
session_start();
require_once __DIR__ . "/includes/conn.php";

$sql = "SELECT * FROM tbl_offers";
$result = mysqli_query($conn, $sql);

$offers = [];
$userName = "";
if (isset($_SESSION["user_name"])) {
    $userName = $_SESSION["user_name"];
}

if ($result === false) {
    die("Query failed: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($result)) {
    $offers[] = $row;
}

echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '    <meta charset="UTF-8">';
echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '    <title>UCLan Website</title>';
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
if ($userName !== "") {
    echo '                <li><a href="logout.php">Log Out</a></li>';
} else {
    echo '                <li><a href="login.php">Log In</a></li>';
}
echo '            </ul>';
echo '        </nav>';
echo '        <div class="header-left">';
echo '            <button class="hamburger">&#9776;</button>';
echo '        </div>';
echo '        <div class="header-center">';
echo '            <h1>Welcome to the UCLan Website</h1>';
echo '        </div>';
echo '        <div class="header-right">';
echo '            <a href="index.php">';
echo '                <img class="logo" src="logo_reverse.png" alt="Uclan Logo">';
echo '            </a>';
echo '        </div>';
echo '    </div>';
echo '</header>';
echo '<main>';
if ($userName !== "") {
    echo '    <p class="welcome-message">Welcome back ' . htmlspecialchars($userName) . '</p>';
}
echo '    <div class="video-section">';
echo '        <iframe width="480" height="230" src="https://player.vimeo.com/video/1071072056?h=d4263dcc56"></iframe>';
echo '        <video width="480" height="230" controls>';
echo '            <source src="video.mp4" type="video/mp4">';
echo '        </video>';
echo '    </div>';
echo '    <section class="offers">';
echo '        <h2>Latest Offers</h2>';
if (!empty($offers)) {
    foreach ($offers as $offer) {
        $offerId = htmlspecialchars($offer["offer_id"]);
        $offerTitle = htmlspecialchars($offer["offer_title"]);
        $offerDescription = htmlspecialchars($offer["offer_desc"]);
        echo '        <div class="offer">';
        echo '            <p class="offer-id">Offer ' . $offerId . '</p>';
        echo '            <h3 class="offer-title">' . $offerTitle . '</h3>';
        echo '            <p class="offer-detail">' . $offerDescription . '</p>';
        echo '        </div>';
    }
} else {
    echo '        <p>No offers available.</p>';
}
echo '    </section>';
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
