<?php
session_start();
require_once __DIR__ . "/includes/conn.php";
require_once __DIR__ . "/includes/cart_cookie.php";

function buildCartItemsFromCookie($conn, $cart)
{
    $cartItems = [];
    $totalAmount = 0;

    if (!empty($cart)) {
        // The cookie only stores ids and quantities, so we load the live product data here.
        $productIds = array_keys($cart);
        $cleanIds = [];

        foreach ($productIds as $productId) {
            $cleanIds[] = (int) $productId;
        }

        if (!empty($cleanIds)) {
            $idList = implode(",", $cleanIds);
            $productsSql = "SELECT product_id, product_title, product_price FROM tbl_products WHERE product_id IN (" . $idList . ")";
            $productsResult = mysqli_query($conn, $productsSql);

            if ($productsResult) {
                while ($productRow = mysqli_fetch_assoc($productsResult)) {
                    $productId = (int) $productRow["product_id"];
                    $quantity = 0;

                    if (isset($cart[$productId])) {
                        $quantity = (int) $cart[$productId];
                    }

                    if ($quantity > 0) {
                        $price = (float) $productRow["product_price"];
                        $subtotal = $price * $quantity;
                        $totalAmount += $subtotal;

                        $cartItems[] = [
                            "product_id" => $productId,
                            "product_title" => $productRow["product_title"],
                            "product_price" => $price,
                            "quantity" => $quantity,
                            "subtotal" => $subtotal,
                            "image" => "tshirts/tshirt" . $productId . ".jpg"
                        ];
                    }
                }
            }
        }
    }

    return [
        "items" => $cartItems,
        "total" => $totalAmount
    ];
}

$userName = "";
if (isset($_SESSION["user_name"])) {
    $userName = $_SESSION["user_name"];
}

$userId = 0;
if (isset($_SESSION["user_id"])) {
    $userId = (int) $_SESSION["user_id"];
}

$offerMessage = "";
$offerInputValue = "";
$checkoutSuccess = "";
$checkoutError = "";

if (isset($_SESSION["cart_offer_code"])) {
    $offerInputValue = $_SESSION["cart_offer_code"];
}

if (isset($_SESSION["checkout_success"])) {
    $checkoutSuccess = $_SESSION["checkout_success"];
    unset($_SESSION["checkout_success"]);
}

if (isset($_SESSION["checkout_error"])) {
    $checkoutError = $_SESSION["checkout_error"];
    unset($_SESSION["checkout_error"]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["remove_item"])) {
        $removeProductId = 0;
        if (isset($_POST["product_id"])) {
            $removeProductId = (int) $_POST["product_id"];
        }

        removeProductFromCartCookie($removeProductId);
        header("Location: cart.php");
        exit;
    }

    if (isset($_POST["clear_cart"])) {
        clearCartCookieData();
        unset($_SESSION["cart_discount"]);
        unset($_SESSION["cart_offer_code"]);
        header("Location: cart.php");
        exit;
    }

    if (isset($_POST["apply_offer"])) {
        if (isset($_POST["offer_code"])) {
            $offerInputValue = strtoupper(trim($_POST["offer_code"]));
        }

        if ($offerInputValue === "GRAD25") {
            $offerTitle = "Grad Promo Code";
            // Check the offer against tbl_offers on the server instead of trusting the browser.
            $offerStmt = mysqli_prepare($conn, "SELECT offer_id FROM tbl_offers WHERE offer_title = ? LIMIT 1");

            if ($offerStmt) {
                mysqli_stmt_bind_param($offerStmt, "s", $offerTitle);
                mysqli_stmt_execute($offerStmt);
                $offerResult = mysqli_stmt_get_result($offerStmt);

                if (mysqli_num_rows($offerResult) > 0) {
                    $_SESSION["cart_discount"] = 0.25;
                    $_SESSION["cart_offer_code"] = "GRAD25";
                    $offerMessage = "Your GRAD25 offer gives you 25% off.";
                } else {
                    unset($_SESSION["cart_discount"]);
                    unset($_SESSION["cart_offer_code"]);
                    $offerMessage = "The graduate offer is not currently available.";
                }

                mysqli_stmt_close($offerStmt);
            } else {
                $offerMessage = "Unable to verify the offer right now.";
            }
        } else {
            unset($_SESSION["cart_discount"]);
            unset($_SESSION["cart_offer_code"]);
            $offerMessage = "No such code";
        }
    }

    if (isset($_POST["checkout"])) {
        if ($userId <= 0) {
            header("Location: login.php");
            exit;
        }

        $checkoutCart = getCartCookieData();
        $checkoutCartData = buildCartItemsFromCookie($conn, $checkoutCart);
        $checkoutItems = $checkoutCartData["items"];

        if (empty($checkoutItems)) {
            $_SESSION["checkout_error"] = "Your cart is empty.";
            header("Location: cart.php");
            exit;
        }

        $cartSummaryParts = [];

        foreach ($checkoutItems as $checkoutItem) {
            $cartSummaryParts[] = $checkoutItem["product_id"] . " x" . $checkoutItem["quantity"];
        }

        $cartSummaryText = implode("; ", $cartSummaryParts);
        // tbl_orders only stores the user id and a compact product list; the timestamp fills itself.
        $insertSql = "INSERT INTO tbl_orders (user_id, product_ids) VALUES (?, ?)";
        $orderStmt = mysqli_prepare($conn, $insertSql);

        if (!$orderStmt) {
            $_SESSION["checkout_error"] = "The orders table needs the expected order columns before checkout can work.";
        } else {
            mysqli_stmt_bind_param($orderStmt, "is", $userId, $cartSummaryText);

            if (mysqli_stmt_execute($orderStmt)) {
                // Only clear the cart after the order row is saved successfully.
                clearCartCookieData();
                unset($_SESSION["cart_discount"]);
                unset($_SESSION["cart_offer_code"]);
                $_SESSION["checkout_success"] = "Thank you for your custom. Your order has been placed.";
            } else {
                $_SESSION["checkout_error"] = "Unable to save your order.";
            }

            mysqli_stmt_close($orderStmt);
        }

        header("Location: cart.php");
        exit;
    }
}

