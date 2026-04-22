<?php
session_start();
require_once __DIR__ . "/includes/conn.php";
require_once __DIR__ . "/includes/cart_cookie.php";

$userName = "";
if (isset($_SESSION["user_name"])) {
    $userName = $_SESSION["user_name"];
}

$userId = 0;
if (isset($_SESSION["user_id"])) {
    $userId = (int) $_SESSION["user_id"];
}

$userIsLoggedIn = isset($_SESSION["user_id"]);

if (!isset($_GET['id'])) {
    die("No product selected.");
}

$id = (int) $_GET['id'];

$sql = "SELECT * FROM tbl_products WHERE product_id = ?";
$productStmt = mysqli_prepare($conn, $sql);

if (!$productStmt) {
    die("Query preparation failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($productStmt, "i", $id);
mysqli_stmt_execute($productStmt);
$productResult = mysqli_stmt_get_result($productStmt);
$product = mysqli_fetch_assoc($productResult);
mysqli_stmt_close($productStmt);

if (!$product) {
    die("Product not found.");
}

$stockLower = strtolower((string) $product["product_stock"]);
$isOutOfStock = strpos($stockLower, "out") !== false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_cart"])) {
    addToCart($conn, $userIsLoggedIn);
}

$reviewError = "";
$reviewSuccess = "";
$reviewTitleValue = "";
$reviewTextValue = "";
$reviewRatingValue = "";
$reviewsAvailable = true;
$reviews = [];
$averageRating = 0;
$reviewCount = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_review"])) {
    if (!isset($_SESSION["user_id"])) {
        $reviewError = "Please log in to post a review.";
    } else {
        if (isset($_POST["review_title"])) {
            $reviewTitleValue = trim($_POST["review_title"]);
        }

        if (isset($_POST["review_text"])) {
            $reviewTextValue = trim($_POST["review_text"]);
        }

        if (isset($_POST["review_rating"])) {
            $reviewRatingValue = trim($_POST["review_rating"]);
        }

        $ratingNumber = (int) $reviewRatingValue;

        if ($reviewTitleValue === "" || $reviewTextValue === "" || $reviewRatingValue === "") {
            $reviewError = "Please complete all review fields.";
        } elseif ($ratingNumber < 1 || $ratingNumber > 5) {
            $reviewError = "Please choose a rating from 1 to 5.";
        } else {
            // Reviews are tied to both the selected product and the logged-in user.
            $insertSql = "INSERT INTO tbl_reviews (product_id, user_id, review_title, review_desc, review_rating) VALUES (?, ?, ?, ?, ?)";
            $insertStmt = mysqli_prepare($conn, $insertSql);

            if (!$insertStmt) {
                $reviewError = "Review form needs the expected tbl_reviews columns.";
            } else {
                mysqli_stmt_bind_param($insertStmt, "iissi", $id, $userId, $reviewTitleValue, $reviewTextValue, $ratingNumber);

                if (mysqli_stmt_execute($insertStmt)) {
                    $reviewSuccess = "Your review has been posted.";
                    $reviewTitleValue = "";
                    $reviewTextValue = "";
                    $reviewRatingValue = "";
                } else {
                    $reviewError = "Unable to save your review right now.";
                }

                mysqli_stmt_close($insertStmt);
            }
        }
    }
}

// Load the summary separately so we can show the score even before listing each review.
$averageStmt = mysqli_prepare($conn, "SELECT AVG(review_rating) AS average_rating, COUNT(*) AS review_count FROM tbl_reviews WHERE product_id = ?");

if ($averageStmt) {
    mysqli_stmt_bind_param($averageStmt, "i", $id);
    mysqli_stmt_execute($averageStmt);
    $averageResult = mysqli_stmt_get_result($averageStmt);
    $averageRow = mysqli_fetch_assoc($averageResult);
    mysqli_stmt_close($averageStmt);

    if ($averageRow) {
        $reviewCount = (int) $averageRow["review_count"];

        if ($reviewCount > 0) {
            $averageRating = (float) $averageRow["average_rating"];
        }
    }
} else {
    $reviewsAvailable = false;
}

// Join to tbl_users so each review can display the reviewer name.
$reviewsStmt = mysqli_prepare(
    $conn,
    "SELECT r.review_title, r.review_desc, r.review_rating, u.user_name
     FROM tbl_reviews r
     LEFT JOIN tbl_users u ON r.user_id = u.user_id
     WHERE r.product_id = ?
     ORDER BY r.review_id DESC"
);

if ($reviewsStmt) {
    mysqli_stmt_bind_param($reviewsStmt, "i", $id);
    mysqli_stmt_execute($reviewsStmt);
    $reviewsResult = mysqli_stmt_get_result($reviewsStmt);

    while ($reviewRow = mysqli_fetch_assoc($reviewsResult)) {
        $reviews[] = $reviewRow;
    }

    mysqli_stmt_close($reviewsStmt);
} else {
    $reviewsAvailable = false;
}

$title = htmlspecialchars($product['product_title']);
$desc = htmlspecialchars($product['product_desc']);
$price = htmlspecialchars($product['product_price']);
$image = 'tshirts/tshirt' . $id . '.jpg';
$stock = htmlspecialchars($product['product_stock']);

$buttonType = 'submit';
$buttonClass = 'add';
$buttonExtra = '';

if ($isOutOfStock) {
    $buttonType = 'button';
    $buttonClass = 'add add-out-of-stock';
    $buttonExtra = " onmouseenter=\"this.textContent='Out Of Stock!'\" onmouseleave=\"this.textContent='Add to Cart'\" aria-disabled=\"true\"";
}

