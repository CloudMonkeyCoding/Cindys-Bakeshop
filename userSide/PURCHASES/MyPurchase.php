<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Purchases - Cindy's Bakeshop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../styles.css" />
</head>
<body class="purchases-page">
  <?php include __DIR__ . '/../topbar.php'; ?>

  <div class="container">
    <h2>MY PURCHASES</h2>
    <div class="tabs">
      <button class="tab active" onclick="showTab('to-process')">🕒 To Process</button>
      <button class="tab" onclick="showTab('to-receive')">📦 To Receive</button>
      <button class="tab" onclick="showTab('completed')">✅ Completed</button>
    </div>
    <div id="to-process" class="tab-content active">
      <div id="cart-items-container"></div>
    </div>
    <div id="to-receive" class="tab-content"></div>
    <div id="completed" class="tab-content"></div>
    <button class="back-btn" onclick="history.back()">Back</button>
  </div>

  <div class="modal" id="cancelModal">
    <div class="modal-content">
      <h3>Cancel Order</h3>
      <p>Please select a reason for cancellation:</p>
      <select id="cancelReason">
        <option value="">-- Select a reason --</option>
        <option value="Changed my mind">Changed my mind</option>
        <option value="Wrong item ordered">Wrong item ordered</option>
        <option value="Found a better option">Found a better option</option>
        <option value="Others">Others</option>
      </select>
      <button onclick="confirmCancel()" style="margin-top: 15px;">Yes, Cancel</button>
      <button onclick="closeCancelModal()" style="margin-left:10px; margin-top: 15px;">No</button>
    </div>
  </div>

  <script type="module">
    import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
    import "../firebase-init.js";

    let cancelIndex = null;

    function showTab(id) {
      document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
      document.getElementById(id).classList.add('active');
      const index = ['to-process', 'to-receive', 'completed'].indexOf(id);
      document.querySelectorAll('.tab')[index].classList.add('active');
    }

    let userEmail = null;
    const auth = getAuth();
    onAuthStateChanged(auth, user => {
      if (user) {
        userEmail = user.email;
        loadCartItems();
      }
    });

    function loadCartItems() {
      if (!userEmail) return;
      const container = document.getElementById('cart-items-container');
      fetch(`../../PHP/order_api.php?action=list&email=${encodeURIComponent(userEmail)}`)
        .then(res => res.json())
        .then(orders => {
          if (!orders || orders.length === 0) {
            container.innerHTML = '<p style="text-align:center;">No items in your cart.</p>';
            return;
          }
          container.innerHTML = '';
          orders.forEach(order => {
            const div = document.createElement('div');
            div.classList.add('order-card');
            const imgSrc = order.Image_Path ? '../../adminSide/products/uploads/' + order.Image_Path : '../Images/cindy_s logo.png';
            div.innerHTML = `
              <div class="order-info">
                <img src="${imgSrc}" class="order-img" alt="Product">
                <div class="order-details">
                  <b>Order #${order.Order_ID}</b><br>
                  Status: ${order.Status}<br>
                  Order Date: ${order.Order_Date}
                </div>
              </div>
              <div class="order-action">
                <p>&nbsp;</p>
              </div>
            `;
            container.appendChild(div);
          });
        });
    }

    function openCancelModal(index) {
      cancelIndex = index;
      document.getElementById("cancelModal").style.display = "flex";
    }

    function closeCancelModal() {
      cancelIndex = null;
      document.getElementById("cancelModal").style.display = "none";
      document.getElementById("cancelReason").value = "";
    }

    function confirmCancel() {
      const reason = document.getElementById("cancelReason").value;
      if (!reason) {
        alert("Please select a reason before cancelling.");
        return;
      }
      if (cancelIndex !== null) {
        closeCancelModal();
        alert("Order cancelled for reason: " + reason);
      }
    }

    window.addEventListener('DOMContentLoaded', loadCartItems);
  </script>
</body>
</html>
