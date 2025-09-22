<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$activePage = 'walkin-order';
$pageTitle = "New Walk-in Order - Cindy's Bakeshop";

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="page-header">
    <h1>Create Walk-in Order</h1>
    <a href="edit-profile.php" class="user-info">
      <span>Admin</span>
      <img src="https://i.pravatar.cc/80" alt="Admin avatar">
    </a>
  </div>

  <div class="walkin-builder">
    <form id="walkinOrderForm" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
      <div class="builder-grid">
        <section class="builder-card">
          <div class="card-header">
            <h2>Customer</h2>
            <p>Search an existing customer or capture details for a new guest.</p>
          </div>
          <div class="customer-mode">
            <label>
              <input type="radio" name="customer_mode" value="existing" checked>
              Existing customer
            </label>
            <label>
              <input type="radio" name="customer_mode" value="new">
              New customer
            </label>
          </div>
          <div class="customer-section" id="existingCustomerSection">
            <label for="customerSearch">Search</label>
            <input type="search" id="customerSearch" placeholder="Search by name or email">
            <p class="helper-text">Start typing to load matching accounts.</p>
            <ul class="search-results" id="customerResults" aria-live="polite"></ul>
            <div class="customer-summary empty" id="customerSummary">
              <p>No customer selected.</p>
            </div>
          </div>
          <div class="customer-section is-hidden" id="newCustomerSection">
            <label for="newCustomerName">Name</label>
            <input type="text" id="newCustomerName" placeholder="Customer name">
            <label for="newCustomerEmail">Email <span class="helper-text">(optional)</span></label>
            <input type="email" id="newCustomerEmail" placeholder="customer@example.com">
            <label for="newCustomerAddress">Address <span class="helper-text">(optional)</span></label>
            <textarea id="newCustomerAddress" rows="3" placeholder="Street, barangay, city"></textarea>
          </div>
        </section>

        <section class="builder-card">
          <div class="card-header">
            <h2>Fulfillment &amp; Payment</h2>
            <p>Record how the order will be fulfilled and paid.</p>
          </div>
          <div class="form-field">
            <label for="fulfillmentType">Fulfillment type</label>
            <select id="fulfillmentType">
              <option value="Pick up">Pick up</option>
              <option value="Delivery">Delivery</option>
            </select>
          </div>
          <div class="form-field">
            <label for="orderStatus">Order status</label>
            <select id="orderStatus">
              <?php foreach (['Pending', 'Confirmed', 'Shipped', 'Delivered'] as $status): ?>
                <option value="<?= htmlspecialchars($status); ?>"><?= htmlspecialchars($status); ?></option>
              <?php endforeach; ?>
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
            <label for="paymentAmount">Amount collected</label>
            <input type="number" id="paymentAmount" min="0" step="0.01" value="0.00">
          </div>
          <div class="form-field">
            <label for="referenceNumber">Reference # <span class="helper-text">(optional)</span></label>
            <input type="text" id="referenceNumber" maxlength="100" placeholder="Receipt or transaction reference">
          </div>
        </section>

        <section class="builder-card builder-wide">
          <div class="card-header">
            <h2>Products &amp; Summary</h2>
            <p>Search the catalogue, add items to the order, and confirm quantities.</p>
          </div>
          <div class="product-toolbar">
            <div class="form-field">
              <label for="productSearch">Product search</label>
              <input type="search" id="productSearch" placeholder="Search by name or category">
            </div>
            <label class="inline-checkbox">
              <input type="checkbox" id="inStockOnly" checked>
              In stock only
            </label>
          </div>
          <div class="product-layout">
            <div class="product-list" id="productResults" aria-live="polite"></div>
            <div class="order-summary">
              <div class="order-summary-header">
                <h3>Order summary</h3>
                <p class="helper-text">Adjust quantities before creating the order.</p>
              </div>
              <div class="order-summary-body">
                <table class="summary-table">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th style="width:120px;">Qty</th>
                      <th>Price</th>
                      <th>Subtotal</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody id="orderSummaryBody">
                    <tr class="summary-empty">
                      <td colspan="5">No items selected yet.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="order-summary-footer">
                <div class="total-row">
                  <span>Total</span>
                  <span id="orderTotal">₱0.00</span>
                </div>
                <div id="formErrors" class="form-messages is-hidden" role="alert"></div>
                <button type="submit" class="btn btn-primary btn-submit">Create walk-in order</button>
              </div>
            </div>
          </div>
        </section>
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
  const newSection = document.getElementById('newCustomerSection');
  const customerSearchInput = document.getElementById('customerSearch');
  const customerResults = document.getElementById('customerResults');
  const customerSummary = document.getElementById('customerSummary');
  const newCustomerName = document.getElementById('newCustomerName');
  const newCustomerEmail = document.getElementById('newCustomerEmail');
  const newCustomerAddress = document.getElementById('newCustomerAddress');
  const fulfillmentTypeSelect = document.getElementById('fulfillmentType');
  const orderStatusSelect = document.getElementById('orderStatus');
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

  const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

  const selectedItems = new Map();
  let selectedCustomer = null;
  let customerSearchTimer = null;
  let productSearchTimer = null;
  let paymentAmountTouched = false;

  function setMessage(target, type, text) {
    if (!target) return;
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
    target.textContent = '';
    target.classList.add('is-hidden');
    target.classList.remove('is-success', 'is-error');
  }

  function renderCustomerSummary() {
    if (!customerSummary) return;
    customerSummary.innerHTML = '';
    customerSummary.classList.remove('empty');
    if (!selectedCustomer) {
      customerSummary.classList.add('empty');
      customerSummary.innerHTML = '<p>No customer selected.</p>';
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'customer-details';

    const name = document.createElement('strong');
    name.textContent = selectedCustomer.name;
    wrapper.appendChild(name);

    if (selectedCustomer.email) {
      const email = document.createElement('span');
      email.textContent = selectedCustomer.email;
      wrapper.appendChild(email);
    }

    if (selectedCustomer.address) {
      const address = document.createElement('span');
      address.textContent = selectedCustomer.address;
      wrapper.appendChild(address);
    }

    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'btn btn-muted btn-small';
    clearBtn.textContent = 'Clear selection';
    clearBtn.addEventListener('click', () => {
      selectedCustomer = null;
      renderCustomerSummary();
    });

    customerSummary.appendChild(wrapper);
    customerSummary.appendChild(clearBtn);
  }

  function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.dataset.id = String(product.id);

    const title = document.createElement('div');
    title.className = 'product-title';
    title.innerHTML = `<strong>${product.name}</strong><span>${product.category || 'Uncategorised'}</span>`;
    card.appendChild(title);

    const meta = document.createElement('div');
    meta.className = 'product-meta';
    const stockClass = product.stock <= 0 ? 'out' : product.stock < 10 ? 'low' : '';
    const stockText = product.stock > 0 ? `Stock: ${product.stock}` : 'Out of stock';
    meta.innerHTML = `<span>${peso.format(product.price)}</span><span class="stock ${stockClass}">${stockText}</span>`;
    card.appendChild(meta);

    const actions = document.createElement('div');
    actions.className = 'product-actions';

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'btn btn-secondary';
    addButton.textContent = 'Add';
    addButton.disabled = product.stock <= 0;
    addButton.addEventListener('click', () => {
      const existing = selectedItems.get(product.id) || { ...product, quantity: 0 };
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

    actions.appendChild(addButton);
    card.appendChild(actions);
    return card;
  }

  function renderProductResults(products) {
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
    summaryBody.innerHTML = '';
    let total = 0;
    if (!selectedItems.size) {
      const row = document.createElement('tr');
      row.className = 'summary-empty';
      const cell = document.createElement('td');
      cell.colSpan = 5;
      cell.textContent = 'No items selected yet.';
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
  }

  async function fetchProducts(term) {
    try {
      const response = await callApi({
        action: 'search_products',
        query: term,
        limit: '20',
        in_stock_only: inStockOnlyCheckbox.checked ? '1' : '0'
      });
      renderProductResults(response.products || []);
    } catch (error) {
      console.error(error);
      renderProductResults([]);
    }
  }

  async function fetchCustomers(term) {
    if (term.length === 0) {
      customerResults.innerHTML = '';
      return;
    }
    try {
      const response = await callApi({ action: 'search_customers', query: term, limit: '10' });
      renderCustomerResults(response.customers || []);
    } catch (error) {
      console.error(error);
    }
  }

  function renderCustomerResults(customers) {
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
      throw new Error(text || 'Unexpected response from server.');
    }
    if (!response.ok) {
      throw new Error(json.message || 'Request failed.');
    }
    return json;
  }

  async function submitForm(event) {
    event.preventDefault();
    clearMessage(formErrors);
    clearMessage(messages);

    const mode = form.querySelector('input[name="customer_mode"]:checked')?.value || 'existing';
    if (mode === 'existing' && !selectedCustomer) {
      setMessage(formErrors, 'error', 'Select a customer before posting the order.');
      return;
    }
    if (mode === 'new') {
      if (newCustomerName.value.trim() === '') {
        setMessage(formErrors, 'error', 'Customer name is required.');
        newCustomerName.focus();
        return;
      }
      if (newCustomerEmail.value.trim() && !/^.+@.+\..+$/.test(newCustomerEmail.value.trim())) {
        setMessage(formErrors, 'error', 'Enter a valid email address or leave it blank.');
        newCustomerEmail.focus();
        return;
      }
      if (fulfillmentTypeSelect.value === 'Delivery' && newCustomerAddress.value.trim() === '') {
        setMessage(formErrors, 'error', 'Provide a delivery address for delivery orders.');
        newCustomerAddress.focus();
        return;
      }
    }
    if (!selectedItems.size) {
      setMessage(formErrors, 'error', 'Add at least one product to the order.');
      return;
    }

    const items = Array.from(selectedItems.values()).map((item) => ({
      product_id: item.id,
      quantity: item.quantity
    }));

    const payload = {
      action: 'create_order',
      customer_mode: mode,
      fulfillment_type: fulfillmentTypeSelect.value,
      order_status: orderStatusSelect.value,
      payment_method: paymentMethodSelect.value,
      payment_status: paymentStatusSelect.value,
      payment_amount: paymentAmountInput.value || '0',
      reference_number: referenceNumberInput.value.trim(),
      items: JSON.stringify(items)
    };

    if (mode === 'existing' && selectedCustomer) {
      payload.user_id = String(selectedCustomer.id);
    } else if (mode === 'new') {
      payload.customer_name = newCustomerName.value.trim();
      payload.customer_email = newCustomerEmail.value.trim();
      payload.customer_address = newCustomerAddress.value.trim();
    }

    try {
      const result = await callApi(payload);
      if (!result.success) {
        throw new Error(result.message || 'Failed to create order.');
      }

      clearMessage(formErrors);
      messages.innerHTML = '';
      messages.classList.remove('is-error');
      messages.classList.add('is-success');
      messages.classList.remove('is-hidden');

      const successText = document.createElement('p');
      successText.innerHTML = `Order <strong>#${String(result.order_id).padStart(5, '0')}</strong> created successfully.`;
      messages.appendChild(successText);

      const linkParagraph = document.createElement('p');
      const orderLink = document.createElement('a');
      orderLink.href = ordersUrl;
      orderLink.textContent = 'Go to Manage Orders';
      orderLink.className = 'link-order';
      linkParagraph.appendChild(orderLink);
      messages.appendChild(linkParagraph);

      selectedItems.clear();
      renderSelectedItems();

      if (mode === 'existing') {
        selectedCustomer = null;
        renderCustomerSummary();
        customerSearchInput.value = '';
      } else {
        newCustomerName.value = '';
        newCustomerEmail.value = '';
        newCustomerAddress.value = '';
      }

      paymentAmountInput.value = '0.00';
      paymentAmountTouched = false;
      referenceNumberInput.value = '';
      orderStatusSelect.value = 'Pending';
      paymentStatusSelect.value = 'Paid';
      paymentMethodSelect.value = 'Cash';
    } catch (error) {
      console.error(error);
      setMessage(messages, 'error', error.message || 'Something went wrong while creating the order.');
    }
  }

  function toggleCustomerSections(mode) {
    if (mode === 'existing') {
      existingSection.classList.remove('is-hidden');
      newSection.classList.add('is-hidden');
      customerSearchInput.disabled = false;
      newCustomerName.disabled = true;
      newCustomerEmail.disabled = true;
      newCustomerAddress.disabled = true;
      customerSearchInput.focus();
    } else {
      existingSection.classList.add('is-hidden');
      newSection.classList.remove('is-hidden');
      customerSearchInput.disabled = true;
      newCustomerName.disabled = false;
      newCustomerEmail.disabled = false;
      newCustomerAddress.disabled = false;
      customerResults.innerHTML = '';
      selectedCustomer = null;
      renderCustomerSummary();
      newCustomerName.focus();
    }
  }

  customerModeRadios.forEach((radio) => {
    radio.addEventListener('change', () => {
      toggleCustomerSections(radio.value);
    });
  });

  customerSearchInput.addEventListener('input', () => {
    if (customerSearchTimer) {
      clearTimeout(customerSearchTimer);
    }
    const term = customerSearchInput.value.trim();
    customerSearchTimer = setTimeout(() => fetchCustomers(term), 250);
  });

  productSearchInput.addEventListener('input', () => {
    if (productSearchTimer) {
      clearTimeout(productSearchTimer);
    }
    const term = productSearchInput.value.trim();
    productSearchTimer = setTimeout(() => fetchProducts(term), 200);
  });

  inStockOnlyCheckbox.addEventListener('change', () => {
    fetchProducts(productSearchInput.value.trim());
  });

  paymentStatusSelect.addEventListener('change', () => {
    if (paymentStatusSelect.value === 'Paid' && !paymentAmountTouched) {
      const total = Array.from(selectedItems.values()).reduce((sum, item) => sum + item.price * item.quantity, 0);
      paymentAmountInput.value = total.toFixed(2);
    } else if (!paymentAmountTouched) {
      paymentAmountInput.value = '0.00';
    }
  });

  paymentAmountInput.addEventListener('input', () => {
    paymentAmountTouched = true;
  });

  form.addEventListener('submit', submitForm);

  toggleCustomerSections('existing');
  renderSelectedItems();
  renderCustomerSummary();
  fetchProducts('');
})();
</script>
JS;

$extraScripts = sprintf($scriptTemplate, $csrfTokenJson, $apiUrlJson, $ordersUrlJson);

include 'includes/footer.php';
