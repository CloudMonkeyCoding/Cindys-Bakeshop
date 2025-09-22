<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$activePage = 'walkin-order';
$pageTitle = "Walk-in POS - Cindy's Bakeshop";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Walk-in POS</h1>
    <a href="edit-profile.php" class="user-info">
      <span>Admin</span>
      <img src="https://i.pravatar.cc/80" alt="Admin avatar">
    </a>
  </div>

  <div class="pos-container">
    <form id="walkinOrderForm" class="pos-form" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
      <input type="hidden" id="orderStatus" value="Confirmed">

      <div class="pos-layout">
        <section class="pos-products">
          <div class="pos-toolbar">
            <input type="search" id="productSearch" placeholder="Search products or categories">
            <label class="pos-checkbox">
              <input type="checkbox" id="inStockOnly" checked>
              In stock only
            </label>
          </div>
          <div class="pos-product-grid" id="productResults" aria-live="polite"></div>
        </section>

        <aside class="pos-sidebar">
          <section class="pos-card">
            <h2>Cart</h2>
            <div class="pos-cart-table-wrapper">
              <table class="summary-table">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th style="width:110px;">Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="orderSummaryBody">
                  <tr class="summary-empty">
                    <td colspan="5">No items added.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <section class="pos-card">
            <h2>Customer</h2>
            <div class="pos-customer-mode">
              <label class="pos-radio">
                <input type="radio" name="customer_mode" value="guest" checked>
                Walk-in
              </label>
              <label class="pos-radio">
                <input type="radio" name="customer_mode" value="existing">
                Existing
              </label>
              <label class="pos-radio">
                <input type="radio" name="customer_mode" value="new">
                New
              </label>
            </div>
            <div id="customerSummary" class="pos-customer-summary">
              <p>This sale will be saved as a walk-in guest.</p>
            </div>
            <div id="existingCustomerSection" class="pos-customer-fields is-hidden">
              <input type="search" id="customerSearch" placeholder="Search by name or email" disabled>
              <ul id="customerResults" class="pos-search-results" aria-live="polite"></ul>
            </div>
            <div id="newCustomerSection" class="pos-customer-fields is-hidden">
              <input type="text" id="newCustomerName" placeholder="Customer name">
              <input type="email" id="newCustomerEmail" placeholder="Email (optional)">
              <textarea id="newCustomerAddress" placeholder="Address (optional)" rows="2"></textarea>
            </div>
          </section>

          <section class="pos-card pos-checkout">
            <h2>Payment</h2>
            <div class="form-field">
              <label for="fulfillmentType">Fulfillment</label>
              <select id="fulfillmentType">
                <option value="Pick up">Pick up</option>
                <option value="Delivery">Delivery</option>
              </select>
            </div>
            <div class="form-field">
              <label for="paymentMethod">Payment method</label>
              <select id="paymentMethod">
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
                <option value="Card">Card</option>
                <option value="Bank transfer">Bank transfer</option>
              </select>
            </div>
            <div class="form-field">
              <label for="paymentStatus">Payment status</label>
              <select id="paymentStatus">
                <option value="Paid" selected>Paid</option>
                <option value="Pending">Pending</option>
                <option value="Partially Paid">Partially Paid</option>
              </select>
            </div>
            <div class="form-field">
              <label for="paymentAmount">Amount paid</label>
              <input type="number" id="paymentAmount" min="0" step="0.01" value="0.00">
            </div>
            <div class="form-field">
              <label for="referenceNumber">Reference # (optional)</label>
              <input type="text" id="referenceNumber" maxlength="100">
            </div>
            <div class="pos-total">
              <span>Total</span>
              <strong id="orderTotal">₱0.00</strong>
            </div>
            <div id="formErrors" class="form-messages is-hidden" role="alert"></div>
            <button type="submit" class="btn btn-primary pos-submit">Complete order</button>
          </section>
        </aside>
      </div>
    </form>
    <div id="walkinMessages" class="form-messages is-hidden" role="alert"></div>
  </div>
</div>

