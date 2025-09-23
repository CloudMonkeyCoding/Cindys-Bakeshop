<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout - Cindy’s Bakeshop</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    body { background:#faf9f7; color:#333; min-height:100vh; display:flex; flex-direction:column; }

    header { background:#8B4513; color:#fff; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center; }
    header h1 { font-size:1.4rem; }
    nav a { color:#fff; margin-left:1rem; text-decoration:none; font-weight:500; }
    nav a:hover { text-decoration:underline; }

    .container { max-width:1200px; margin:2rem auto; padding:0 1rem; display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem; }
    .section { background:#fff; border-radius:14px; padding:1.8rem; box-shadow:0 6px 16px rgba(0,0,0,0.06); }
    .section h2 { color:#8B4513; font-size:1.35rem; margin-bottom:1.2rem; }

    .info-row { display:flex; flex-direction:column; gap:0.4rem; margin-bottom:1rem; }
    .info-row label { font-weight:600; color:#5b4326; }
    .info-row input,
    .info-row textarea,
    .info-row select { width:100%; padding:0.75rem; border:1px solid #d9c9ba; border-radius:10px; font-size:0.95rem; transition:border 0.2s, box-shadow 0.2s; background:#fffaf4; }
    .info-row textarea { resize:vertical; min-height:90px; }
    .info-row input[readonly],
    .info-row textarea[readonly] { background:#f4ede6; }
    .info-row input:focus,
    .info-row textarea:focus,
    .info-row select:focus { border-color:#8B4513; outline:none; box-shadow:0 0 0 2px rgba(139,69,19,0.15); }

    .editable-field { display:flex; gap:0.5rem; align-items:center; }
    .editable-field button { padding:0.45rem 0.9rem; background:#f5d0a3; border:none; border-radius:8px; cursor:pointer; font-weight:600; color:#704214; }
    .editable-field button:hover { background:#f0b974; }

    .cart-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
    .cart-header label { display:flex; align-items:center; gap:0.4rem; font-weight:600; }
    .refresh-btn { background:transparent; border:1px solid #d9c9ba; border-radius:8px; padding:0.4rem 0.9rem; cursor:pointer; color:#8B4513; font-weight:600; }
    .refresh-btn:hover { background:#f5e6d8; }

    #cartItems { display:flex; flex-direction:column; gap:1rem; }
    .cart-item { display:flex; justify-content:space-between; align-items:center; gap:1rem; border:1px solid #f0e5da; border-radius:12px; padding:1rem; background:#fffaf4; }
    .cart-left { display:flex; gap:0.75rem; align-items:center; flex:1; }
    .cart-left img { width:72px; height:72px; object-fit:cover; border-radius:10px; }
    .item-info { display:flex; flex-direction:column; gap:0.2rem; }
    .item-name { font-weight:600; color:#8B4513; }
    .item-price { font-size:0.9rem; color:#6b5b4a; }
    .stock-note { font-size:0.8rem; color:#c0392b; }

    .qty-control { display:flex; align-items:center; gap:0.45rem; }
    .qty-btn { width:30px; height:30px; border-radius:50%; border:none; background:#8B4513; color:#fff; cursor:pointer; font-size:1rem; display:flex; justify-content:center; align-items:center; }
    .qty-btn:disabled { background:#d8c6ba; cursor:not-allowed; }
    .qty-display { min-width:28px; text-align:center; font-weight:600; }
    .remove-btn { background:#f9d7d5; color:#c0392b; border:none; border-radius:8px; padding:0.35rem 0.7rem; cursor:pointer; font-size:0.85rem; }
    .remove-btn:hover { background:#f4b1ad; }

    .cart-total { display:flex; justify-content:space-between; align-items:center; font-weight:600; margin-top:1rem; font-size:1rem; }
    .actions { display:flex; justify-content:space-between; gap:0.8rem; margin-top:1.2rem; flex-wrap:wrap; }
    .actions button { flex:1; border:none; border-radius:10px; padding:0.8rem 1rem; font-weight:600; cursor:pointer; font-size:1rem; }
    .actions .secondary { background:#f5e6d8; color:#8B4513; }
    .actions .secondary:hover { background:#f0d6bd; }
    .actions .primary { background:#8B4513; color:#fff; }
    .actions .primary:hover { background:#a15a20; }

    .error { color:#c0392b; margin-top:0.8rem; font-size:0.9rem; min-height:1.2rem; }
    .success { color:#2e7d32; margin-top:0.8rem; font-size:0.95rem; min-height:1.2rem; }
    .empty { text-align:center; padding:2rem 1rem; color:#7f7f7f; }

    .modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); justify-content:center; align-items:center; z-index:2000; }
    .modal.show { display:flex; }
    .modal-content { background:#fff; padding:2rem; border-radius:14px; max-width:420px; width:90%; box-shadow:0 12px 24px rgba(0,0,0,0.18); }
    .modal-content h3 { color:#8B4513; margin-bottom:1rem; text-align:center; }
    .modal-content ul { list-style:none; padding:0; margin:1rem 0; display:flex; flex-direction:column; gap:0.5rem; }
    .modal-actions { display:flex; justify-content:flex-end; gap:0.8rem; margin-top:1.5rem; }
    .modal-actions button { padding:0.6rem 1.1rem; border-radius:8px; border:none; cursor:pointer; font-weight:600; }
    .modal-actions .close-btn { background:#f0d6bd; color:#8B4513; }
    .modal-actions .details-btn { background:#8B4513; color:#fff; }

    @media (max-width: 768px) {
      header { flex-direction:column; gap:0.8rem; align-items:flex-start; }
      nav { width:100%; display:flex; flex-wrap:wrap; gap:0.8rem; }
      nav a { margin:0; }
    }
  </style>
</head>
<body>
  <header>
    <h1>🥖 Cindy’s Bakeshop</h1>
    <nav>
      <a href="home.php">Home</a>
      <a href="menu.php">Menu</a>
      <a href="favorites.php">Favorites</a>
      <a href="orders.php">Orders</a>
      <a href="profile.php">Profile</a>
      <a href="settings.php">Settings</a>
    </nav>
  </header>

  <div class="container">
    <div class="section">
      <h2>Customer Information</h2>
      <div class="info-row">
        <label for="name">Full Name</label>
        <div class="editable-field">
          <input type="text" id="name" required readonly>
          <button type="button" data-target="name" class="edit-btn">Edit</button>
          <button type="button" data-target="name" class="save-btn" style="display:none;">Save</button>
        </div>
      </div>

      <div class="info-row">
        <label for="address">Delivery Address</label>
        <div class="editable-field">
          <textarea id="address" rows="3" required readonly></textarea>
          <button type="button" data-target="address" class="edit-btn">Edit</button>
          <button type="button" data-target="address" class="save-btn" style="display:none;">Save</button>
        </div>
      </div>

      <div class="info-row">
        <label for="orderType">Order Type</label>
        <select id="orderType" required>
          <option value="">-- Select --</option>
          <option value="Pick up">Pick Up</option>
          <option value="Delivery">Delivery</option>
        </select>
      </div>

      <div class="info-row">
        <label for="payment">Payment Method</label>
        <select id="payment" required disabled>
          <option value="">-- Select --</option>
        </select>
      </div>

      <div class="info-row">
        <label for="notes">Order Notes</label>
        <textarea id="notes" rows="2" placeholder="Additional instructions (optional)"></textarea>
      </div>
    </div>

    <div class="section">
      <h2>Your Cart</h2>
      <div class="cart-header">
        <label><input type="checkbox" id="toggleAll" checked> Select All</label>
        <button type="button" class="refresh-btn" id="refreshCart">Refresh</button>
      </div>
      <div id="cartItems"></div>
      <div class="cart-total">
        <span>Items: <span id="itemCount">0</span></span>
        <span>Total: ₱<span id="cartTotal">0.00</span></span>
      </div>
      <div class="actions">
        <button type="button" class="secondary" id="backToMenu">← Back to Menu</button>
        <button type="button" class="primary" id="confirmBtn">✅ Confirm Order</button>
      </div>
      <div class="error" id="cartError"></div>
      <div class="success" id="cartSuccess"></div>
    </div>
  </div>

  <div class="modal" id="confirmationModal" role="dialog" aria-modal="true">
    <div class="modal-content">
      <h3>🎉 Order Confirmed!</h3>
      <p><strong>Order ID:</strong> <span id="summaryOrderId"></span></p>
      <p><strong>Name:</strong> <span id="summaryName"></span></p>
      <p><strong>Order Type:</strong> <span id="summaryType"></span></p>
      <p><strong>Payment:</strong> <span id="summaryPayment"></span></p>
      <p id="summaryAddressWrap" style="display:none;"><strong>Address:</strong> <span id="summaryAddress"></span></p>
      <h4 style="margin-top:1rem; color:#8B4513;">🛒 Items</h4>
      <ul id="summaryItems"></ul>
      <p style="font-weight:bold; color:#d2691e;">Total: ₱<span id="summaryTotal"></span></p>
      <div class="modal-actions">
        <button type="button" class="close-btn" id="closeModal">Close</button>
        <a class="details-btn" id="detailsLink" href="#">View Details</a>
      </div>
    </div>
  </div>

  <script type="module">
    import '../userSide/firebase-init.js';
    import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

    const auth = getAuth();
    let userEmail = null;
    let cartId = null;
    const cartItemsContainer = document.getElementById('cartItems');
    const toggleAllCheckbox = document.getElementById('toggleAll');
    const itemCountEl = document.getElementById('itemCount');
    const cartTotalEl = document.getElementById('cartTotal');
    const cartError = document.getElementById('cartError');
    const cartSuccess = document.getElementById('cartSuccess');
    const confirmBtn = document.getElementById('confirmBtn');
    const refreshBtn = document.getElementById('refreshCart');
    const backToMenuBtn = document.getElementById('backToMenu');
    const nameInput = document.getElementById('name');
    const addressInput = document.getElementById('address');
    const orderTypeSelect = document.getElementById('orderType');
    const paymentSelect = document.getElementById('payment');
    const notesInput = document.getElementById('notes');
    const modal = document.getElementById('confirmationModal');
    const summaryOrderId = document.getElementById('summaryOrderId');
    const summaryName = document.getElementById('summaryName');
    const summaryType = document.getElementById('summaryType');
    const summaryPayment = document.getElementById('summaryPayment');
    const summaryAddressWrap = document.getElementById('summaryAddressWrap');
    const summaryAddress = document.getElementById('summaryAddress');
    const summaryItems = document.getElementById('summaryItems');
    const summaryTotal = document.getElementById('summaryTotal');
    const detailsLink = document.getElementById('detailsLink');
    const closeModalBtn = document.getElementById('closeModal');

    let checkoutData = [];

    function formatCurrency(value) {
      return Number(value || 0).toFixed(2);
    }

    function showError(message) {
      cartError.textContent = message;
      if (message) cartSuccess.textContent = '';
    }

    function showSuccess(message) {
      cartSuccess.textContent = message;
      if (message) cartError.textContent = '';
    }

    function updatePaymentOptions() {
      paymentSelect.innerHTML = '<option value="">-- Select --</option>';
      if (orderTypeSelect.value === 'Delivery') {
        paymentSelect.innerHTML += '<option value="GCash">GCash</option>';
      } else if (orderTypeSelect.value === 'Pick up') {
        paymentSelect.innerHTML += '<option value="Cash on Pick Up">Cash on Pick Up</option>';
        paymentSelect.innerHTML += '<option value="GCash">GCash</option>';
      }
      paymentSelect.disabled = orderTypeSelect.value === '';
    }

    orderTypeSelect.addEventListener('change', () => {
      updatePaymentOptions();
      if (orderTypeSelect.value === 'Delivery') {
        addressInput.parentElement.parentElement.style.display = '';
      } else {
        addressInput.parentElement.parentElement.style.display = '';
      }
    });

    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const targetId = btn.dataset.target;
        const field = document.getElementById(targetId);
        if (!field) return;
        field.readOnly = false;
        field.focus();
        btn.style.display = 'none';
        const saveBtn = btn.nextElementSibling;
        if (saveBtn) saveBtn.style.display = 'inline-block';
      });
    });

    document.querySelectorAll('.save-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const targetId = btn.dataset.target;
        const field = document.getElementById(targetId);
        if (!field) return;
        field.readOnly = true;
        btn.style.display = 'none';
        const editBtn = btn.previousElementSibling;
        if (editBtn) editBtn.style.display = 'inline-block';
        saveProfile();
      });
    });

    toggleAllCheckbox.addEventListener('change', () => {
      document.querySelectorAll('.select-item').forEach(cb => {
        cb.checked = toggleAllCheckbox.checked;
      });
      updateTotals();
    });

    refreshBtn.addEventListener('click', loadCart);
    backToMenuBtn.addEventListener('click', () => { window.location.href = 'menu.php'; });
    closeModalBtn.addEventListener('click', () => { modal.classList.remove('show'); });

    async function loadProfile() {
      if (!userEmail) return;
      try {
        const resp = await fetch(`/PHP/user_api.php?action=get_profile&email=${encodeURIComponent(userEmail)}`);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        nameInput.value = data.name || '';
        addressInput.value = data.address || '';
      } catch (err) {
        console.error('Failed to load profile', err);
      }
    }

    async function saveProfile() {
      if (!userEmail) return;
      const params = new URLSearchParams({
        email: userEmail,
        name: nameInput.value.trim(),
        address: addressInput.value.trim()
      });
      try {
        await fetch('/PHP/user_api.php?action=set_profile', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });
      } catch (err) {
        console.error('Failed to save profile', err);
      }
    }

    function renderEmptyCart() {
      cartItemsContainer.innerHTML = '<div class="empty">Your cart is empty.</div>';
      itemCountEl.textContent = '0';
      cartTotalEl.textContent = '0.00';
    }

    async function loadCart() {
      if (!userEmail) return;
      showError('');
      showSuccess('');
      try {
        const resp = await fetch(`/PHP/cart_api.php?action=list&email=${encodeURIComponent(userEmail)}`);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        cartId = data.cart_id || null;
        const items = Array.isArray(data.items) ? data.items : [];

        if (!items.length) {
          renderEmptyCart();
          return;
        }

        cartItemsContainer.innerHTML = '';
        items.forEach(item => {
          const cartItem = document.createElement('div');
          cartItem.className = 'cart-item';
          cartItem.dataset.id = item.Cart_Item_ID;
          cartItem.dataset.productId = item.Product_ID;
          cartItem.dataset.stock = item.Stock_Quantity ?? 0;
          cartItem.dataset.price = item.Price ?? 0;
          cartItem.dataset.quantity = item.Quantity ?? 1;

          const imagePath = item.Image_Path ? `/adminSide/products/uploads/${item.Image_Path}` : '../userSide/Images/cindy_s logo.png';
          const price = Number(item.Price || 0);
          const quantity = parseInt(item.Quantity, 10) || 0;
          const stock = parseInt(item.Stock_Quantity, 10) || 0;
          const available = Math.max(stock, 0);
          const shortage = available < quantity;

          cartItem.innerHTML = `
            <div class="cart-left">
              <input type="checkbox" class="select-item" ${quantity > 0 ? 'checked' : ''}>
              <img src="${imagePath}" alt="${item.Name}">
              <div class="item-info">
                <div class="item-name">${item.Name}</div>
                <div class="item-price">₱${price.toFixed(2)}</div>
                ${shortage ? `<div class="stock-note">Only ${available} left in stock</div>` : ''}
              </div>
            </div>
            <div class="qty-control">
              <button class="qty-btn" data-action="decrease">−</button>
              <span class="qty-display">${quantity}</span>
              <button class="qty-btn" data-action="increase">+</button>
              <button class="remove-btn" title="Remove from cart">🗑</button>
            </div>
          `;

          const selectCheckbox = cartItem.querySelector('.select-item');
          selectCheckbox.addEventListener('change', () => {
            updateTotals();
            toggleAllCheckbox.checked = Array.from(document.querySelectorAll('.select-item')).every(cb => cb.checked);
          });

          cartItem.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', () => handleQuantityChange(cartItem, btn.dataset.action));
          });

          cartItem.querySelector('.remove-btn').addEventListener('click', () => removeItem(cartItem));

          cartItemsContainer.appendChild(cartItem);
        });

        updateTotals();
      } catch (err) {
        console.error('Failed to load cart', err);
        renderEmptyCart();
        showError('Unable to load your cart. Please try again.');
      }
    }

    async function handleQuantityChange(cartItem, action) {
      const qtyDisplay = cartItem.querySelector('.qty-display');
      let quantity = parseInt(qtyDisplay.textContent, 10) || 0;
      const stock = parseInt(cartItem.dataset.stock, 10) || 0;
      const cartItemId = cartItem.dataset.id;

      if (action === 'increase') {
        if (quantity >= stock) {
          showError(`Only ${stock} item(s) available in stock.`);
          return;
        }
        quantity += 1;
      } else if (action === 'decrease') {
        if (quantity <= 1) return;
        quantity -= 1;
      }

      try {
        const params = new URLSearchParams({ cart_item_id: cartItemId, quantity: quantity });
        const resp = await fetch('/PHP/cart_api.php?action=update', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        if (data.capped) {
          quantity = parseInt(data.quantity, 10) || quantity;
          showError('Quantity adjusted to available stock.');
        } else {
          showError('');
        }
        qtyDisplay.textContent = quantity;
        cartItem.dataset.quantity = quantity;
        updateTotals();
      } catch (err) {
        console.error('Failed to update quantity', err);
        showError('Unable to update quantity right now.');
      }
    }

    async function removeItem(cartItem) {
      const cartItemId = cartItem.dataset.id;
      try {
        const params = new URLSearchParams({ cart_item_id: cartItemId });
        const resp = await fetch('/PHP/cart_api.php?action=remove', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });
        await resp.text();
        cartItem.remove();
        if (!cartItemsContainer.children.length) {
          renderEmptyCart();
        } else {
          updateTotals();
        }
        showSuccess('Item removed from cart.');
      } catch (err) {
        console.error('Failed to remove item', err);
        showError('Unable to remove item. Please try again.');
      }
    }

    function updateTotals() {
      let total = 0;
      let count = 0;
      document.querySelectorAll('.cart-item').forEach(item => {
        const checkbox = item.querySelector('.select-item');
        const qty = parseInt(item.querySelector('.qty-display').textContent, 10) || 0;
        if (checkbox.checked) {
          const price = parseFloat(item.dataset.price || '0');
          total += price * qty;
          count += qty;
        }
      });
      cartTotalEl.textContent = formatCurrency(total);
      itemCountEl.textContent = count.toString();
    }

    async function getLatestCart() {
      const resp = await fetch(`/PHP/cart_api.php?action=list&email=${encodeURIComponent(userEmail)}`);
      const text = await resp.text();
      if (!resp.ok) throw new Error(text);
      return JSON.parse(text);
    }

    async function placeOrder() {
      if (!userEmail) return;
      const name = nameInput.value.trim();
      const address = addressInput.value.trim();
      const orderType = orderTypeSelect.value;
      const payment = paymentSelect.value;

      if (!name) {
        showError('Please provide your full name.');
        return;
      }
      if (!orderType) {
        showError('Please select an order type.');
        return;
      }
      if (!payment) {
        showError('Please choose a payment method.');
        return;
      }
      if (orderType === 'Delivery' && !address) {
        showError('Please provide a delivery address.');
        return;
      }

      const selectedItems = [];
      checkoutData = [];

      document.querySelectorAll('.cart-item').forEach(item => {
        const checkbox = item.querySelector('.select-item');
        if (!checkbox.checked) return;
        const qty = parseInt(item.querySelector('.qty-display').textContent, 10) || 0;
        if (qty <= 0) return;
        selectedItems.push({
          cartItemId: item.dataset.id,
          productId: item.dataset.productId,
          quantity: qty,
          stock: parseInt(item.dataset.stock, 10) || 0,
          name: item.querySelector('.item-name').textContent
        });
        checkoutData.push({ product_id: item.dataset.productId, quantity: qty });
      });

      if (!selectedItems.length) {
        showError('Please select at least one item to checkout.');
        return;
      }

      // Persist profile changes before placing order
      await saveProfile();

      try {
        const latest = await getLatestCart();
        const latestItems = latest.items || [];
        const shortages = [];

        selectedItems.forEach(sel => {
          const match = latestItems.find(it => String(it.Cart_Item_ID) === String(sel.cartItemId));
          const available = parseInt(match?.Stock_Quantity ?? sel.stock, 10) || 0;
          if (sel.quantity > available) {
            shortages.push({ ...sel, available });
          }
        });

        if (shortages.length) {
          await Promise.all(shortages.map(s => {
            const params = new URLSearchParams({ cart_item_id: s.cartItemId, quantity: s.available });
            return fetch('/PHP/cart_api.php?action=update', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: params
            });
          }));
          showError(shortages.map(s => `${s.name} quantity reduced to ${s.available}.`).join('\n'));
          await loadCart();
          return;
        }

        const params = new URLSearchParams({
          email: userEmail,
          items: JSON.stringify(checkoutData),
          mop: payment,
          order_type: orderType
        });
        const resp = await fetch('/PHP/order_api.php?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);

        summaryOrderId.textContent = data.order_id;
        summaryName.textContent = name;
        summaryType.textContent = orderType;
        summaryPayment.textContent = payment;
        if (orderType === 'Delivery') {
          summaryAddressWrap.style.display = 'block';
          summaryAddress.textContent = address;
        } else {
          summaryAddressWrap.style.display = 'none';
        }

        summaryItems.innerHTML = '';
        let total = 0;
        selectedItems.forEach(item => {
          const price = parseFloat(document.querySelector(`.cart-item[data-id="${item.cartItemId}"]`).dataset.price || '0');
          const li = document.createElement('li');
          li.textContent = `${item.quantity} × ${item.name}`;
          summaryItems.appendChild(li);
          total += price * item.quantity;
        });
        summaryTotal.textContent = formatCurrency(total);
        detailsLink.href = `order_details.php?order_id=${encodeURIComponent(data.order_id)}`;

        modal.classList.add('show');
        showSuccess('Order placed successfully!');
        await loadCart();
      } catch (err) {
        console.error('Failed to place order', err);
        showError('There was a problem placing your order. Please try again.');
      }
    }

    confirmBtn.addEventListener('click', placeOrder);

    onAuthStateChanged(auth, async (user) => {
      if (!user) {
        window.location.href = 'login.php';
        return;
      }
      userEmail = user.email;
      await loadProfile();
      updatePaymentOptions();
      await loadCart();
    });
  </script>
</body>
</html>