$cart = getCartCookieData();
$cartData = buildCartItemsFromCookie($conn, $cart);
$cartItems = $cartData["items"];
$totalAmount = $cartData["total"];

$discountRate = 0;
if (isset($_SESSION["cart_discount"])) {
    $discountRate = (float) $_SESSION["cart_discount"];
}

$discountAmount = 0;
if ($discountRate > 0) {
    $discountAmount = $totalAmount * $discountRate;
}

$finalTotal = $totalAmount - $discountAmount;
if ($finalTotal < 0) {
    $finalTotal = 0;
}

$cartOfferCode = "";
if (isset($_SESSION["cart_offer_code"])) {
    $cartOfferCode = htmlspecialchars($_SESSION["cart_offer_code"]);
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
echo '            <h1>Shopping Cart</h1>';
echo '        </div>';
echo '        <div class="header-right">';
echo '            <a href="index.php">';
echo '                <img class="logo" src="logo_reverse.png" alt="Uclan Logo">';
echo '            </a>';
echo '        </div>';
echo '    </div>';
echo '</header>';
echo '<main>';
if ($checkoutSuccess !== '') {
    echo '    <div class="offer cart-summary">';
    echo '        <h2 class="offer-title">Order Complete</h2>';
    echo '        <p class="form-success">' . htmlspecialchars($checkoutSuccess) . '</p>';
    echo '    </div>';
}
if ($checkoutError !== '') {
    echo '    <div class="offer cart-summary">';
    echo '        <h2 class="offer-title">Checkout Error</h2>';
    echo '        <p class="form-feedback">' . htmlspecialchars($checkoutError) . '</p>';
    echo '    </div>';
}
if (empty($cartItems)) {
    echo '    <div class="offer cart-summary">';
    echo '        <h2 class="offer-title">Your Basket</h2>';
    echo '        <p class="offer-detail">Your cart is empty.</p>';
    echo '    </div>';
} else {
    foreach ($cartItems as $item) {
        $itemImage = htmlspecialchars($item['image']);
        $itemTitle = htmlspecialchars($item['product_title']);
        $itemQuantity = (int) $item['quantity'];
        $itemPrice = number_format($item['product_price'], 2);
        $itemSubtotal = number_format($item['subtotal'], 2);
        $itemId = (int) $item['product_id'];

        echo '    <div class="offer cart-item-card">';
        echo '        <img src="' . $itemImage . '" alt="' . $itemTitle . '" class="cart-image" style="width: 160px; max-width: 160px; height: auto;">';
        echo '        <h3 class="offer-title">' . $itemTitle . '</h3>';
        echo '        <p class="offer-detail">Quantity: ' . $itemQuantity . '</p>';
        echo '        <p class="offer-detail">Price: £' . $itemPrice . '</p>';
        echo '        <p class="offer-detail">Subtotal: £' . $itemSubtotal . '</p>';
        echo '        <form action="cart.php" method="POST" class="cart-inline-form">';
        echo '            <input type="hidden" name="product_id" value="' . $itemId . '">';
        echo '            <button type="submit" name="remove_item">Remove</button>';
        echo '        </form>';
        echo '    </div>';
    }

    echo '    <div class="offer cart-summary">';
    echo '        <h2 class="offer-title">Discount Code</h2>';
    if ($offerMessage !== '') {
        echo '        <p class="form-success">' . htmlspecialchars($offerMessage) . '</p>';
    }
    echo '        <form action="cart.php" method="POST" class="cart-offer-form">';
    echo '            <label for="offer_code">Discount Code:</label><br>';
    echo '            <input type="text" id="offer_code" name="offer_code" value="' . htmlspecialchars($offerInputValue) . '">';
    echo '            <button type="submit" name="apply_offer">Apply</button>';
    echo '        </form>';
    echo '        <form action="cart.php" method="POST" class="cart-inline-form">';
    echo '            <button type="submit" name="clear_cart">Clear Cart</button>';
    echo '        </form>';
    echo '    </div>';

    echo '    <div class="offer cart-summary">';
    echo '        <h2 class="offer-title">Order Summary</h2>';
    echo '        <p class="offer-detail">Subtotal: £' . number_format($totalAmount, 2) . '</p>';
    if ($discountRate > 0) {
        echo '        <p class="offer-detail">Discount (' . $cartOfferCode . '): -£' . number_format($discountAmount, 2) . '</p>';
    }
    echo '        <p class="offer-detail"><strong>Total: £' . number_format($finalTotal, 2) . '</strong></p>';
    if ($userId > 0) {
        echo '        <form action="cart.php" method="POST" class="cart-inline-form">';
        echo '            <button type="submit" name="checkout">Checkout</button>';
        echo '        </form>';
    } else {
        echo '        <p class="offer-detail">Please <a href="login.php">log in</a> to checkout.</p>';
    }
    echo '    </div>';
}
echo '</main>';
echo '<footer>';
echo '    <div class="footer-sec1">';
echo "        <button onclick=\"window.location.href='cart.php'\">&#8593;</button>";
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
