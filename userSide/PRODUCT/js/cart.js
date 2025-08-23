async function addToCart() {
  const qtyEl = document.getElementById("qty");
  const qty = qtyEl ? parseInt(qtyEl.value, 10) || 1 : 1;
  const productId = new URLSearchParams(window.location.search).get("id");
  if (!productId) {
    alert("Missing product id.");
    return false;
  }
  try {
    // Ensure we have a cart ID for this user
    let cartId = localStorage.getItem("cart_id");
    if (!cartId) {
      let email = null;
      try {
        const auth = window.getAuth ? window.getAuth() : null;
        email = auth && auth.currentUser ? auth.currentUser.email : null;
      } catch (e) {
        console.error("Auth unavailable", e);
      }
      const listUrl = email
        ? `/PHP/cart_api.php?action=list&email=${encodeURIComponent(email)}`
        : `/PHP/cart_api.php?action=list`;
      const listResp = await fetch(listUrl);
      const listData = await listResp.json();
      cartId = listData.cart_id;
      localStorage.setItem("cart_id", cartId);
    }
    const body = `cart_id=${encodeURIComponent(cartId)}&product_id=${encodeURIComponent(
      productId
    )}&quantity=${encodeURIComponent(qty)}`;
    const response = await fetch("/PHP/cart_api.php?action=add", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body
    });
    const result = await response.json();
    if (result.cart_item_id) {
      alert("Item added to cart!");
      return true;
    } else {
      alert("Failed to add item to cart.");
      return false;
    }
  } catch (error) {
    console.error(error);
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
