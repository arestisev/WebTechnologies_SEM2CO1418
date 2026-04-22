<?php
function getCartCookieData()
{
    $cart = [];

    if (isset($_COOKIE["cart"])) {
        // The cart cookie stores a simple product_id => quantity map as JSON.
        $decoded = json_decode($_COOKIE["cart"], true);

        if (is_array($decoded)) {
            foreach ($decoded as $productId => $quantity) {
                $cleanProductId = (int) $productId;
                $cleanQuantity = (int) $quantity;

                if ($cleanProductId > 0 && $cleanQuantity > 0) {
                    $cart[$cleanProductId] = $cleanQuantity;
                }
            }
        }
    }

    return $cart;
}

function writeCartCookieData($cart)
{
    $cleanCart = [];

    foreach ($cart as $productId => $quantity) {
        $cleanProductId = (int) $productId;
        $cleanQuantity = (int) $quantity;

        if ($cleanProductId > 0 && $cleanQuantity > 0) {
            $cleanCart[$cleanProductId] = $cleanQuantity;
        }
    }

    $cookieValue = json_encode($cleanCart);

    // Keep the cookie and the current request in sync after every cart change.
    setcookie("cart", $cookieValue, time() + (60 * 60 * 24 * 7), "/");
    $_COOKIE["cart"] = $cookieValue;
}

function addProductToCartCookie($productId, $quantity)
{
    $cart = getCartCookieData();
    $cleanProductId = (int) $productId;
    $cleanQuantity = (int) $quantity;

    if ($cleanProductId <= 0 || $cleanQuantity <= 0) {
        return;
    }

    if (isset($cart[$cleanProductId])) {
        $cart[$cleanProductId] += $cleanQuantity;
    } else {
        $cart[$cleanProductId] = $cleanQuantity;
    }

    writeCartCookieData($cart);
}

function removeProductFromCartCookie($productId)
{
    $cart = getCartCookieData();
    $cleanProductId = (int) $productId;

    if (isset($cart[$cleanProductId])) {
        unset($cart[$cleanProductId]);
        writeCartCookieData($cart);
    }
}

function clearCartCookieData()
{
    setcookie("cart", "", time() - 3600, "/");
    unset($_COOKIE["cart"]);
}

function addToCart($conn, $userIsLoggedIn)
{
    header("Content-Type: text/plain; charset=UTF-8");

    if (!$userIsLoggedIn) {
        echo "login_required";
        exit;
    }

    $productId = 0;
    if (isset($_POST["product_id"])) {
        $productId = (int) $_POST["product_id"];
    }

    $quantity = 1;
    if (isset($_POST["quantity"])) {
        $quantity = (int) $_POST["quantity"];
    }

    if ($productId <= 0 || $quantity <= 0) {
        echo "invalid_quantity";
        exit;
    }

    $stock = mysqli_prepare($conn, "SELECT product_stock FROM tbl_products WHERE product_id = ? LIMIT 1");

    if (!$stock) {
        echo "error";
        exit;
    }

    mysqli_stmt_bind_param($stock, "i", $productId);
    mysqli_stmt_execute($stock);
    $stockResult = mysqli_stmt_get_result($stock);
    $stockRow = mysqli_fetch_assoc($stockResult);
    mysqli_stmt_close($stock);

    if (!$stockRow || stripos($stockRow["product_stock"], "out") !== false) {
        echo "out_of_stock";
        exit;
    }

    addProductToCartCookie($productId, $quantity);
    echo "added";
    exit;
}
