<?php
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Cindy's Bakery — Order History</title>
  <style>
    body { font-family: 'Poppins', sans-serif; background: #f7f3ef; margin: 0; padding: 0; color:#3d2c1d; }
    header { display:flex; align-items:center; justify-content:space-between; background:#ff8c42; color:white; padding:16px 20px; box-shadow:0 2px 8px rgba(0,0,0,0.15); }
    header h1 { font-size:1.4rem; margin:0; }
    header a { color:#fff; text-decoration:none; font-weight:600; margin-left:1rem; }
    header a:hover { text-decoration:underline; }
    .tabs { display:flex; justify-content:center; gap:10px; margin:20px 0; flex-wrap:wrap; }
    .tab { padding:9px 18px; border-radius:20px; border:1px solid #ffd5b0; background:#fff; cursor:pointer; font-size:0.9rem; color:#5b4326; transition:all 0.2s ease; }
    .tab.active { background:#ff8c42; color:white; border-color:#ff8c42; box-shadow:0 4px 12px rgba(255,140,66,0.3); }
    .orders { max-width:900px; margin:0 auto; display:flex; flex-direction:column; gap:18px; padding:0 15px 40px; }
    .order-card { background:#fff; border-radius:16px; box-shadow:0 6px 16px rgba(0,0,0,0.08); padding:18px; border:1px solid rgba(255,140,66,0.18); transition:transform 0.2s ease; }
    .order-card:hover { transform:translateY(-2px); }
    .order-header { display:flex; justify-content:space-between; color:#8b5e3b; font-size:0.9rem; margin-bottom:12px; flex-wrap:wrap; gap:10px; }
    .order-items { display:flex; gap:14px; align-items:center; }
    .order-items img { width:80px; height:80px; object-fit:cover; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.12); }
    .item-info { flex:1; }
    .item-info h3 { margin:0; font-size:1rem; color:#3d2c1d; }
    .item-info p { margin:4px 0; font-size:0.88rem; color:#6f5844; }
    .order-footer { display:flex; justify-content:space-between; align-items:center; margin-top:12px; flex-wrap:wrap; gap:10px; }
    .status { font-size:0.85rem; font-weight:700; padding:6px 12px; border-radius:16px; }
    .status.pending { background:#ffe6c9; color:#c56a12; }
    .status.completed { background:#c8f5c2; color:#2e7d32; }
    .status.cancelled { background:#f8c7c7; color:#c0392b; }
    .total { font-weight:700; font-size:1rem; color:#3d2c1d; }
    .footer-actions { display:flex; align-items:center; gap:12px; }
    .details-link { color:#ff8c42; font-weight:600; text-decoration:none; }
    .details-link:hover { text-decoration:underline; }
    .empty { text-align:center; padding:3rem 1rem; color:#8b5e3b; background:#fff; border-radius:16px; box-shadow:0 6px 18px rgba(0,0,0,0.05); margin:0 15px; }
    @media (max-width:600px) { .order-items { flex-direction:column; align-items:flex-start; } .order-footer { flex-direction:column; align-items:flex-start; } }
  </style>
</head>
<body>
  <header>
    <div>
      <h1>My Order History</h1>
    </div>
    <div>
      <a href="menu.php">Menu</a>
      <a href="favorites.php">Favorites</a>
      <a href="checkout.php">Cart</a>
      <a href="profile.php">Profile</a>
      <a href="settings.php">Settings</a>
    </div>
  </header>

  <div class="tabs">
    <div class="tab active" data-filter="all">All</div>
    <div class="tab" data-filter="topay">To Pay</div>
    <div class="tab" data-filter="toprepare">To Prepare</div>
    <div class="tab" data-filter="toreceive">To Receive</div>
    <div class="tab" data-filter="completed">Completed</div>
  </div>

  <div class="orders" id="ordersContainer"></div>

  <script type="module">
    import '../userSide/firebase-init.js';
    import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

    const auth = getAuth();
    const container = document.getElementById('ordersContainer');
    const tabs = document.querySelectorAll('.tab');

    function formatDate(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function statusToFilter(status, paymentStatus) {
      const normalized = (status || '').toLowerCase();
      const payment = (paymentStatus || '').toLowerCase();
      if (normalized === 'completed') return 'completed';
      if (normalized === 'cancelled') return 'cancelled';
      if (normalized.includes('delivery') || normalized.includes('shipped') || normalized.includes('receive')) return 'toreceive';
      if (normalized.includes('prepare') || normalized.includes('processing')) return 'toprepare';
      if (payment !== 'paid') return 'topay';
      return 'toprepare';
    }

    function statusClass(status) {
      switch ((status || '').toLowerCase()) {
        case 'completed': return 'completed';
        case 'cancelled': return 'cancelled';
        default: return 'pending';
      }
    }

    function statusLabel(status, paymentStatus) {
      const normalized = (status || '').toLowerCase();
      if (normalized === 'completed') return 'Completed';
      if (normalized === 'cancelled') return 'Cancelled';
      if (normalized.includes('delivery') || normalized.includes('receive')) return 'To Receive';
      if (normalized.includes('prepare') || normalized.includes('processing')) return 'To Prepare';
      if ((paymentStatus || '').toLowerCase() === 'paid') return 'To Prepare';
      return 'To Pay';
    }

    function renderOrders(orders) {
      container.innerHTML = '';
      if (!orders.length) {
        container.innerHTML = '<div class="empty">You have no orders yet. Explore the menu and start your first order!</div>';
        return;
      }
      orders.forEach(order => {
        const card = document.createElement('div');
        card.className = 'order-card';
        card.dataset.filter = order.filterKey;

        const header = document.createElement('div');
        header.className = 'order-header';
        header.innerHTML = `<span>Order #${order.id}</span><span>${order.date}</span>`;

        const items = document.createElement('div');
        items.className = 'order-items';
        const img = document.createElement('img');
        img.src = order.image || '../userSide/Images/cindy_s logo.png';
        img.alt = order.primaryItemName;
        const info = document.createElement('div');
        info.className = 'item-info';
        info.innerHTML = `<h3>${order.primaryItemName}</h3><p>${order.summary}</p><p>Payment: ${order.paymentStatus || 'N/A'}</p>`;
        items.appendChild(img);
        items.appendChild(info);

        const footer = document.createElement('div');
        footer.className = 'order-footer';
        const status = document.createElement('span');
        status.className = `status ${order.statusClass}`;
        status.textContent = order.statusLabel;
        const actions = document.createElement('div');
        actions.className = 'footer-actions';
        const total = document.createElement('span');
        total.className = 'total';
        total.textContent = `₱${order.total}`;
        const details = document.createElement('a');
        details.href = `order_details.php?order_id=${order.id}`;
        details.className = 'details-link';
        details.textContent = 'Details';
        actions.appendChild(total);
        actions.appendChild(details);
        footer.appendChild(status);
        footer.appendChild(actions);

        card.appendChild(header);
        card.appendChild(items);
        card.appendChild(footer);
        container.appendChild(card);
      });
    }

    function applyFilter(filter) {
      document.querySelectorAll('.order-card').forEach(card => {
        const key = card.dataset.filter;
        if (filter === 'all' || key === filter) {
          card.style.display = '';
        } else {
          card.style.display = key === 'cancelled' && filter !== 'completed' ? '' : 'none';
          if (key === 'cancelled' && filter !== 'completed' && filter !== 'topay' && filter !== 'toprepare' && filter !== 'toreceive') {
            card.style.display = '';
          }
        }
      });
    }

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        applyFilter(tab.dataset.filter);
      });
    });

    async function fetchOrders(email) {
      const resp = await fetch(`/PHP/order_api.php?action=list&email=${encodeURIComponent(email)}`);
      const text = await resp.text();
      if (!resp.ok) throw new Error(text);
      const baseOrders = JSON.parse(text);
      if (!Array.isArray(baseOrders) || !baseOrders.length) {
        renderOrders([]);
        return;
      }

      const detailed = await Promise.all(baseOrders.map(async order => {
        try {
          const detailResp = await fetch(`/PHP/order_api.php?action=view&order_id=${order.Order_ID}`);
          const detailText = await detailResp.text();
          if (!detailResp.ok) throw new Error(detailText);
          const detail = JSON.parse(detailText);
          const items = detail.items || [];
          const transaction = detail.transaction || {};
          const total = transaction.Amount_Paid || items.reduce((sum, item) => sum + Number(item.Subtotal || 0), 0);
          const primaryItem = items[0];
          const remaining = Math.max(items.length - 1, 0);
          const summary = primaryItem ? `${primaryItem.Quantity} × ${primaryItem.Name}${remaining > 0 ? ` • +${remaining} more item(s)` : ''}` : 'No items recorded';
          const filterKey = statusToFilter(order.Status, transaction.Payment_Status);
          return {
            id: order.Order_ID,
            date: formatDate(order.Order_Date),
            statusLabel: statusLabel(order.Status, transaction.Payment_Status),
            statusClass: statusClass(order.Status),
            total: Number(total || 0).toFixed(2),
            summary,
            paymentStatus: transaction.Payment_Status || 'Pending',
            primaryItemName: primaryItem ? primaryItem.Name : 'Order Items',
            image: order.Image_Path ? `/adminSide/products/uploads/${order.Image_Path}` : null,
            filterKey
          };
        } catch (err) {
          console.error('Failed to load order details', err);
          return {
            id: order.Order_ID,
            date: formatDate(order.Order_Date),
            statusLabel: statusLabel(order.Status),
            statusClass: statusClass(order.Status),
            total: '0.00',
            summary: 'Unable to load items',
            paymentStatus: 'Unknown',
            primaryItemName: 'Order Items',
            image: order.Image_Path ? `/adminSide/products/uploads/${order.Image_Path}` : null,
            filterKey: statusToFilter(order.Status)
          };
        }
      }));

      renderOrders(detailed);
      const active = document.querySelector('.tab.active');
      applyFilter(active ? active.dataset.filter : 'all');
    }

    onAuthStateChanged(auth, (user) => {
      if (!user) {
        window.location.href = 'login.php';
        return;
      }
      fetchOrders(user.email).catch(err => {
        console.error('Unable to fetch orders', err);
        renderOrders([]);
      });
    });
  </script>
</body>
</html>
