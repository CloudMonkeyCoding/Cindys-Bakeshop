async function addToCart() {
  const qtyEl = document.getElementById("qty");
  const qty = qtyEl ? qtyEl.value : 1;
  const productId = new URLSearchParams(window.location.search).get("id");
  if (!productId) {
    alert("Missing product id.");
    return false;
  }
  try {
    const response = await fetch("/PHP/cart_item_functions.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ productId, quantity: qty })
    });
    const result = await response.json();
    if (result.success) {
      alert("Item added to cart!");
      return true;
    } else {
      alert("Failed to add item to cart.");
      return false;
    }
  } catch (error) {
    alert("Error adding item to cart.");
    return false;
  }
}

async function buyNow() {
  const success = await addToCart();
  if (success) {
    window.location.href = "/userSide/CART/cart_checkout_page.html";
  }
}
