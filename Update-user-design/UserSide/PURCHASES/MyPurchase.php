<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Orders - Cindy's Bakeshop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles.css" />
  <style>
    body.purchases-view {
      display: flex;
      flex-direction: column;
    }

    .orders-hero {
      background: linear-gradient(135deg, rgba(139, 69, 19, 0.92), rgba(130, 214, 247, 0.85));
      border-radius: 32px;
      padding: clamp(2.5rem, 5vw, 4rem);
      color: #fff;
      margin-bottom: 3rem;
      box-shadow: 0 30px 60px rgba(139, 69, 19, 0.25);
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .orders-hero h1 {
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 700;
    }

    .orders-hero p {
      font-size: 1.05rem;
      max-width: 560px;
      opacity: 0.85;
    }

    .status-tabs {
      display: inline-flex;
      flex-wrap: wrap;
      background: rgba(255, 255, 255, 0.9);
      padding: 0.5rem;
      border-radius: var(--radius-pill);
      box-shadow: var(--shadow-soft);
      gap: 0.5rem;
    }

    .status-tabs button {
      border: none;
      padding: 0.65rem 1.4rem;
      border-radius: var(--radius-pill);
      background: transparent;
      font-weight: 600;
      color: var(--primary-brown);
      cursor: pointer;
    }

    .status-tabs button.active {
      background: linear-gradient(135deg, var(--primary-brown), var(--primary-brown-dark));
      color: #fff;
      box-shadow: 0 12px 28px rgba(139, 69, 19, 0.2);
    }

    .orders-grid {
      display: grid;
      gap: 1.6rem;
      margin-top: 2.5rem;
    }

    .order-card {
      background: rgba(255, 255, 255, 0.92);
      border-radius: 28px;
      padding: 1.6rem;
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 1.5rem;
      align-items: center;
      box-shadow: var(--shadow-soft);
      border: 1px solid rgba(139, 69, 19, 0.1);
    }

    .order-summary {
      display: flex;
      gap: 1.2rem;
      align-items: center;
    }

    .order-summary img {
      width: 96px;
      height: 96px;
      object-fit: cover;
      border-radius: 22px;
      box-shadow: 0 18px 32px rgba(139, 69, 19, 0.18);
    }

    .order-meta {
      display: grid;
      gap: 0.35rem;
    }

    .order-meta h3 {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--primary-brown);
    }

    .order-meta span {
      color: var(--text-muted);
      font-size: 0.95rem;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.4rem 1rem;
      border-radius: var(--radius-pill);
      font-size: 0.85rem;
      font-weight: 600;
      background: rgba(139, 69, 19, 0.12);
      color: var(--primary-brown);
    }

    .order-actions {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 0.75rem;
    }

    .order-actions a,
    .order-actions button {
      padding: 0.7rem 1.4rem;
      border-radius: var(--radius-pill);
      border: none;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
    }

    .order-actions a {
      background: rgba(139, 69, 19, 0.1);
      color: var(--primary-brown);
    }

    .order-actions button.primary {
      background: linear-gradient(135deg, var(--primary-brown), var(--primary-brown-dark));
      color: #fff;
    }

    .order-actions button.danger {
      background: rgba(200, 40, 60, 0.12);
      color: #c8283c;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      border-radius: 28px;
      background: rgba(255, 255, 255, 0.92);
      border: 1px dashed rgba(139, 69, 19, 0.2);
      color: var(--text-muted);
      box-shadow: var(--shadow-soft);
      margin-top: 2rem;
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.4);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 3500;
      padding: 1.5rem;
    }

    .modal-backdrop.show {
      display: flex;
    }

    .modal-card {
      background: #fff;
      border-radius: 24px;
      padding: 2rem;
      max-width: 420px;
      width: 100%;
      box-shadow: var(--shadow-strong);
      display: grid;
      gap: 1.2rem;
    }

    .modal-card h3 {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--primary-brown);
    }

    .modal-card select {
      border-radius: 16px;
      border: 1px solid rgba(139, 69, 19, 0.12);
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 0.8rem;
    }

    .modal-actions button {
      padding: 0.65rem 1.3rem;
      border-radius: var(--radius-pill);
      border: none;
      font-weight: 600;
      cursor: pointer;
    }

    .modal-actions .primary {
      background: linear-gradient(135deg, var(--primary-brown), var(--primary-brown-dark));
      color: #fff;
    }

    .modal-actions .secondary {
      background: rgba(139, 69, 19, 0.12);
      color: var(--primary-brown);
    }

    @media (max-width: 780px) {
      .order-card {
        grid-template-columns: 1fr;
        align-items: flex-start;
      }

      .order-actions {
        align-items: stretch;
      }
    }
  </style>