echo '<!DOCTYPE html>';
echo '<html lang="en">';
echo '<head>';
echo '    <meta charset="UTF-8">';
echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '    <title>Product</title>';
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
echo '            <h1>Product Details</h1>';
echo '        </div>';
echo '        <div class="header-right">';
echo '            <a href="index.php">';
echo '                <img class="logo" src="logo_reverse.png" alt="Uclan Logo">';
echo '            </a>';
echo '        </div>';
echo '    </div>';
echo '</header>';
echo '<div id="product-detail" class="prod">';
echo '    <img src="' . $image . '" alt="' . $title . '" class="p-images">';
echo '    <h2>' . $title . '</h2>';
echo '    <p>' . $desc . '</p>';
echo '    <p>Price: £' . $price . '</p>';
echo '    <p>Status: ' . $stock . '</p>';
echo '    <form action="Item.php?id=' . $id . '" method="POST" class="add-to-cart-form" onsubmit="return handleAddToCartForm(event, this);">';
echo '        <input type="hidden" name="product_id" value="' . $id . '">';
echo '        <label for="stock' . $id . '">Quantity:</label>';
echo '        <input type="number" id="stock' . $id . '" name="quantity" min="1" value="1">';
echo '        <button type="' . $buttonType . '" name="add_to_cart" class="' . $buttonClass . '"' . $buttonExtra . '>Add to Cart</button>';
echo '    </form>';
echo '    <a href="Products.php">&#8594; Products</a>';
echo '</div>';
echo '<section class="reviews">';
echo '    <div class="offer review-summary">';
echo '        <h2 class="offer-title">Customer Reviews</h2>';
if ($reviewsAvailable) {
    if ($reviewCount > 0) {
        echo '        <p class="offer-detail">Average rating: ' . number_format($averageRating, 1) . '/5 from ' . $reviewCount . ' review(s).</p>';
    } else {
        echo '        <p class="offer-detail">No reviews yet for this product.</p>';
    }
} else {
    echo '        <p class="offer-detail">Review information is not available right now.</p>';
}
echo '    </div>';
if (!empty($reviews)) {
    foreach ($reviews as $review) {
        $reviewerName = 'Verified user';
        if (isset($review['user_name']) && trim((string) $review['user_name']) !== '') {
            $reviewerName = $review['user_name'];
        }

        echo '    <div class="offer review-card">';
        echo '        <p class="offer-id">' . htmlspecialchars($reviewerName) . '</p>';
        echo '        <h3 class="offer-title">' . htmlspecialchars($review['review_title']) . '</h3>';
        echo '        <p class="offer-detail">Rating: ' . htmlspecialchars($review['review_rating']) . '/5</p>';
        echo '        <p class="offer-detail">' . htmlspecialchars($review['review_desc']) . '</p>';
        echo '    </div>';
    }
}
echo '    <div class="offer review-form-box">';
echo '        <h3 class="offer-title">Post a Review</h3>';
if ($reviewSuccess !== '') {
    echo '        <p class="form-success">' . htmlspecialchars($reviewSuccess) . '</p>';
}
if ($reviewError !== '') {
    echo '        <p class="form-feedback">' . htmlspecialchars($reviewError) . '</p>';
}
if ($userName === '') {
    echo '        <p class="offer-detail">Please <a href="login.php">log in</a> to post a review.</p>';
} elseif (!$reviewsAvailable) {
    echo '        <p class="offer-detail">The review form is not available for this product right now.</p>';
} else {
    echo '        <form action="Item.php?id=' . $id . '" method="POST" class="review-form">';
    echo '            <p>';
    echo '                <label for="review_title">Review Title:</label><br>';
    echo '                <input type="text" id="review_title" name="review_title" value="' . htmlspecialchars($reviewTitleValue) . '" required>';
    echo '            </p>';
    echo '            <p>';
    echo '                <label for="review_text">Review Description:</label><br>';
    echo '                <textarea id="review_text" name="review_text" rows="5" required>' . htmlspecialchars($reviewTextValue) . '</textarea>';
    echo '            </p>';
    echo '            <p>';
    echo '                <label for="review_rating">Rating:</label><br>';
    echo '                <select id="review_rating" name="review_rating" required>';
    echo '                    <option value="">Choose a rating</option>';
    echo '                    <option value="1"' . ($reviewRatingValue === '1' ? ' selected' : '') . '>1</option>';
    echo '                    <option value="2"' . ($reviewRatingValue === '2' ? ' selected' : '') . '>2</option>';
    echo '                    <option value="3"' . ($reviewRatingValue === '3' ? ' selected' : '') . '>3</option>';
    echo '                    <option value="4"' . ($reviewRatingValue === '4' ? ' selected' : '') . '>4</option>';
    echo '                    <option value="5"' . ($reviewRatingValue === '5' ? ' selected' : '') . '>5</option>';
    echo '                </select>';
    echo '            </p>';
    echo '            <p>';
    echo '                <button type="submit" name="submit_review">Post Review</button>';
    echo '            </p>';
    echo '        </form>';
}
echo '    </div>';
echo '</section>';
echo '<footer>';
echo '    <div class="footer-sec2">';
echo '        <h4>Contact Us</h4>';
echo '        <p><a href="https://maps.app.goo.gl/TbRMAgcJkC2mjEpm7">12 – 14 University Avenue Pyla, 7080 Larnaka, Cyprus</a></p>';
echo '        <p><a href="mailto:info@uclancyprus.ac.cy">Email: info@uclancyprus.ac.cy</a></p>';
echo '        <p><a href="tel:+357246940000">Tel: +357 24694000</a></p>';
echo '    </div>';
echo '    <div class="footer-sec1">';
echo '        <p>&copy; 2025 Uclan WebSite. All rights reserved.</p>';
echo '    </div>';
echo '</footer>';
echo '<script src="script.js?v=20260418"></script>';
echo '</body>';
echo '</html>';
