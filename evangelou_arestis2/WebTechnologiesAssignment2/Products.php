<?php
session_start();
require_once __DIR__ . "/includes/conn.php";
require_once __DIR__ . "/includes/cart_cookie.php";

$userName = "";
if (isset($_SESSION["user_name"])) {
    $userName = $_SESSION["user_name"];
}

$userIsLoggedIn = isset($_SESSION["user_id"]);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_cart"])) {
    addToCart($conn, $userIsLoggedIn);
}

$search = "";
if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search !== "") {
    $sql = "SELECT * FROM tbl_products WHERE product_title LIKE ?";
    $productSearchStatement = mysqli_prepare($conn, $sql);

    if ($productSearchStatement === false) {
        die("Query failed: " . mysqli_error($conn));
    }

    $searchTerm = "%" . $search . "%";
    mysqli_stmt_bind_param($productSearchStatement, "s", $searchTerm);
    mysqli_stmt_execute($productSearchStatement);
    $result = mysqli_stmt_get_result($productSearchStatement);
    mysqli_stmt_close($productSearchStatement);
} else {
    $sql = "SELECT * FROM tbl_products";
    $result = mysqli_query($conn, $sql);
}

if ($result === false) {
    die("Query failed: " . mysqli_error($conn));
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
echo '            <h1>The Products Page</h1>';
echo '        </div>';
echo '        <div class="header-right">';
echo '            <a href="index.php">';
echo '                <img class="logo" src="logo_reverse.png" alt="Uclan Logo">';
echo '            </a>';
echo '        </div>';
echo '    </div>';
echo '</header>';
echo '<div class="product-tools">';
echo '    <form action="Products.php" method="GET" class="search-form">';
echo '        <label for="search">Search by title:</label>';
echo '        <input type="text" id="search" name="search" value="' . htmlspecialchars($search) . '" placeholder="Enter product title">';
echo '        <button type="submit">Search</button>';
if ($search !== "") {
    echo '        <a href="Products.php" class="clear-search">Clear</a>';
}
echo '    </form>';
echo '    <div class="stock-filter">';
echo '        <label for="filter-stock">Filter by stock:</label>';
echo '        <select id="filter-stock">';
echo '            <option value="all">All</option>';
echo '            <option value="in">In stock</option>';
echo '            <option value="out">Out of stock</option>';
echo '        </select>';
echo '    </div>';
echo '</div>';
echo '<main class="products">';
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id = htmlspecialchars($row['product_id']);
        $title = htmlspecialchars($row['product_title']);
        $desc = htmlspecialchars($row['product_desc']);
        $price = htmlspecialchars($row['product_price']);
        $image = 'tshirts/tshirt' . $id . '.jpg';
        $stock = htmlspecialchars($row['product_stock']);
        $stockLower = strtolower($stock);

        // The stock text drives both the filter and the hover colour.
        $isOutOfStock = false;

        if (strpos($stockLower, 'out') !== false) {
            $dataStock = 'out';
            $cardClass = 'product no-stock';
            $isOutOfStock = true;
        } elseif (strpos($stockLower, 'last') !== false || strpos($stockLower, 'low') !== false) {
            $dataStock = 'in';
            $cardClass = 'product last';
        } else {
            $dataStock = 'in';
            $cardClass = 'product good';
        }

        $buttonType = 'submit';
        $buttonClass = 'add';
        $buttonExtra = '';

        if ($isOutOfStock) {
            $buttonType = 'button';
            $buttonClass = 'add add-out-of-stock';
            $buttonExtra = " onmouseenter=\"this.textContent='Out Of Stock!'\" onmouseleave=\"this.textContent='Add to Cart'\" aria-disabled=\"true\"";
        }

        echo '<div id="product-' . $id . '" class="' . $cardClass . '" data-id="' . $id . '" data-stock="' . $dataStock . '">';
        echo '    <img src="' . $image . '" alt="' . $title . '" class="p-images">';
        echo '    <h2>' . $title . '</h2>';
        echo '    <p>' . $desc . '</p>';
        echo '    <p>Price: £' . $price . '</p>';
        echo '    <p>Status: ' . $stock . '</p>';
        echo '    <p><a class="read-more-link" href="Item.php?id=' . $id . '">Read more</a></p>';
        echo '    <form action="Products.php" method="POST" class="add-to-cart-form" onsubmit="return handleAddToCartForm(event, this);">';
        echo '        <input type="hidden" name="product_id" value="' . $id . '">';
        echo '        <label for="stock' . $id . '">Quantity:</label>';
        echo '        <input type="number" id="stock' . $id . '" name="quantity" min="1" value="1">';
        echo '        <button type="' . $buttonType . '" name="add_to_cart" class="' . $buttonClass . '"' . $buttonExtra . '>Add to Cart</button>';
        echo '    </form>';
        echo '</div>';
    }
} else {
    echo '    <p>No products found.</p>';
}
echo '</main>';
echo '<footer>';
echo '    <div class="footer-sec1">';
echo "        <button onclick=\"window.location.href='Products.php'\">&#8593;</button>";
echo '    </div>';
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