<?php
$csrfTokenJson = json_encode($_SESSION['csrf_token']);
$apiUrlJson = json_encode('api/walkin_order_actions.php');
$ordersUrlJson = json_encode('orders.php');
$scriptTemplate = <<<'JS'
<script>
(() => {
  const csrfToken = %s;
  const apiUrl = %s;
  const ordersUrl = %s;
  const form = document.getElementById('walkinOrderForm');
  const customerModeRadios = form.querySelectorAll('input[name="customer_mode"]');
  const existingSection = document.getElementById('existingCustomerSection');
  const newCustomerSection = document.getElementById('newCustomerSection');
  const customerSearchInput = document.getElementById('customerSearch');
  const customerResults = document.getElementById('customerResults');
  const customerSummary = document.getElementById('customerSummary');
  const newCustomerNameInput = document.getElementById('newCustomerName');
  const newCustomerEmailInput = document.getElementById('newCustomerEmail');
  const newCustomerAddressInput = document.getElementById('newCustomerAddress');
  let currentCustomerMode = 'guest';
  const fulfillmentTypeSelect = document.getElementById('fulfillmentType');
  const orderStatusInput = document.getElementById('orderStatus');
  const paymentMethodSelect = document.getElementById('paymentMethod');
  const paymentStatusSelect = document.getElementById('paymentStatus');
  const paymentAmountInput = document.getElementById('paymentAmount');
  const referenceNumberInput = document.getElementById('referenceNumber');
  const productSearchInput = document.getElementById('productSearch');
  const inStockOnlyCheckbox = document.getElementById('inStockOnly');
  const productResultsContainer = document.getElementById('productResults');
  const summaryBody = document.getElementById('orderSummaryBody');
  const orderTotalLabel = document.getElementById('orderTotal');
  const formErrors = document.getElementById('formErrors');
  const messages = document.getElementById('walkinMessages');

  const hasConsole = typeof console !== 'undefined';
  const log = (level, ...args) => {
    if (!hasConsole) return;
    const method = console[level] || console.log;
    method.call(console, '[Walk-in POS]', ...args);
  };

  log('debug', 'POS script initialised');

  const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

  const selectedItems = new Map();
  let selectedCustomer = null;
  let customerSearchTimer = null;
  let productSearchTimer = null;
  let paymentAmountTouched = false;

  function setMessage(target, type, text) {
    if (!target) return;
    log('debug', 'Setting message', { targetId: target.id || null, type, text });
    target.textContent = text;
    target.classList.remove('is-hidden', 'is-success', 'is-error');
    if (type === 'success') {
      target.classList.add('is-success');
    } else if (type === 'error') {
      target.classList.add('is-error');
    }
  }

  function clearMessage(target) {
    if (!target) return;
    log('debug', 'Clearing message', { targetId: target.id || null });
    target.textContent = '';
    target.classList.add('is-hidden');
    target.classList.remove('is-success', 'is-error');
  }

  function renderCustomerSummary() {
    if (!customerSummary) return;
    log('debug', 'Rendering customer summary', {
      mode: currentCustomerMode,
      selectedCustomer,
      newCustomerDraft: {
        name: newCustomerNameInput ? newCustomerNameInput.value : '',
        email: newCustomerEmailInput ? newCustomerEmailInput.value : '',
        address: newCustomerAddressInput ? newCustomerAddressInput.value : '',
      },
    });
    customerSummary.innerHTML = '';
    if (currentCustomerMode === 'guest') {
      customerSummary.innerHTML = '<p>This sale will be saved as a walk-in guest.</p>';
      return;
    }

    if (currentCustomerMode === 'existing') {
      if (!selectedCustomer) {
        customerSummary.innerHTML = '<p>Select an account to attach (optional).</p>';
        return;
      }
      const list = document.createElement('div');
      list.className = 'customer-details';
      const name = document.createElement('strong');
      name.textContent = selectedCustomer.name;
      list.appendChild(name);
      if (selectedCustomer.email) {
        const email = document.createElement('span');
        email.textContent = selectedCustomer.email;
        list.appendChild(email);
      }
      if (selectedCustomer.address) {
        const address = document.createElement('span');
        address.textContent = selectedCustomer.address;
        list.appendChild(address);
      }
      const clearBtn = document.createElement('button');
      clearBtn.type = 'button';
      clearBtn.className = 'btn btn-muted btn-small';
      clearBtn.textContent = 'Clear';
      clearBtn.addEventListener('click', () => {
        selectedCustomer = null;
        if (customerResults) {
          customerResults.innerHTML = '';
        }
        if (customerSearchInput) {
          customerSearchInput.value = '';
          customerSearchInput.focus();
        }
        renderCustomerSummary();
      });
      customerSummary.appendChild(list);
      customerSummary.appendChild(clearBtn);
      return;
    }

    if (currentCustomerMode === 'new') {
      const nameValue = newCustomerNameInput ? newCustomerNameInput.value.trim() : '';
      const emailValue = newCustomerEmailInput ? newCustomerEmailInput.value.trim() : '';
      const addressValue = newCustomerAddressInput ? newCustomerAddressInput.value.trim() : '';
      if (!nameValue) {
        customerSummary.innerHTML = '<p>Enter the customer details to create a new account.</p>';
        return;
      }
      const info = document.createElement('div');
      info.className = 'customer-details';
      const nameEl = document.createElement('strong');
      nameEl.textContent = nameValue;
      info.appendChild(nameEl);
      if (emailValue) {
        const emailEl = document.createElement('span');
        emailEl.textContent = emailValue;
        info.appendChild(emailEl);
      }
      if (addressValue) {
        const addressEl = document.createElement('span');
        addressEl.textContent = addressValue;
        info.appendChild(addressEl);
      }
      customerSummary.appendChild(info);
    }
  }

  function createProductCard(product) {
    const card = document.createElement('button');
    card.type = 'button';
    card.className = 'pos-product-card';
    card.dataset.id = String(product.id);

    const header = document.createElement('div');
    header.className = 'pos-product-header';
    const name = document.createElement('strong');
    name.textContent = product.name;
    header.appendChild(name);
    const category = document.createElement('span');
    category.textContent = product.category || 'Uncategorised';
    header.appendChild(category);
    card.appendChild(header);

    const meta = document.createElement('div');
    meta.className = 'pos-product-meta';
    const price = document.createElement('span');
    price.textContent = peso.format(product.price);
    meta.appendChild(price);

    const stock = document.createElement('span');
    const stockClass = product.stock <= 0 ? 'out' : product.stock < 10 ? 'low' : 'ok';
    stock.className = `pos-product-stock ${stockClass}`;
    stock.textContent = product.stock > 0 ? `Stock: ${product.stock}` : 'Out of stock';
    meta.appendChild(stock);
    card.appendChild(meta);

    card.disabled = product.stock <= 0;
    card.addEventListener('click', () => {
      log('debug', 'Product card clicked', { product });
      const existing = selectedItems.get(product.id) || { id: product.id, name: product.name, price: product.price, stock: product.stock, quantity: 0 };
      if (existing.quantity >= product.stock) {
        setMessage(formErrors, 'error', `Only ${product.stock} piece(s) of ${product.name} available.`);
        return;
      }
      existing.quantity += 1;
      existing.stock = product.stock;
      selectedItems.set(product.id, existing);
      renderSelectedItems();
      clearMessage(formErrors);
    });

    return card;
  }

  function renderProductResults(products) {
    log('debug', 'Rendering product results', { count: products.length });
    productResultsContainer.innerHTML = '';
    if (!products.length) {
      const empty = document.createElement('div');
      empty.className = 'empty-state';
      empty.textContent = 'No products matched the search.';
      productResultsContainer.appendChild(empty);
      return;
    }
    products.forEach((product) => {
      productResultsContainer.appendChild(createProductCard(product));
    });
  }

  function updateQuantity(id, nextQuantity) {
    log('debug', 'Update quantity requested', { id, nextQuantity });
    const item = selectedItems.get(id);
    if (!item) return;
    if (nextQuantity < 1) {
      nextQuantity = 1;
    }
    if (nextQuantity > item.stock) {
      nextQuantity = item.stock;
      setMessage(formErrors, 'error', `Only ${item.stock} piece(s) of ${item.name} available.`);
    } else {
      clearMessage(formErrors);
    }
    item.quantity = nextQuantity;
    selectedItems.set(id, item);
    renderSelectedItems();
  }

  function renderSelectedItems() {
    log('debug', 'Rendering selected items', { itemCount: selectedItems.size });
    summaryBody.innerHTML = '';
    let total = 0;
    if (!selectedItems.size) {
      const row = document.createElement('tr');
      row.className = 'summary-empty';
      const cell = document.createElement('td');
      cell.colSpan = 5;
      cell.textContent = 'No items added.';
      row.appendChild(cell);
      summaryBody.appendChild(row);
    } else {
      selectedItems.forEach((item, id) => {
        const row = document.createElement('tr');
        row.dataset.id = String(id);

        const nameCell = document.createElement('td');
        nameCell.className = 'summary-name';
        nameCell.textContent = item.name;
        row.appendChild(nameCell);

        const qtyCell = document.createElement('td');
        const qtyWrapper = document.createElement('div');
        qtyWrapper.className = 'quantity-control';

        const minusBtn = document.createElement('button');
        minusBtn.type = 'button';
        minusBtn.className = 'qty-btn';
        minusBtn.textContent = '−';
        minusBtn.addEventListener('click', () => updateQuantity(id, item.quantity - 1));
        qtyWrapper.appendChild(minusBtn);

        const qtyInput = document.createElement('input');
        qtyInput.type = 'number';
        qtyInput.min = '1';
        qtyInput.max = String(item.stock);
        qtyInput.value = String(item.quantity);
        qtyInput.addEventListener('change', () => {
          const next = Math.max(1, Math.min(item.stock, parseInt(qtyInput.value, 10) || 1));
          updateQuantity(id, next);
        });
        qtyWrapper.appendChild(qtyInput);

        const plusBtn = document.createElement('button');
        plusBtn.type = 'button';
        plusBtn.className = 'qty-btn';
        plusBtn.textContent = '+';
        plusBtn.addEventListener('click', () => updateQuantity(id, item.quantity + 1));
        qtyWrapper.appendChild(plusBtn);

        qtyCell.appendChild(qtyWrapper);
        row.appendChild(qtyCell);

        const priceCell = document.createElement('td');
        priceCell.textContent = peso.format(item.price);
        row.appendChild(priceCell);

        const subtotal = item.price * item.quantity;
        total += subtotal;
        const subtotalCell = document.createElement('td');
        subtotalCell.textContent = peso.format(subtotal);
        row.appendChild(subtotalCell);

        const removeCell = document.createElement('td');
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-muted btn-small';
        removeBtn.textContent = 'Remove';
        removeBtn.addEventListener('click', () => {
          log('debug', 'Removing item from cart', { id, name: item.name });
          selectedItems.delete(id);
          renderSelectedItems();
        });
        removeCell.appendChild(removeBtn);
        row.appendChild(removeCell);

        summaryBody.appendChild(row);
      });
    }

    orderTotalLabel.textContent = peso.format(total);
    if (!paymentAmountTouched || paymentStatusSelect.value === 'Paid') {
      paymentAmountInput.value = total.toFixed(2);
    }
    log('debug', 'Cart totals updated', {
      total,
      paymentStatus: paymentStatusSelect.value,
      items: Array.from(selectedItems.values()).map((item) => ({
        id: item.id,
        name: item.name,
        quantity: item.quantity,
        stock: item.stock,
        price: item.price,
      })),
    });
  }

  async function fetchProducts(term) {
    log('debug', 'Fetching products', { term, inStockOnly: inStockOnlyCheckbox.checked });
    try {
      const response = await callApi({
        action: 'search_products',
        query: term,
        limit: '30',
        in_stock_only: inStockOnlyCheckbox.checked ? '1' : '0'
      });
      log('debug', 'Product search response', { count: (response.products || []).length });
      renderProductResults(response.products || []);
    } catch (error) {
      log('error', 'Failed to fetch products', error);
      renderProductResults([]);
    }
  }

  async function fetchCustomers(term) {
    if (term.length === 0) {
      if (customerResults) {
        customerResults.innerHTML = '';
      }
      return;
    }
    try {
      log('debug', 'Fetching customers', { term });
      const response = await callApi({ action: 'search_customers', query: term, limit: '10' });
      log('debug', 'Customer search response', { count: (response.customers || []).length });
      renderCustomerResults(response.customers || []);
    } catch (error) {
      log('error', 'Failed to fetch customers', error);
    }
  }

  function renderCustomerResults(customers) {
    if (!customerResults) return;
    log('debug', 'Rendering customer results', { count: customers.length });
    customerResults.innerHTML = '';
    if (!customers.length) {
      const empty = document.createElement('li');
      empty.className = 'empty-state';
      empty.textContent = 'No customers found.';
      customerResults.appendChild(empty);
      return;
    }
    customers.forEach((customer) => {
      const item = document.createElement('li');
      item.className = 'search-result';
      item.innerHTML = `<strong>${customer.name}</strong><span>${customer.email || 'No email on file'}</span>`;
      item.addEventListener('click', () => {
        log('debug', 'Existing customer selected', customer);
        selectedCustomer = customer;
        customerResults.innerHTML = '';
        renderCustomerSummary();
      });
      customerResults.appendChild(item);
    });
  }

  async function callApi(params) {
    const body = new URLSearchParams(params);
    body.set('csrf_token', csrfToken);
    const debugPayload = {};
    body.forEach((value, key) => {
      debugPayload[key] = key === 'csrf_token' ? '[redacted]' : value;
    });
    log('debug', 'API request payload', debugPayload);
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      body
    });
    const text = await response.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch (error) {
      log('error', 'Failed to parse API response', { text });
      throw new Error(text || 'Unexpected response from server.');
    }
    log('debug', 'API response received', { status: response.status, ok: response.ok, body: json });
    if (!response.ok) {
      log('error', 'API responded with error status', { status: response.status, body: json });
      throw new Error(json.message || 'Request failed.');
    }
    return json;
  }

  async function submitForm(event) {
    event.preventDefault();
    clearMessage(formErrors);
    clearMessage(messages);

    const modeInput = form.querySelector('input[name="customer_mode"]:checked');
    const mode = modeInput ? modeInput.value : 'guest';
    currentCustomerMode = mode;
    log('debug', 'Form submission initiated', {
      mode,
      itemCount: selectedItems.size,
      paymentStatus: paymentStatusSelect.value,
    });

    if (!selectedItems.size) {
      log('warn', 'Submission blocked: no items selected');
      setMessage(formErrors, 'error', 'Add at least one product to the cart.');
      return;
    }

    if (mode === 'existing' && !selectedCustomer) {
      log('warn', 'Submission blocked: existing customer not selected');
      setMessage(formErrors, 'error', 'Select a customer or switch to walk-in.');
      return;
    }

    if (mode === 'new') {
      const nameValue = newCustomerNameInput ? newCustomerNameInput.value.trim() : '';
      const emailValue = newCustomerEmailInput ? newCustomerEmailInput.value.trim() : '';
      if (!nameValue) {
        log('warn', 'Submission blocked: missing new customer name');
        setMessage(formErrors, 'error', 'Enter a customer name or record the sale as a walk-in.');
        return;
      }
      if (newCustomerEmailInput && emailValue && !newCustomerEmailInput.checkValidity()) {
        log('warn', 'Submission blocked: invalid email for new customer', { emailValue });
        newCustomerEmailInput.reportValidity();
        return;
      }
    }

    const items = Array.from(selectedItems.values()).map((item) => ({
      product_id: item.id,
      quantity: item.quantity
    }));
    log('debug', 'Items prepared for submission', { items });

    const payload = {
      action: 'create_order',
      customer_mode: mode,
      fulfillment_type: fulfillmentTypeSelect.value,
      order_status: orderStatusInput ? orderStatusInput.value : 'Confirmed',
      payment_method: paymentMethodSelect.value,
      payment_status: paymentStatusSelect.value,
      payment_amount: paymentAmountInput.value || '0',
      reference_number: referenceNumberInput.value.trim(),
      items: JSON.stringify(items)
    };

    if (mode === 'existing' && selectedCustomer) {
      payload.user_id = String(selectedCustomer.id);
    } else if (mode === 'new') {
      const nameValue = newCustomerNameInput ? newCustomerNameInput.value.trim() : '';
      const emailValue = newCustomerEmailInput ? newCustomerEmailInput.value.trim() : '';
      const addressValue = newCustomerAddressInput ? newCustomerAddressInput.value.trim() : '';
      payload.new_customer_name = nameValue;
      if (emailValue) {
        payload.new_customer_email = emailValue;
      }
      if (addressValue) {
        payload.new_customer_address = addressValue;
      }
    }

    const payloadPreview = Object.assign({ csrf_token: '[redacted]' }, payload);
    log('debug', 'Payload built', payloadPreview);

    try {
      const result = await callApi(payload);
      if (!result.success) {
        throw new Error(result.message || 'Failed to create order.');
      }

      log('debug', 'Order created successfully', result);

      clearMessage(formErrors);
      setMessage(messages, 'success', `Order #${String(result.order_id).padStart(5, '0')} created.`);
      const link = document.createElement('a');
      link.href = ordersUrl;
      link.textContent = 'View orders';
      link.className = 'link-order';
      messages.appendChild(document.createTextNode(' '));
      messages.appendChild(link);

      selectedItems.clear();
      renderSelectedItems();

      selectedCustomer = null;
      if (customerResults) {
        customerResults.innerHTML = '';
      }
      if (customerSearchInput) {
        customerSearchInput.value = '';
      }
      if (newCustomerNameInput) newCustomerNameInput.value = '';
      if (newCustomerEmailInput) newCustomerEmailInput.value = '';
      if (newCustomerAddressInput) newCustomerAddressInput.value = '';
      paymentAmountInput.value = '0.00';
      paymentAmountTouched = false;
      referenceNumberInput.value = '';
      paymentStatusSelect.value = 'Paid';
      paymentMethodSelect.value = 'Cash';
      if (fulfillmentTypeSelect) {
        fulfillmentTypeSelect.value = 'Pick up';
      }
      if (orderStatusInput) {
        orderStatusInput.value = 'Confirmed';
      }

      const guestRadio = form.querySelector('input[name="customer_mode"][value="guest"]');
      if (guestRadio) {
        guestRadio.checked = true;
      }
      toggleCustomerSections('guest');
      log('debug', 'Form reset after successful submission');
    } catch (error) {
      log('error', 'Order submission failed', error);
      setMessage(messages, 'error', error.message || 'Something went wrong while creating the order.');
    }
  }

  function toggleCustomerSections(mode) {
    currentCustomerMode = mode;
    log('debug', 'Customer mode toggled', { mode });

    if (mode === 'existing') {
      if (existingSection) {
        existingSection.classList.remove('is-hidden');
      }
      if (newCustomerSection) {
        newCustomerSection.classList.add('is-hidden');
      }
      if (customerSearchInput) {
        customerSearchInput.disabled = false;
        customerSearchInput.focus();
      }
    } else if (mode === 'new') {
      if (newCustomerSection) {
        newCustomerSection.classList.remove('is-hidden');
      }
      if (existingSection) {
        existingSection.classList.add('is-hidden');
      }
      if (customerSearchInput) {
        customerSearchInput.value = '';
        customerSearchInput.disabled = true;
      }
    } else {
      if (existingSection) {
        existingSection.classList.add('is-hidden');
      }
      if (newCustomerSection) {
        newCustomerSection.classList.add('is-hidden');
      }
      if (customerSearchInput) {
        customerSearchInput.value = '';
        customerSearchInput.disabled = true;
      }
      if (customerResults) {
        customerResults.innerHTML = '';
      }
      selectedCustomer = null;
    }
    renderCustomerSummary();
  }

  customerModeRadios.forEach((radio) => {
    radio.addEventListener('change', () => {
      toggleCustomerSections(radio.value);
    });
  });

  if (customerSearchInput) {
    customerSearchInput.addEventListener('input', () => {
      if (customerSearchTimer) {
        clearTimeout(customerSearchTimer);
      }
      const term = customerSearchInput.value.trim();
      log('debug', 'Customer search term updated', { term });
      customerSearchTimer = setTimeout(() => fetchCustomers(term), 250);
    });
  }

  if (newCustomerNameInput) {
    newCustomerNameInput.addEventListener('input', renderCustomerSummary);
  }
  if (newCustomerEmailInput) {
    newCustomerEmailInput.addEventListener('input', renderCustomerSummary);
  }
  if (newCustomerAddressInput) {
    newCustomerAddressInput.addEventListener('input', renderCustomerSummary);
  }

  productSearchInput.addEventListener('input', () => {
    if (productSearchTimer) {
      clearTimeout(productSearchTimer);
    }
    const term = productSearchInput.value.trim();
    log('debug', 'Product search term updated', { term });
    productSearchTimer = setTimeout(() => fetchProducts(term), 200);
  });

  inStockOnlyCheckbox.addEventListener('change', () => {
    log('debug', 'In-stock filter toggled', { checked: inStockOnlyCheckbox.checked });
    fetchProducts(productSearchInput.value.trim());
  });

  paymentStatusSelect.addEventListener('change', () => {
    log('debug', 'Payment status changed', { status: paymentStatusSelect.value });
    if (paymentStatusSelect.value === 'Paid' && !paymentAmountTouched) {
      const total = Array.from(selectedItems.values()).reduce((sum, item) => sum + item.price * item.quantity, 0);
      paymentAmountInput.value = total.toFixed(2);
    } else if (!paymentAmountTouched) {
      paymentAmountInput.value = '0.00';
    }
  });

  paymentAmountInput.addEventListener('input', () => {
    paymentAmountTouched = true;
    log('debug', 'Payment amount input changed', { value: paymentAmountInput.value });
  });

  form.addEventListener('submit', submitForm);

  toggleCustomerSections('guest');
  renderCustomerSummary();
  renderSelectedItems();
  fetchProducts('');
})();
</script>
JS;

$extraScripts = sprintf($scriptTemplate, $csrfTokenJson, $apiUrlJson, $ordersUrlJson);

include 'includes/footer.php';
