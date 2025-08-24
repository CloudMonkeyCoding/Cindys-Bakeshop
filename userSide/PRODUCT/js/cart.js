async function addToCart() {
  const qtyEl = document.getElementById("qty");
  const qty = qtyEl ? parseInt(qtyEl.value, 10) || 1 : 1;
  const productId = new URLSearchParams(window.location.search).get("id");
  if (!productId) {
    alert("Missing product id.");
    return false;
  }
  try {
    // Ensure we have user info and a cart ID
    let email = null;
    try {
      const auth = window.getAuth ? window.getAuth() : null;
      email = auth && auth.currentUser ? auth.currentUser.email : null;
    } catch (e) {
      console.error("Auth unavailable", e);
    }

    let cartId = localStorage.getItem("cart_id");
    if (!cartId) {
      const listUrl = email
        ? `/PHP/cart_api.php?action=list&email=${encodeURIComponent(email)}`
        : `/PHP/cart_api.php?action=list`;
      const listResp = await fetch(listUrl);
      const listText = await listResp.text();
      let listData;
      try {
        listData = JSON.parse(listText);
      } catch (e) {
        console.error("Invalid cart list response", listText);
        alert("Error retrieving cart.");
        return false;
      }
      cartId = listData.cart_id;
      localStorage.setItem("cart_id", cartId);
    }

    const params = new URLSearchParams();
    params.set("cart_id", cartId);
    params.set("product_id", productId);
    params.set("quantity", qty);
    if (email) params.set("email", email);

    const response = await fetch("/PHP/cart_api.php?action=add", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString()
    });
    const respText = await response.text();
    let result;
    try {
      result = JSON.parse(respText);
    } catch (e) {
      console.error("Invalid add-to-cart response", respText);
      alert("Error adding item to cart.");
      return false;
    }
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
