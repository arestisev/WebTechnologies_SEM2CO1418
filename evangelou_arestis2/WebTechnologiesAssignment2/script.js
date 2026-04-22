
function handleAddToCartForm(event, form) {
    event.preventDefault();

    (async () => {
        try {
            const formData = new FormData(form);
            formData.append("add_to_cart", "1");

            const response = await fetch(form.action, {
                method: "POST",
                body: formData
            });

            const status = (await response.text()).trim();

            if (status === "login_required") {
                window.location.href = "login.php";
            } else if (status === "out_of_stock") {
                alert("That item is currently out of stock.");
            } else if (status === "invalid_quantity") {
                alert("Please choose a valid quantity.");
            } else if (status === "error") {
                alert("Unable to add to cart right now.");
            } else {
                alert("Product added to cart!");
            }
        } catch (error) {
            alert("Unable to add to cart right now.");
        }
    })();

    return false;
}

window.handleAddToCartForm = handleAddToCartForm;

const filterstock = document.getElementById("filter-stock");
const productCards = document.querySelectorAll(".product");
const hamburger = document.querySelector(".hamburger");
const nav = document.querySelector(".nav");

if (hamburger && nav) {
    hamburger.addEventListener("click", () => {
        if (nav.classList.toggle("active")) {
            hamburger.innerHTML = "&#10006;";
        } else {
            hamburger.innerHTML = "&#9776;";
        }
    });
}

document.querySelectorAll(".product").forEach(product => {
    product.addEventListener("click", (e) => {
        // Let the form controls do their own job instead of turning every click into navigation.
        if (
            e.target.classList.contains("add") ||
            e.target.tagName === "INPUT" ||
            e.target.tagName === "LABEL"
        ) {
            return;
        }

        const id = product.dataset.id;
        window.location.href = "Item.php?id=" + id;
    });
});

if (filterstock) {
    filterstock.addEventListener("change", () => {
        const value = filterstock.value;

        productCards.forEach(card => {
            // The cards already carry a simple stock tag in data-stock, so filtering can stay tiny.
            const stock = card.dataset.stock;

            if (value === "all" || stock === value) {
                card.style.display = "";
            } else {
                card.style.display = "none";
            }
        });
    });
}
