<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cart & Checkout - Cindy's Bakeshop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles.css" />
  <style>
    body.checkout-view {
      display: flex;
      flex-direction: column;
    }

    .checkout-hero {
      margin-bottom: 2rem;
      display: grid;
      gap: 1rem;
    }

    .checkout-hero h1 {
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 700;
    }

    .checkout-hero p {
      color: var(--text-muted);
      max-width: 560px;
      font-size: 1rem;
    }

    .checkout-wrapper {
      display: grid;
      grid-template-columns: minmax(0, 2fr) minmax(0, 1.2fr);
      gap: 2rem;
      align-items: flex-start;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.92);
      border-radius: 28px;
      box-shadow: var(--shadow-soft);
      border: 1px solid rgba(139, 69, 19, 0.12);
      padding: 2rem;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
    }

    .card-header h2 {
      font-size: 1.6rem;
      font-weight: 700;
    }

    .cart-list {
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
    }

    .cart-item {
      display: grid;
      grid-template-columns: auto 1fr auto;
      gap: 1rem;
      align-items: center;
      padding: 1.25rem;
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.65);
      border: 1px solid rgba(139, 69, 19, 0.08);
    }

    .cart-item .item-check {
      width: 18px;
      height: 18px;
      accent-color: var(--primary-brown);
    }

    .cart-item img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 20px;
      box-shadow: 0 12px 22px rgba(139, 69, 19, 0.15);
    }

    .cart-item-left {
      display: grid;
      grid-template-columns: auto 80px 1fr;
      gap: 1rem;
      align-items: center;
    }

    .item-details b {
      font-size: 1.1rem;
    }

    .item-details span {
      display: block;
      font-size: 0.9rem;
      color: var(--text-muted);
      margin-top: 0.25rem;
    }

    .item-actions {
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .qty-btn {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: rgba(139, 69, 19, 0.12);
      color: var(--primary-brown);
      font-size: 1.2rem;
      font-weight: 600;
    }

    .qty-display {
      min-width: 32px;
      text-align: center;
      font-weight: 600;
    }

    .edit-btn,
    .remove-btn {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: rgba(139, 69, 19, 0.08);
      font-size: 1.1rem;
    }

    .edit-note {
      margin-top: 0.75rem;
    }

    .edit-note input {
      width: 100%;
      border-radius: 12px;
      border: 1px solid rgba(139, 69, 19, 0.12);
      padding: 0.6rem 0.8rem;
      font-size: 0.9rem;
    }

    .cart-footer {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(139, 69, 19, 0.1);
    }

    .check-all {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-weight: 600;
    }

    .cart-totals {
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
      font-weight: 600;
    }

    .primary-btn {
      padding: 0.85rem 1.8rem;
      border-radius: var(--radius-pill);
      background: linear-gradient(135deg, var(--primary-brown), var(--primary-brown-dark));
      color: #fff;
      font-weight: 600;
      box-shadow: 0 16px 32px rgba(139, 69, 19, 0.25);
    }

    .secondary-btn {
      padding: 0.8rem 1.6rem;
      border-radius: var(--radius-pill);
      background: rgba(139, 69, 19, 0.12);
      color: var(--primary-brown);
      font-weight: 600;
    }

    .summary-card {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .summary-item {
      display: flex;
      justify-content: space-between;
      font-weight: 500;
      padding: 0.6rem 0;
      border-bottom: 1px dashed rgba(139, 69, 19, 0.1);
    }

    .summary-item:last-child {
      border-bottom: none;
    }

    .checkout-form {
      display: flex;
      flex-direction: column;
      gap: 1.1rem;
    }

    .checkout-form label {
      font-weight: 600;
      font-size: 0.95rem;
    }

    .checkout-form input,
    .checkout-form textarea,
    .checkout-form select {
      width: 100%;
      border-radius: 14px;
      border: 1px solid rgba(139, 69, 19, 0.15);
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      background: rgba(255, 255, 255, 0.9);
      font-family: inherit;
      resize: vertical;
    }

    .field-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.4rem;
    }

    .edit-field-btn,
    .done-btn {
      background: none;
      border: none;
      color: var(--primary-brown);
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
    }

    .done-btn {
      display: none;
    }

    .confirmation {
      margin-top: 1rem;
      font-weight: 600;
      color: var(--primary-brown);
    }

    @media (max-width: 1100px) {
      .checkout-wrapper {
        grid-template-columns: 1fr;
      }

      #checkout-section {
        order: 2;
      }
    }

    @media (max-width: 768px) {
      .checkout-wrapper {
        gap: 1.5rem;
      }

      .cart-item {
        grid-template-columns: 1fr;
      }

      .cart-item-left {
        grid-template-columns: auto 1fr;
      }

      .item-actions {
        justify-content: flex-end;
      }

      body:not(.checkout-active) #checkout-section {
        display: none;
      }

      body.checkout-active #cart-section {
        display: none;
      }

      body.checkout-active main {
        padding-top: 120px;
      }
    }
  </style>