</head>
<body class="purchases-view">
  <?php include __DIR__ . '/../topbar.php'; ?>

  <main class="page-container">
    <section class="orders-hero">
      <h1>Track your Cindy's journeys</h1>
      <p>From the oven to your doorstep—see what’s baking, what’s on the way, and what you’ve already savoured.</p>
      <div class="status-tabs" role="tablist">
        <button type="button" class="active" data-filter="all">All orders</button>
        <button type="button" data-filter="to-process">To process</button>
        <button type="button" data-filter="to-receive">To receive</button>
        <button type="button" data-filter="completed">Completed</button>
      </div>
    </section>

    <div class="orders-grid" id="ordersGrid"></div>
    <div class="empty-state" id="ordersEmpty" hidden>No orders yet. Explore the menu and treat yourself!</div>
  </main>

  <div class="modal-backdrop" id="cancelModal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-card">
      <h3>Cancel order</h3>
      <p>Please select a reason so we can improve your next visit.</p>
      <select id="cancelReason">
        <option value="">-- Select a reason --</option>
        <option value="Changed my mind">Changed my mind</option>
        <option value="Wrong item ordered">Wrong item ordered</option>
        <option value="Found a better option">Found a better option</option>
        <option value="Others">Others</option>
      </select>
      <div class="modal-actions">
        <button type="button" class="secondary" id="cancelClose">Keep order</button>
        <button type="button" class="primary" id="cancelConfirm">Confirm cancel</button>
      </div>
    </div>
  </div>

  <script type="module">
    import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
    import "../firebase-init.js";

    const auth = getAuth();
    const ordersGrid = document.getElementById('ordersGrid');
    const ordersEmpty = document.getElementById('ordersEmpty');
    const tabs = document.querySelectorAll('.status-tabs button');
    const cancelModal = document.getElementById('cancelModal');
    const cancelReason = document.getElementById('cancelReason');
    const cancelClose = document.getElementById('cancelClose');
    const cancelConfirm = document.getElementById('cancelConfirm');

    let userEmail = null;
    let orders = [];
    let pendingCancelId = null;
    let activeFilter = 'all';

    const STATUS_MAP = new Map([
      ['Pending', 'to-process'],
      ['Processing', 'to-process'],
      ['Confirmed', 'to-process'],
      ['On Delivery', 'to-receive'],
      ['Shipped', 'to-receive'],
      ['Ready for pickup', 'to-receive'],
      ['Completed', 'completed'],
      ['Delivered', 'completed'],
      ['Cancelled', 'completed']
    ]);

    function statusToCategory(status) {
      return STATUS_MAP.get(status) || 'to-process';
    }

    function formatDate(input) {
      if (!input) return '—';
      const date = new Date(input);
      if (Number.isNaN(date.getTime())) {
        return input;
      }
      return date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function renderOrders() {
      ordersGrid.innerHTML = '';
      const filtered = orders.filter(order => activeFilter === 'all' || statusToCategory(order.Status) === activeFilter);

      if (filtered.length === 0) {
        ordersEmpty.hidden = false;
        return;
      }
      ordersEmpty.hidden = true;

      filtered.forEach(order => {
        const card = document.createElement('article');
        card.className = 'order-card';
        const category = statusToCategory(order.Status);
        const imgSrc = order.Image_Path ? `../../../adminSide/products/uploads/${order.Image_Path}` : '../../../Images/logo.png';
        card.innerHTML = `
          <div class="order-summary">
            <img src="${imgSrc}" alt="Order product image">
            <div class="order-meta">
              <h3>Order #${order.Order_ID ?? ''}</h3>
              <span>${formatDate(order.Order_Date ?? order.created_at)}</span>
              <span>Total Items: ${order.Total_Items ?? order.Quantity ?? 1}</span>
              <span class="badge">${order.Status || 'Processing'}</span>
            </div>
          </div>
          <div class="order-actions">
            <a href="../INVOICE/orderDetails.php?order_id=${order.Order_ID ?? ''}">View details</a>
            ${category === 'to-process' ? '<button type="button" class="danger" data-action="cancel" data-id="' + (order.Order_ID ?? '') + '">Cancel order</button>' : ''}
          </div>
        `;
        ordersGrid.appendChild(card);
      });
    }

    function attachTabHandlers() {
      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(btn => btn.classList.remove('active'));
          tab.classList.add('active');
          activeFilter = tab.dataset.filter;
          renderOrders();
        });
      });
    }

    function fetchOrders(email) {
      return fetch(`../../../PHP/order_api.php?action=list&email=${encodeURIComponent(email)}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            throw new Error(data.error);
          }
          orders = Array.isArray(data) ? data : [];
          renderOrders();
        })
        .catch(() => {
          orders = [];
          renderOrders();
        });
    }

    ordersGrid.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) return;
      if (target.dataset.action === 'cancel') {
        pendingCancelId = target.dataset.id;
        cancelModal.classList.add('show');
        cancelModal.setAttribute('aria-hidden', 'false');
      }
    });

    cancelClose.addEventListener('click', () => {
      pendingCancelId = null;
      cancelReason.value = '';
      cancelModal.classList.remove('show');
      cancelModal.setAttribute('aria-hidden', 'true');
    });

    cancelConfirm.addEventListener('click', () => {
      if (!cancelReason.value) {
        alert('Please select a reason before cancelling.');
        return;
      }
      if (pendingCancelId) {
        alert(`Order #${pendingCancelId} cancelled for reason: ${cancelReason.value}`);
      }
      cancelClose.click();
    });

    cancelModal.addEventListener('click', (event) => {
      if (event.target === cancelModal) {
        cancelClose.click();
      }
    });

    onAuthStateChanged(auth, user => {
      if (user) {
        userEmail = user.email;
        fetchOrders(userEmail);
      }
    });

    attachTabHandlers();
    renderOrders();
  </script>
</body>
</html>
