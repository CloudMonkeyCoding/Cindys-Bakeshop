<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order Details • Cindy’s Bakeshop</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }
    body { background:#f7f2ea; color:#3c2a1d; min-height:100vh; display:flex; flex-direction:column; }

    nav { background:#fff9f3; display:flex; justify-content:space-between; align-items:center; padding:1rem 2rem; box-shadow:0 1px 6px rgba(0,0,0,0.06); position:sticky; top:0; z-index:999; }
    nav .logo { font-size:1.3rem; font-weight:600; color:#a66e44; display:flex; align-items:center; gap:.6rem; }
    nav .logo img { height:42px; }
    nav ul { list-style:none; display:flex; gap:1.2rem; align-items:center; }
    nav ul li a { color:#3c2a1d; text-decoration:none; font-weight:500; }
    nav ul li a:hover, nav ul li a.active { color:#a66e44; }
    nav .menu-toggle { display:none; font-size:1.8rem; cursor:pointer; }

    @media (max-width:768px) {
      nav { flex-wrap:wrap; }
      nav ul { display:none; flex-direction:column; gap:1rem; width:100%; margin-top:1rem; }
      nav ul.show { display:flex; }
      .menu-toggle { display:block; }
      .profile-dropdown .dropdown-menu { position:static; box-shadow:none; border-radius:6px; background:#fff9f3; margin-top:0.5rem; width:100%; }
      .profile-dropdown .dropdown-menu li { padding:0.7rem 0.5rem; border-top:1px solid #f0e5da; }
      .profile-dropdown .dropdown-menu li:first-child { border-top:none; }
    }

    .profile-dropdown { position:relative; }
    .profile-dropdown span { cursor:pointer; display:flex; align-items:center; gap:.25rem; }
    .profile-dropdown .dropdown-menu { position:absolute; top:120%; right:0; background:#fff; color:#3c2a1d; border-radius:8px; box-shadow:0 3px 10px rgba(0,0,0,0.08); display:none; flex-direction:column; min-width:190px; z-index:2000; }
    .profile-dropdown .dropdown-menu li { padding:0.7rem 1rem; }
    .profile-dropdown .dropdown-menu li a { text-decoration:none; color:#3c2a1d; display:block; }
    .profile-dropdown .dropdown-menu li:hover { background:#f9f6f2; }
    .profile-dropdown .dropdown-menu li[data-auth="required"] { display:none; }
    .profile-dropdown.show .dropdown-menu { display:flex; }

    main { flex:1; }
    .invoice-wrapper { max-width:900px; margin:2.5rem auto; padding:0 1.5rem 3rem; }
    .invoice-card { background:#fff; border-radius:20px; box-shadow:0 14px 30px rgba(0,0,0,0.08); padding:2.5rem; display:flex; flex-direction:column; gap:1.5rem; }
    .invoice-card header { display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.8rem; align-items:center; }
    .invoice-card h1 { font-size:1.8rem; color:#5b3a1e; }
    .invoice-card .status { padding:0.4rem 0.8rem; border-radius:999px; background:#f3e2da; color:#8b4513; font-weight:600; font-size:0.9rem; }

    .info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.4rem; }
    .info-block { background:#fff8ef; border-radius:14px; padding:1rem; display:flex; flex-direction:column; gap:0.4rem; }
    .info-block span.label { font-size:0.85rem; color:#806552; text-transform:uppercase; letter-spacing:0.04em; }
    .info-block span.value { font-size:1.05rem; color:#4a3016; font-weight:600; }

    .items-section { border-top:1px solid #f0e5da; padding-top:1.5rem; }
    .items-section h2 { font-size:1.2rem; color:#a66e44; margin-bottom:1rem; }
    .item-row { display:flex; justify-content:space-between; align-items:center; padding:0.75rem 1rem; border-radius:12px; background:#fff9f3; margin-bottom:0.8rem; }
    .item-row .details { display:flex; flex-direction:column; gap:0.2rem; }
    .item-row .details span { color:#6f5844; }
    .item-row .details strong { color:#5b3a1e; }
    .item-row .amount { font-weight:700; color:#c25d21; }

    .total-row { display:flex; justify-content:space-between; align-items:center; font-size:1.2rem; font-weight:700; color:#c25d21; margin-top:1rem; }

    .actions { display:flex; gap:1rem; flex-wrap:wrap; margin-top:1.5rem; }
    .actions a, .actions button { flex:1 1 160px; padding:0.85rem 1.2rem; border-radius:12px; border:none; font-weight:700; cursor:pointer; text-decoration:none; text-align:center; font-size:1rem; transition:background 0.2s, transform 0.2s; }
    .actions .primary { background:#c25d21; color:#fff; box-shadow:0 10px 22px rgba(194,93,33,0.22); }
    .actions .primary:hover { background:#a74d16; transform:translateY(-2px); }
    .actions .secondary { background:#f3e2da; color:#8b4513; }
    .actions .secondary:hover { background:#e5cdbd; }

    .empty-state { max-width:520px; margin:6rem auto; text-align:center; background:#fff; padding:2.5rem; border-radius:16px; box-shadow:0 12px 26px rgba(0,0,0,0.1); color:#6f5844; font-size:1.05rem; display:none; }
  </style>
</head>
<body>
  <nav>
    <div class="logo"><img src="../Kehnt_admin_Design/Cindys.png" alt="Cindy’s Logo"/> Cindy’s Bakeshop</div>
    <div class="menu-toggle" id="menuToggle">☰</div>
    <ul id="navMenu">
      <li><a href="home.php">Home</a></li>
      <li><a href="menu.php">Menu</a></li>
      <li><a href="checkout.php">Cart 🛒</a></li>
      <li><a href="orders.php" class="active">Orders</a></li>
      <li class="profile-dropdown">
        <span id="profileToggle">Account ▾</span>
        <ul class="dropdown-menu" id="profileMenu">
          <li data-auth="required"><a href="profile.php">View Profile</a></li>
          <li data-auth="required"><a href="favorites.php">Favorites</a></li>
          <li data-auth="required"><a href="orders.php">Order History</a></li>
          <li data-auth="required"><a href="settings.php">Account Settings</a></li>
          <li data-auth="required"><a href="logout.php">Logout</a></li>
          <li data-auth="guest"><a href="login.php">Login</a></li>
          <li data-auth="guest"><a href="signup.php">Sign Up</a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <main>
    <div class="invoice-wrapper">
      <div class="invoice-card" id="invoice" style="display:none;">
        <header>
          <div>
            <h1>Order <span id="orderId">#—</span></h1>
            <p style="color:#806552;">Placed on <span id="orderDate">—</span></p>
          </div>
          <span class="status" id="orderStatus">Pending</span>
        </header>

        <div class="info-grid">
          <div class="info-block">
            <span class="label">Customer</span>
            <span class="value" id="customerName">—</span>
          </div>
          <div class="info-block">
            <span class="label">Email</span>
            <span class="value" id="customerEmail">—</span>
          </div>
          <div class="info-block">
            <span class="label">Payment Method</span>
            <span class="value" id="paymentMethod">—</span>
          </div>
          <div class="info-block" id="addressBlock">
            <span class="label">Delivery Address</span>
            <span class="value" id="customerAddress">—</span>
          </div>
        </div>

        <div class="items-section">
          <h2>Items</h2>
          <div id="itemsList"></div>
          <div class="total-row">
            <span>Total</span>
            <span id="orderTotal">₱0.00</span>
          </div>
        </div>

        <div class="actions">
          <a href="orders.php" class="secondary">← Back to Orders</a>
          <button type="button" class="primary" id="downloadBtn">Download PDF</button>
        </div>
      </div>

      <div class="empty-state" id="emptyState">
        We couldn’t find the order you’re looking for. Return to your orders to review your recent purchases.
      </div>
    </div>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script type="module">
    import '../userSide/firebase-init.js';
    import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    menuToggle?.addEventListener('click', () => navMenu.classList.toggle('show'));

    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    profileToggle?.addEventListener('click', () => profileMenu?.parentElement?.classList.toggle('show'));
    window.addEventListener('click', (event) => {
      if (!event.target.closest('.profile-dropdown')) {
        profileMenu?.parentElement?.classList.remove('show');
      }
    });

    const auth = getAuth();
    let userEmail = null;

    const invoiceCard = document.getElementById('invoice');
    const emptyState = document.getElementById('emptyState');
    const orderIdEl = document.getElementById('orderId');
    const orderDateEl = document.getElementById('orderDate');
    const orderStatusEl = document.getElementById('orderStatus');
    const customerNameEl = document.getElementById('customerName');
    const customerEmailEl = document.getElementById('customerEmail');
    const customerAddressEl = document.getElementById('customerAddress');
    const addressBlock = document.getElementById('addressBlock');
    const paymentMethodEl = document.getElementById('paymentMethod');
    const itemsListEl = document.getElementById('itemsList');
    const orderTotalEl = document.getElementById('orderTotal');
    const downloadBtn = document.getElementById('downloadBtn');

    const params = new URLSearchParams(window.location.search);
    const orderIdParam = params.get('order_id');

    function updateProfileMenu() {
      if (!profileToggle || !profileMenu) return;
      const authedItems = profileMenu.querySelectorAll('[data-auth="required"]');
      const guestItems = profileMenu.querySelectorAll('[data-auth="guest"]');
      if (userEmail) {
        profileToggle.textContent = `${userEmail} ▾`;
        authedItems.forEach((item) => { item.style.display = 'block'; });
        guestItems.forEach((item) => { item.style.display = 'none'; });
      } else {
        profileToggle.textContent = 'Login ▾';
        authedItems.forEach((item) => { item.style.display = 'none'; });
        guestItems.forEach((item) => { item.style.display = 'block'; });
        profileMenu.parentElement?.classList.remove('show');
      }
    }

    function formatDate(value) {
      if (!value) return '—';
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return value;
      return date.toLocaleString(undefined, { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    function formatCurrency(amount) {
      return `₱${Number(amount || 0).toFixed(2)}`;
    }

    function renderItems(items) {
      itemsListEl.innerHTML = '';
      items.forEach((item) => {
        const row = document.createElement('div');
        row.className = 'item-row';
        row.innerHTML = `
          <div class="details">
            <strong>${item.Name}</strong>
            <span>${item.Quantity} × ${formatCurrency(item.Price ?? item.Subtotal)}</span>
          </div>
          <div class="amount">${formatCurrency(item.Subtotal)}</div>
        `;
        itemsListEl.appendChild(row);
      });
    }

    async function loadOrder() {
      if (!orderIdParam) {
        invoiceCard.style.display = 'none';
        emptyState.style.display = 'block';
        return;
      }
      try {
        const resp = await fetch(`/PHP/order_api.php?action=view&order_id=${encodeURIComponent(orderIdParam)}`);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        if (!data.order) {
          invoiceCard.style.display = 'none';
          emptyState.style.display = 'block';
          return;
        }
        const order = data.order;
        const transaction = data.transaction || {};
        const user = data.user || {};
        const items = Array.isArray(data.items) ? data.items : [];

        orderIdEl.textContent = `#${order.Order_ID}`;
        orderDateEl.textContent = formatDate(order.Order_Date);
        orderStatusEl.textContent = order.Status || 'Pending';
        customerNameEl.textContent = user.Name || '—';
        customerEmailEl.textContent = user.Email || userEmail || '—';
        paymentMethodEl.textContent = transaction.Payment_Method || '—';

        const address = user.Address || '';
        if (address.trim()) {
          customerAddressEl.textContent = address;
          addressBlock.style.display = '';
        } else {
          addressBlock.style.display = 'none';
        }

        renderItems(items);
        const total = transaction.Amount_Paid ?? items.reduce((sum, item) => sum + Number(item.Subtotal || 0), 0);
        orderTotalEl.textContent = formatCurrency(total);

        invoiceCard.style.display = 'flex';
        emptyState.style.display = 'none';
      } catch (err) {
        console.error('Unable to load order', err);
        invoiceCard.style.display = 'none';
        emptyState.style.display = 'block';
      }
    }

    downloadBtn?.addEventListener('click', () => {
      if (!invoiceCard || invoiceCard.style.display === 'none') return;
      const options = {
        margin: 0.5,
        filename: `CindysBakeshop_Order_${orderIdParam || 'invoice'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
      };
      html2pdf().set(options).from(invoiceCard).save();
    });

    onAuthStateChanged(auth, (user) => {
      if (!user) {
        window.location.href = 'login.php';
        return;
      }
      userEmail = user.email;
      updateProfileMenu();
      loadOrder();
    });

    updateProfileMenu();
  </script>
</body>
</html>