</head>
<body class="checkout-view">
  <?php include __DIR__ . '/../topbar.php'; ?>
  <main class="page-container">
    <section class="checkout-hero">
      <h1>Your cart is almost ready to bake!</h1>
      <p>Review your selections, adjust quantities, and confirm the sweetest delivery details. We'll take care of the rest.</p>
    </section>

    <div class="checkout-wrapper">
      <section class="glass-card" id="cart-section">
        <div class="card-header">
          <h2>Cart Overview</h2>
          <span class="tag-pill" id="cartStatus">Ready to checkout</span>
        </div>
        <div class="cart-list" id="cart-items"></div>
        <div class="cart-footer">
          <label class="check-all">
            <input type="checkbox" onclick="toggleAll(this)" checked>
            Select all
          </label>
          <div class="cart-totals">
            <span class="total-items">Items: 0</span>
            <span class="total-price">Total Price: ₱0.00</span>
          </div>
          <button class="primary-btn" onclick="goToCheckout()">Proceed to checkout</button>
        </div>
      </section>

      <section class="glass-card" id="checkout-section" style="display: none;">
        <div class="card-header">
          <h2>Delivery details</h2>
          <button class="secondary-btn" type="button" onclick="goBack()">Back to cart</button>
        </div>

        <div class="summary-card" id="checkout-items"></div>

        <form class="checkout-form" onsubmit="placeOrder(event)">
          <div>
            <div class="field-header">
              <label for="name">Full Name</label>
              <button type="button" id="edit-name" class="edit-field-btn">Edit</button>
            </div>
            <div class="input-wrapper">
              <input type="text" id="name" required readonly />
              <button type="button" id="done-name" class="done-btn">Done</button>
            </div>
          </div>

          <div>
            <div class="field-header">
              <label for="address">Delivery Address</label>
              <button type="button" id="edit-address" class="edit-field-btn">Edit</button>
            </div>
            <div class="input-wrapper">
              <textarea id="address" rows="3" required readonly></textarea>
              <button type="button" id="done-address" class="done-btn">Done</button>
            </div>
          </div>

          <div>
            <label for="order-type">Delivery or Pick Up</label>
            <select id="order-type" required>
              <option value="">-- Select --</option>
              <option value="Delivery">Delivery</option>
              <option value="Pick up">Pick up</option>
            </select>
          </div>

          <div>
            <label for="mop">Mode of Payment</label>
            <select id="mop" required disabled>
              <option value="">-- Select --</option>
            </select>
          </div>

          <button type="submit" class="primary-btn">Place order</button>
        </form>
        <div class="confirmation" id="confirmationMsg"></div>
      </section>
    </div>
  </main>

  <script type="module">
    import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
    import "../firebase-init.js";

    const cartContainer = document.getElementById('cart-items');
    const masterCheckbox = document.querySelector('.check-all input[type="checkbox"]');
    let cartId = null;
    let checkoutData = [];
    let userEmail = null;
    const nameField = document.getElementById('name');
    const addressField = document.getElementById('address');
    const nameEditBtn = document.getElementById('edit-name');
    const addrEditBtn = document.getElementById('edit-address');
    const nameDoneBtn = document.getElementById('done-name');
    const addrDoneBtn = document.getElementById('done-address');
    const orderTypeSelect = document.getElementById('order-type');
    const mopSelect = document.getElementById('mop');
    const cartStatus = document.getElementById('cartStatus');

    orderTypeSelect.addEventListener('change', () => {
      mopSelect.innerHTML = '<option value="">-- Select --</option>';
      if (orderTypeSelect.value === 'Delivery') {
        mopSelect.innerHTML += '<option value="GCash">GCash</option>';
      } else if (orderTypeSelect.value === 'Pick up') {
        mopSelect.innerHTML += '<option value="Cash on Pick Up">Cash on Pick Up</option>';
        mopSelect.innerHTML += '<option value="GCash">GCash</option>';
      }
      mopSelect.disabled = orderTypeSelect.value === '';
    });

    nameEditBtn.addEventListener('click', () => {
      nameField.readOnly = false;
      nameDoneBtn.style.display = 'inline-flex';
      nameField.focus();
    });

    nameDoneBtn.addEventListener('click', () => {
      nameField.readOnly = true;
      nameDoneBtn.style.display = 'none';
      saveProfile();
    });

    addrEditBtn.addEventListener('click', () => {
      addressField.readOnly = false;
      addrDoneBtn.style.display = 'inline-flex';
      addressField.focus();
    });

    addrDoneBtn.addEventListener('click', () => {
      addressField.readOnly = true;
      addrDoneBtn.style.display = 'none';
      saveProfile();
    });

    const auth = getAuth();
    onAuthStateChanged(auth, user => {
      if (user) {
        userEmail = user.email;
        loadCart();
        loadProfile();
      }
    });

    async function loadProfile() {
      if (!userEmail) return;
      const res = await fetch(`../../../PHP/user_api.php?action=get_profile&email=${encodeURIComponent(userEmail)}`);
      const data = await res.json();
      nameField.value = data.name || '';
      addressField.value = data.address || '';
    }

    async function loadCart() {
      if (!userEmail) return;
      const res = await fetch(`../../../PHP/cart_api.php?action=list&email=${encodeURIComponent(userEmail)}`);
      const data = await res.json();
      cartId = data.cart_id;
      const cart = data.items;
      cartContainer.innerHTML = '';

      if (!cart || cart.length === 0) {
        cartContainer.innerHTML = '<p class="empty-note">Your cart is empty.</p>';
        document.querySelector('.total-items').textContent = 'Items: 0';
        document.querySelector('.total-price').textContent = 'Total Price: ₱0.00';
        cartStatus.textContent = 'Add items to continue';
        return;
      }

      cartStatus.textContent = 'Ready to checkout';

      cart.forEach(item => {
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.setAttribute('data-id', item.Cart_Item_ID);
        div.setAttribute('data-product', item.Product_ID);
        div.setAttribute('data-price', item.Price);
        div.setAttribute('data-stock', item.Stock_Quantity);
        const imageSrc = item.Image_Path ? '../../../adminSide/products/uploads/' + item.Image_Path : '../../../Images/logo.png';
        div.innerHTML = `
          <div class="cart-item-left">
            <input type="checkbox" class="item-check" checked>
            <img src="${imageSrc}" alt="${item.Name}">
            <div class="item-details">
              <b>${item.Name}</b>
              <span>₱${parseFloat(item.Price).toFixed(2)}</span>
              <div class="edit-note" style="display: none;">
                <input type="text" placeholder="Add note (e.g. No icing)">
              </div>
            </div>
          </div>
          <div class="item-actions">
            <button class="qty-btn decrease-btn" type="button">-</button>
            <div class="qty-display">${item.Quantity}</div>
            <button class="qty-btn increase-btn" type="button">+</button>
            <button class="edit-btn" type="button">✏️</button>
            <button class="remove-btn" type="button">🗑️</button>
          </div>
        `;
        cartContainer.appendChild(div);

        div.querySelector('.decrease-btn').addEventListener('click', e => decreaseQty(e.target));
        div.querySelector('.increase-btn').addEventListener('click', e => increaseQty(e.target));
        div.querySelector('.edit-btn').addEventListener('click', e => toggleEdit(e.target));
        div.querySelector('.remove-btn').addEventListener('click', e => removeItem(e.target));
      });

      document.querySelectorAll('.item-check').forEach(cb => {
        cb.addEventListener('change', updateTotal);
      });

      updateTotal();
    }

    function saveProfile() {
      if (!userEmail) return;
      fetch('../../../PHP/user_api.php?action=set_profile', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `email=${encodeURIComponent(userEmail)}&name=${encodeURIComponent(nameField.value)}&address=${encodeURIComponent(addressField.value)}`
      });
    }

    function updateTotal() {
      let total = 0;
      let itemCount = 0;
      document.querySelectorAll('.cart-item').forEach(item => {
        const checkbox = item.querySelector('.item-check');
        if (checkbox.checked) {
          const price = parseFloat(item.getAttribute('data-price'));
          const qty = parseInt(item.querySelector('.qty-display').textContent);
          total += price * qty;
          itemCount += qty;
        }
      });
      document.querySelector('.total-price').textContent = 'Total Price: ₱' + total.toFixed(2);
      document.querySelector('.total-items').textContent = 'Items: ' + itemCount;
      checkMasterToggle();
    }

    function increaseQty(button) {
      const qtyDisplay = button.previousElementSibling;
      let qty = parseInt(qtyDisplay.textContent);
      qtyDisplay.textContent = qty + 1;
      saveQty(button, qty + 1);
      updateTotal();
    }

    function decreaseQty(button) {
      const qtyDisplay = button.nextElementSibling;
      let qty = parseInt(qtyDisplay.textContent);
      if (qty > 1) {
        qtyDisplay.textContent = qty - 1;
        saveQty(button, qty - 1);
        updateTotal();
      }
    }

    function saveQty(button, newQty) {
      const item = button.closest('.cart-item');
      const id = item.getAttribute('data-id');
      fetch('../../../PHP/cart_api.php?action=update', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cart_item_id=${id}&quantity=${newQty}`
      }).then(() => {
        if (newQty <= 0) {
          item.remove();
        }
        updateTotal();
      });
    }

    window.toggleAll = function(masterCheckbox) {
      const checkboxes = document.querySelectorAll('.item-check');
      checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
      updateTotal();
    }

    function checkMasterToggle() {
      const checkboxes = document.querySelectorAll('.item-check');
      const allChecked = Array.from(checkboxes).every(cb => cb.checked);
      masterCheckbox.checked = allChecked;
    }

    function removeItem(button) {
      const item = button.closest('.cart-item');
      const id = item.getAttribute('data-id');
      fetch('../../../PHP/cart_api.php?action=remove', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cart_item_id=${id}`
      }).then(() => loadCart());
    }

    function toggleEdit(button) {
      const item = button.closest('.cart-item');
      const note = item.querySelector('.edit-note');
      note.style.display = note.style.display === 'none' ? 'block' : 'none';
    }

    window.goToCheckout = function() {
      const checkoutItems = document.getElementById('checkout-items');
      checkoutItems.innerHTML = '';
      checkoutData = [];

      let hasItem = false;

      document.querySelectorAll('.cart-item').forEach(item => {
        const checkbox = item.querySelector('.item-check');
        if (checkbox.checked) {
          const name = item.querySelector('.item-details b').textContent;
          let qty = parseInt(item.querySelector('.qty-display').textContent, 10);
          const stock = parseInt(item.getAttribute('data-stock'), 10);
          if (qty > stock) {
            if (stock <= 0) {
              alert(`${name} is out of stock and has been removed from your cart.`);
              saveQty(item.querySelector('.increase-btn'), 0);
              return;
            }
            alert(`${name} quantity reduced to available stock of ${stock}.`);
            qty = stock;
            item.querySelector('.qty-display').textContent = stock;
            saveQty(item.querySelector('.increase-btn'), stock);
          }
          if (qty <= 0) {
            return;
          }
          hasItem = true;
          const price = parseFloat(item.getAttribute('data-price'));
          const total = price * qty;

          const div = document.createElement('div');
          div.classList.add('summary-item');
          div.innerHTML = `<span>${name} ×${qty}</span><span>₱${total.toFixed(2)}</span>`;
          checkoutItems.appendChild(div);
          checkoutData.push({product_id: item.getAttribute('data-product'), quantity: qty});
        }
      });

      updateTotal();

      if (!hasItem) {
        alert('Please select at least one item to check out.');
        return;
      }

      document.getElementById('checkout-section').style.display = 'flex';
      document.body.classList.add('checkout-active');
      window.scrollTo({ top: document.getElementById('checkout-section').offsetTop - 120, behavior: 'smooth' });
    }

    window.goBack = function() {
      document.body.classList.remove('checkout-active');
      document.getElementById('checkout-section').style.display = 'none';
      window.scrollTo({ top: document.getElementById('cart-section').offsetTop - 120, behavior: 'smooth' });
    }

    async function placeOrder(e) {
      e.preventDefault();
      const name = document.getElementById('name').value;
      const address = document.getElementById('address').value;
      const orderType = document.getElementById('order-type').value;
      const mop = document.getElementById('mop').value;

      try {
        const res = await fetch(`../../../PHP/cart_api.php?action=list&email=${encodeURIComponent(userEmail)}`);
        const cartType = res.headers.get('Content-Type') || '';
        const cartText = await res.text();
        if (!res.ok || !cartType.includes('application/json')) {
          throw new Error(cartText);
        }
        let latest;
        try {
          latest = JSON.parse(cartText);
        } catch (error) {
          throw new Error('Invalid response from server.');
        }

        if (!latest.items || latest.items.length === 0) {
          alert('Your cart is empty.');
          return;
        }

        const payload = new URLSearchParams({
          cart_id: latest.cart_id,
          name,
          address,
          order_type: orderType,
          mop,
        });

        const orderRes = await fetch('../../../PHP/order_api.php?action=place', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: payload.toString(),
        });

        const orderText = await orderRes.text();
        const orderData = JSON.parse(orderText);
        if (orderData.error) {
          throw new Error(orderData.error);
        }

        document.getElementById('confirmationMsg').textContent = 'Order placed successfully!';
        loadCart();
        document.body.classList.remove('checkout-active');
        document.getElementById('checkout-section').style.display = 'none';
      } catch (error) {
        document.getElementById('confirmationMsg').textContent = error.message || 'Failed to place order.';
      }
    }

    window.placeOrder = placeOrder;
  </script>
</body>
</html>
