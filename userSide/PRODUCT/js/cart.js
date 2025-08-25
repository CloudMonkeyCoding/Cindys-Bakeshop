async function addToCart() {
  const qtyEl = document.getElementById("qty");
  const qty = qtyEl ? parseInt(qtyEl.value, 10) || 1 : 1;
  if (typeof window.maxStock !== 'undefined' && qty > window.maxStock) {
    alert(`Only ${window.maxStock} left in stock.`);
    return false;
  }
  const productId = new URLSearchParams(window.location.search).get("id");
  if (!productId) {
    alert("Missing product id.");
    return false;
  }
  try {
    // Ensure we have user info
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
    const listText = await listResp.text();
    let listData;
    try {
      listData = JSON.parse(listText);
    } catch (e) {
      console.error("Invalid cart list response", listText);
      alert("Error retrieving cart.");
      return false;
    }
    const cartId = listData.cart_id;
    localStorage.setItem("cart_id", cartId);
    const existing = listData.items.find(
      (item) => String(item.Product_ID) === String(productId)
    );

    const params = new URLSearchParams();
    let action = "add";
    let newQty = qty;
    if (existing) {
      newQty = parseInt(existing.Quantity, 10) + qty;
      if (typeof window.maxStock !== 'undefined' && newQty > window.maxStock) {
        alert(`Only ${window.maxStock} left in stock.`);
        return false;
      }
      action = "update";
      params.set("cart_item_id", existing.Cart_Item_ID);
      params.set("quantity", newQty);
    } else {
      params.set("cart_id", cartId);
      params.set("product_id", productId);
      params.set("quantity", qty);
    }
    if (email) params.set("email", email);

    const response = await fetch(`/PHP/cart_api.php?action=${action}`, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString()
    });
    const respText = await response.text();
    let result;
    try {
      result = JSON.parse(respText);
    } catch (e) {
      console.error("Invalid cart response", respText);
      alert("Error adding item to cart.");
      return false;
    }
    if ((action === "add" && result.cart_item_id) || (action === "update" && result.updated)) {
      alert(action === "add" ? "Item added to cart!" : "Cart quantity updated!");
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
    window.location.href = "/userSide/CART/cart_checkout_page.php";
  }
}
