<?php
require_once '../PHP/db_connect.php';
require_once '../PHP/inventory_functions.php';

$activePage = 'inventory-report';
$pageTitle = "Inventory Report - Cindy's Bakeshop";

$inventoryData = [];
$categoryTotals = [];
$totalTracked = 0;
$preOrderCount = 0;
$lowStockCount = 0;
$inventoryLogEntries = [];

if ($pdo) {
    $rows = getInventoryWithProducts($pdo);
    foreach ($rows as $row) {
        $category = $row['Category'] ?? 'Uncategorized';
        $stock = $row['Stock_Quantity'];

        if (!array_key_exists($category, $inventoryData)) {
            $inventoryData[$category] = [];
        }
        if (!array_key_exists($category, $categoryTotals)) {
            $categoryTotals[$category] = 0;
        }

        $inventoryData[$category][] = [
            'id' => $row['Product_ID'],
            'name' => $row['Name'],
            'stock' => $stock
        ];

        $totalTracked++;

        if ($stock === null) {
            $preOrderCount++;
        } else {
            $stockValue = max(0, (int)$stock);
            $categoryTotals[$category] += $stockValue;
            if ($stockValue <= 10) {
                $lowStockCount++;
            }
        }
    }

    ksort($inventoryData);
    ksort($categoryTotals);

    $logRows = getInventoryChangeLog($pdo);
    foreach ($logRows as $logRow) {
        $orderId = isset($logRow['Order_ID']) ? (int)$logRow['Order_ID'] : 0;
        $rawQuantity = isset($logRow['Quantity']) ? (int)$logRow['Quantity'] : 0;
        $quantity = $rawQuantity < 0 ? abs($rawQuantity) : max(0, $rawQuantity);
        $changeValue = $quantity > 0 ? -$quantity : 0;

        $rawDate = $logRow['Order_Date'] ?? null;
        $formattedDate = null;
        if ($rawDate) {
            $timestamp = strtotime($rawDate);
            if ($timestamp !== false) {
                $formattedDate = date('M j, Y', $timestamp);
            }
        }

        $status = $logRow['Status'] ?? 'Pending';
        if (!$status) {
            $status = 'Pending';
        }

        $orderCode = $orderId ? '#' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT) : '—';

        $inventoryLogEntries[] = [
            'order_item_id' => (int)($logRow['Order_Item_ID'] ?? 0),
            'order_id' => $orderId,
            'order_code' => $orderCode,
            'product_id' => (int)($logRow['Product_ID'] ?? 0),
            'product_name' => $logRow['Product_Name'] ?? '',
            'quantity' => $quantity,
            'change' => $changeValue,
            'status' => $status,
            'order_date' => $rawDate,
            'order_date_formatted' => $formattedDate,
            'customer_name' => $logRow['Customer_Name'] ?? '',
            'change_type' => 'Order Placement'
        ];
    }
}

$inventoryJson = json_encode($inventoryData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($inventoryJson === '[]' || $inventoryJson === 'null') {
    $inventoryJson = '{}';
}

$inventoryLogJson = json_encode($inventoryLogEntries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($inventoryLogJson === 'null') {
    $inventoryLogJson = '[]';
}

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Inventory Report</h1>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <button class="btn btn-primary" id="exportInventory">Export Inventory PDF</button>
    </div>
  </div>

  <section class="stats-grid columns-4" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <div class="stat-card">
      <h3>Inventory Categories</h3>
      <div class="value" id="inventoryCategoryCount"><?= number_format(count($inventoryData)); ?></div>
      <div class="meta">Grouped by product type</div>
    </div>
    <div class="stat-card">
      <h3>Tracked SKUs</h3>
      <div class="value" id="inventorySkuCount"><?= number_format($totalTracked); ?></div>
      <div class="meta">Products with stock records</div>
    </div>
    <div class="stat-card">
      <h3>Pre-order Items</h3>
      <div class="value" id="inventoryPreorderCount"><?= number_format($preOrderCount); ?></div>
      <div class="meta">Stock marked as TBD</div>
    </div>
    <div class="stat-card">
      <h3>Low Stock Alerts</h3>
      <div class="value" id="inventoryLowStockCount"><?= number_format($lowStockCount); ?></div>
      <div class="meta">Items at or below 10 pcs</div>
    </div>
  </section>

  <div class="stats-grid columns-4" style="margin-top:24px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Stock by Category</h2>
      <canvas id="categoryChart" height="220"></canvas>
    </div>
  </div>

  <div class="card" style="margin-top:24px;">
    <div class="table-actions">
      <input type="text" id="inventorySearch" placeholder="🔍 Search inventory item...">
    </div>
    <div id="inventoryContainer" class="inventory-groups">
      <noscript>
        <?php if (empty($inventoryData)): ?>
          <p class="table-empty">No inventory records found.</p>
        <?php else: ?>
          <?php foreach ($inventoryData as $category => $items): ?>
            <h2 style="margin-top:24px;">
              <?= htmlspecialchars($category); ?>
            </h2>
            <table class="inventory-table">
              <thead>
                <tr>
                  <th>Item Name</th>
                  <th>Stock</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars($item['name']); ?></td>
                    <td>
                      <?php if ($item['stock'] === null): ?>
                        Pre-order
                      <?php else: ?>
                        <?= number_format(max(0, (int)$item['stock'])); ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endforeach; ?>
        <?php endif; ?>
      </noscript>
    </div>
  </div>

  <div class="card" style="margin-top:24px;">
    <div class="table-actions">
      <h2 style="margin:0;font-size:18px;font-weight:600;">Inventory Change Log</h2>
      <input type="text" id="inventoryLogSearch" placeholder="🔍 Search change log...">
    </div>
    <table class="inventory-log-table" id="inventoryLogTable">
      <thead>
        <tr>
          <th>Date</th>
          <th>Order</th>
          <th>Customer</th>
          <th>Product</th>
          <th>Change</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="inventoryLogBody">
        <?php if (empty($inventoryLogEntries)): ?>
          <tr>
            <td colspan="6" class="table-empty">No inventory changes recorded.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($inventoryLogEntries as $entry): ?>
            <?php
              $changeValue = (int)($entry['change'] ?? 0);
              $changeLabel = ($changeValue > 0 ? '+' : '') . number_format($changeValue) . ' pcs';
              $changeClass = $changeValue > 0 ? 'stock-change-positive' : ($changeValue < 0 ? 'stock-change-negative' : 'stock-change-zero');
              $status = $entry['status'] ?? 'Pending';
              $statusClass = strtolower($status);
              $statusClass = preg_replace('/[^a-z0-9]+/', '-', $statusClass ?? '') ?: 'pending';
              $customerName = $entry['customer_name'] ?? '';
            ?>
            <tr data-order-id="<?= (int)($entry['order_id'] ?? 0); ?>">
              <td><?= htmlspecialchars($entry['order_date_formatted'] ?? $entry['order_date'] ?? '—'); ?></td>
              <td class="log-order"><?= htmlspecialchars($entry['order_code'] ?? ('#' . str_pad((string)($entry['order_id'] ?? 0), 5, '0', STR_PAD_LEFT))); ?></td>
              <td><?= htmlspecialchars($customerName !== '' ? $customerName : '—'); ?></td>
              <td class="log-product"><?= htmlspecialchars($entry['product_name'] ?? ''); ?></td>
              <td><span class="<?= $changeClass; ?>"><?= htmlspecialchars($changeLabel); ?></span></td>
              <td>
                <span class="status-pill status-<?= htmlspecialchars($statusClass); ?>">
                  <?= htmlspecialchars($status); ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$extraScripts = <<<JS
<script>
document.addEventListener('DOMContentLoaded', () => {
  const inventoryEndpoint = '../PHP/inventory_functions.php';
  const inventoryContainer = document.getElementById('inventoryContainer');
  const searchInput = document.getElementById('inventorySearch');
  const exportBtn = document.getElementById('exportInventory');
  const logSearchInput = document.getElementById('inventoryLogSearch');
  const logTableBody = document.getElementById('inventoryLogBody');
  const statsEls = {
    categoryCount: document.getElementById('inventoryCategoryCount'),
    skuCount: document.getElementById('inventorySkuCount'),
    preOrderCount: document.getElementById('inventoryPreorderCount'),
    lowStockCount: document.getElementById('inventoryLowStockCount')
  };

  const numberFormatter = new Intl.NumberFormat();
  let currentSearchTerm = '';
  let currentLogSearchTerm = '';
  let latestUpdateToken = 0;
  let categoryChartInstance = null;
  let inventoryIndex = new Map();
  let inventoryData = {};
  let inventoryLogEntries = [];

  if (searchInput) {
    currentSearchTerm = searchInput.value.toLowerCase();
    searchInput.addEventListener('input', () => {
      currentSearchTerm = searchInput.value.toLowerCase();
      applySearchFilter();
    });
  }

  if (inventoryContainer) {
    inventoryContainer.addEventListener('click', (event) => {
      const button = event.target.closest('.inventory-adjust-btn');
      if (!button || !inventoryContainer.contains(button)) {
        return;
      }
      const control = button.closest('.inventory-stock-control');
      if (!control) {
        return;
      }
      const delta = button.classList.contains('inventory-minus') ? -1 : 1;
      adjustStock(control, delta);
    });

    inventoryContainer.addEventListener('input', (event) => {
      const input = event.target.closest('.inventory-stock-input');
      if (!input) {
        return;
      }
      const control = input.closest('.inventory-stock-control');
      updateStockStatus(control);
    });

    inventoryContainer.addEventListener('change', (event) => {
      const input = event.target.closest('.inventory-stock-input');
      if (!input) {
        return;
      }
      const control = input.closest('.inventory-stock-control');
      persistStock(control);
    });

    inventoryContainer.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        const input = event.target.closest('.inventory-stock-input');
        if (input) {
          event.preventDefault();
          input.blur();
        }
      }
    });
  }

  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      exportInventoryToPDF();
    });
  }

  if (logSearchInput) {
    currentLogSearchTerm = logSearchInput.value.trim().toLowerCase();
    logSearchInput.addEventListener('input', () => {
      currentLogSearchTerm = logSearchInput.value.trim().toLowerCase();
      renderInventoryLogTable();
    });
  }

  function renderInventoryUI() {
    buildInventoryIndex();
    const metrics = computeMetrics(inventoryData);
    updateStats(metrics);
    renderCategoryChart(metrics);
    renderInventoryTable();
  }

  function buildInventoryIndex() {
    inventoryIndex.clear();
    Object.entries(inventoryData).forEach(([category, items]) => {
      if (!Array.isArray(items)) {
        return;
      }
      items.forEach((item, position) => {
        if (!item || typeof item.id === 'undefined') {
          return;
        }
        inventoryIndex.set(Number(item.id), { category, position });
      });
    });
  }

  function renderInventoryTable() {
    if (!inventoryContainer) {
      return;
    }

    inventoryContainer.innerHTML = '';

    const categories = Object.keys(inventoryData);
    if (!categories.length) {
      const emptyMessage = document.createElement('p');
      emptyMessage.className = 'table-empty';
      emptyMessage.textContent = 'No inventory records found.';
      inventoryContainer.appendChild(emptyMessage);
      return;
    }

    categories.sort((a, b) => a.localeCompare(b));

    categories.forEach((category) => {
      const section = document.createElement('div');
      section.className = 'inventory-section';

      const heading = document.createElement('h2');
      heading.textContent = category;
      section.appendChild(heading);

      const table = document.createElement('table');
      table.className = 'inventory-table';

      const thead = document.createElement('thead');
      const headerRow = document.createElement('tr');
      ['Item Name', 'Stock'].forEach((label) => {
        const th = document.createElement('th');
        th.textContent = label;
        headerRow.appendChild(th);
      });
      thead.appendChild(headerRow);
      table.appendChild(thead);

      const tbody = document.createElement('tbody');
      const items = Array.isArray(inventoryData[category]) ? [...inventoryData[category]] : [];
      items.sort((a, b) => (a?.name || '').localeCompare(b?.name || ''));

      items.forEach((item) => {
        const row = document.createElement('tr');
        row.dataset.itemName = (item?.name || '').toLowerCase();

        const nameCell = document.createElement('td');
        nameCell.textContent = item?.name || 'Unnamed Item';
        row.appendChild(nameCell);

        const stockCell = document.createElement('td');
        const control = document.createElement('div');
        control.className = 'inventory-stock-control';
        control.dataset.productId = Number(item?.id ?? 0);
        control.dataset.category = category;

        const minusButton = document.createElement('button');
        minusButton.type = 'button';
        minusButton.className = 'inventory-adjust-btn inventory-minus';
        minusButton.setAttribute('aria-label', 'Decrease stock');
        minusButton.textContent = '−';

        const stockInput = document.createElement('input');
        stockInput.type = 'number';
        stockInput.className = 'inventory-stock-input';
        stockInput.min = '0';
        stockInput.step = '1';
        stockInput.setAttribute('inputmode', 'numeric');
        stockInput.placeholder = 'Pre-order';
        if (item?.stock !== null && typeof item?.stock !== 'undefined') {
          stockInput.value = Number(item.stock);
        } else {
          stockInput.value = '';
        }

        const plusButton = document.createElement('button');
        plusButton.type = 'button';
        plusButton.className = 'inventory-adjust-btn inventory-plus';
        plusButton.setAttribute('aria-label', 'Increase stock');
        plusButton.textContent = '+';

        control.appendChild(minusButton);
        control.appendChild(stockInput);
        control.appendChild(plusButton);

        const note = document.createElement('span');
        note.className = 'inventory-stock-note';

        stockCell.appendChild(control);
        stockCell.appendChild(note);

        row.appendChild(stockCell);
        tbody.appendChild(row);
      });

      table.appendChild(tbody);
      section.appendChild(table);
      inventoryContainer.appendChild(section);
    });

    inventoryContainer.querySelectorAll('.inventory-stock-control').forEach((control) => {
      updateStockStatus(control);
    });

    applySearchFilter();
  }

  function updateStats(metrics) {
    if (statsEls.categoryCount) {
      statsEls.categoryCount.textContent = numberFormatter.format(metrics.categoryCount);
    }
    if (statsEls.skuCount) {
      statsEls.skuCount.textContent = numberFormatter.format(metrics.totalTracked);
    }
    if (statsEls.preOrderCount) {
      statsEls.preOrderCount.textContent = numberFormatter.format(metrics.preOrderCount);
    }
    if (statsEls.lowStockCount) {
      statsEls.lowStockCount.textContent = numberFormatter.format(metrics.lowStockCount);
    }
  }

  function renderCategoryChart(metrics) {
    const chartCanvas = document.getElementById('categoryChart');
    if (!chartCanvas || typeof Chart === 'undefined') {
      return;
    }

    const labels = Object.keys(metrics.totals);
    const values = labels.map((label) => metrics.totals[label]);
    const chartLabels = labels.length ? labels : ['No Data'];
    const chartValues = labels.length ? values : [0];

    if (!categoryChartInstance) {
      categoryChartInstance = new Chart(chartCanvas, {
        type: 'bar',
        data: {
          labels: chartLabels,
          datasets: [{
            label: 'Units on Hand',
            data: chartValues,
            backgroundColor: '#27ae60'
          }]
        },
        options: {
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          }
        }
      });
    } else {
      categoryChartInstance.data.labels = chartLabels;
      categoryChartInstance.data.datasets[0].data = chartValues;
      categoryChartInstance.update();
    }
  }

  function applySearchFilter() {
    if (!inventoryContainer) {
      return;
    }

    let visibleRows = 0;
    inventoryContainer.querySelectorAll('.inventory-section').forEach((section) => {
      const rows = section.querySelectorAll('tbody tr');
      let sectionVisible = 0;

      rows.forEach((row) => {
        const name = row.dataset.itemName || '';
        const matches = !currentSearchTerm || name.includes(currentSearchTerm);
        row.style.display = matches ? '' : 'none';
        if (matches) {
          sectionVisible += 1;
          visibleRows += 1;
        }
      });

      section.style.display = sectionVisible ? '' : 'none';
    });

    const existingEmptyState = inventoryContainer.querySelector('.inventory-empty-state');
    if (!visibleRows && currentSearchTerm) {
      if (!existingEmptyState) {
        const empty = document.createElement('p');
        empty.className = 'table-empty inventory-empty-state';
        empty.textContent = 'No inventory matches your search.';
        inventoryContainer.appendChild(empty);
      }
    } else if (existingEmptyState) {
      existingEmptyState.remove();
    }
  }

  function adjustStock(control, delta) {
    if (!control) {
      return;
    }
    const input = control.querySelector('.inventory-stock-input');
    if (!input) {
      return;
    }
    let value = parseInt(input.value, 10);
    if (Number.isNaN(value)) {
      value = 0;
    }
    value += delta;
    if (value < 0) {
      value = 0;
    }
    input.value = value;
    updateStockStatus(control);
    persistStock(control);
  }

  function updateStockStatus(control) {
    if (!control) {
      return;
    }
    const input = control.querySelector('.inventory-stock-input');
    const note = control.parentElement?.querySelector('.inventory-stock-note');
    const row = control.closest('tr');

    const raw = (input?.value ?? '').toString().trim();
    if (note) {
      note.textContent = '';
      note.classList.remove('is-preorder', 'is-low', 'is-zero');
    }
    if (row) {
      row.removeAttribute('data-stock-state');
    }

    if (raw === '') {
      if (note) {
        note.textContent = 'Pre-order';
        note.classList.add('is-preorder');
      }
      if (row) {
        row.setAttribute('data-stock-state', 'preorder');
      }
      return null;
    }

    let numeric = parseInt(raw, 10);
    if (Number.isNaN(numeric) || numeric < 0) {
      numeric = 0;
    }
    if (input && String(numeric) !== raw) {
      input.value = numeric;
    }

    if (note) {
      if (numeric === 0) {
        note.textContent = 'Out of stock';
        note.classList.add('is-zero');
      } else if (numeric <= 10) {
        note.textContent = 'Low stock';
        note.classList.add('is-low');
      } else {
        note.textContent = '';
      }
    }

    if (row) {
      if (numeric === 0) {
        row.setAttribute('data-stock-state', 'zero');
      } else if (numeric <= 10) {
        row.setAttribute('data-stock-state', 'low');
      } else {
        row.setAttribute('data-stock-state', 'ok');
      }
    }

    return numeric;
  }

  function persistStock(control) {
    if (!control) {
      return;
    }
    const input = control.querySelector('.inventory-stock-input');
    if (!input) {
      return;
    }
    const productId = Number(control.dataset.productId);
    const sanitized = sanitizeStockValue(input.value);
    input.value = sanitized === '' ? '' : sanitized;
    updateStockStatus(control);

    const itemMeta = inventoryIndex.get(productId);
    const previous = itemMeta ? inventoryData[itemMeta.category]?.[itemMeta.position]?.stock ?? null : null;

    if (sanitized === '' && previous === null) {
      return;
    }
    if (sanitized !== '' && previous === Number(sanitized)) {
      return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('stock_quantity', sanitized);

    const requestId = ++latestUpdateToken;
    setControlSaving(control, true);

    fetch(inventoryEndpoint, {
      method: 'POST',
      body: formData
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to update inventory.');
        }
        return response.json();
      })
      .then((result) => {
        if (!result?.success) {
          throw new Error(result?.error || 'Inventory update rejected.');
        }
        if (requestId !== latestUpdateToken) {
          return;
        }
        inventoryData = normalizeInventoryData(result.data);
        renderInventoryUI();
      })
      .catch((error) => {
        console.error('Error updating stock', error);
        renderInventoryUI();
      })
      .finally(() => {
        setControlSaving(control, false);
      });
  }

  function setControlSaving(control, isSaving) {
    if (!control) {
      return;
    }
    control.classList.toggle('is-saving', Boolean(isSaving));
  }

  function sanitizeStockValue(raw) {
    const trimmed = (raw ?? '').toString().trim();
    if (trimmed === '') {
      return '';
    }
    let numeric = parseInt(trimmed, 10);
    if (Number.isNaN(numeric) || numeric < 0) {
      numeric = 0;
    }
    return numeric;
  }

  function normalizeInventoryData(raw) {
    const normalized = {};
    if (!raw || typeof raw !== 'object') {
      return normalized;
    }
    Object.entries(raw).forEach(([category, items]) => {
      if (!Array.isArray(items)) {
        return;
      }
      normalized[category] = items.map((item) => {
        const stockValue = item?.stock;
        let normalizedStock = null;
        if (stockValue === null || stockValue === '' || typeof stockValue === 'undefined') {
          normalizedStock = null;
        } else {
          const parsed = parseInt(stockValue, 10);
          normalizedStock = Number.isNaN(parsed) ? 0 : Math.max(0, parsed);
        }
        return {
          id: Number(item?.id ?? 0),
          name: item?.name ?? '',
          stock: normalizedStock
        };
      });
    });
    return normalized;
  }

  function computeMetrics(data) {
    const totals = {};
    let totalTracked = 0;
    let preOrderCount = 0;
    let lowStockCount = 0;

    Object.entries(data).forEach(([category, items]) => {
      let categoryTotal = 0;
      if (!Array.isArray(items)) {
        totals[category] = 0;
        return;
      }
      items.forEach((item) => {
        totalTracked += 1;
        if (item.stock === null || typeof item.stock === 'undefined') {
          preOrderCount += 1;
        } else {
          const value = Math.max(0, parseInt(item.stock, 10) || 0);
          categoryTotal += value;
          if (value <= 10) {
            lowStockCount += 1;
          }
        }
      });
      totals[category] = categoryTotal;
    });

    return {
      totals,
      totalTracked,
      preOrderCount,
      lowStockCount,
      categoryCount: Object.keys(data).length
    };
  }

  function exportInventoryToPDF() {
    if (!window.jspdf) {
      return;
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.setFontSize(16);
    doc.text('Inventory Report', 14, 20);

    let y = 30;
    const categories = Object.keys(inventoryData).sort((a, b) => a.localeCompare(b));
    if (!categories.length) {
      doc.setFontSize(12);
      doc.text('No inventory records found.', 14, y);
      doc.save('inventory-report.pdf');
      return;
    }

    categories.forEach((category) => {
      if (y > 260) {
        doc.addPage();
        y = 20;
      }
      doc.setFontSize(14);
      doc.text(category, 14, y);
      y += 6;

      const body = (inventoryData[category] || []).map((item) => {
        const stock = item.stock === null || typeof item.stock === 'undefined' ? 'Pre-order' : String(item.stock);
        return [item.name || 'Unnamed Item', stock];
      });

      if (!body.length) {
        doc.setFontSize(12);
        doc.text('No items in this category.', 18, y);
        y += 8;
        return;
      }

      doc.autoTable({
        startY: y,
        head: [['Item Name', 'Stock']],
        body,
        theme: 'grid',
        styles: { fontSize: 10 }
      });

      if (doc.lastAutoTable) {
        y = doc.lastAutoTable.finalY + 10;
      }
    });

    doc.save('inventory-report.pdf');
  }

  function renderInventoryLogTable() {
    if (!logTableBody) {
      return;
    }

    const entries = inventoryLogEntries.filter((entry) => matchesLogSearch(entry));

    logTableBody.innerHTML = '';

    if (!entries.length) {
      const emptyRow = document.createElement('tr');
      const emptyCell = document.createElement('td');
      emptyCell.colSpan = 6;
      emptyCell.className = 'table-empty';
      emptyCell.textContent = 'No inventory changes recorded.';
      emptyRow.appendChild(emptyCell);
      logTableBody.appendChild(emptyRow);
      return;
    }

    const fragment = document.createDocumentFragment();

    entries.forEach((entry) => {
      const row = document.createElement('tr');
      if (entry.orderId) {
        row.dataset.orderId = String(entry.orderId);
      }

      const dateCell = document.createElement('td');
      dateCell.textContent = entry.orderDateFormatted || entry.orderDate || '—';
      row.appendChild(dateCell);

      const orderCell = document.createElement('td');
      orderCell.className = 'log-order';
      orderCell.textContent = entry.referenceLabel || '—';
      row.appendChild(orderCell);

      const customerCell = document.createElement('td');
      customerCell.textContent = entry.customerName || '—';
      row.appendChild(customerCell);

      const productCell = document.createElement('td');
      productCell.className = 'log-product';
      productCell.textContent = entry.productName || '—';
      row.appendChild(productCell);

      const changeCell = document.createElement('td');
      const changeSpan = document.createElement('span');
      const changeValue = typeof entry.change === 'number' ? entry.change : 0;
      const changeClass = getStockChangeClass(changeValue);
      if (changeClass) {
        changeSpan.className = changeClass;
      }
      const formattedChange = `\${changeValue > 0 ? '+' : ''}\${numberFormatter.format(changeValue)} pcs`;
      changeSpan.textContent = formattedChange;
      changeCell.appendChild(changeSpan);
      row.appendChild(changeCell);

      const statusCell = document.createElement('td');
      statusCell.appendChild(createStatusPill(entry.status));
      row.appendChild(statusCell);

      fragment.appendChild(row);
    });

    logTableBody.appendChild(fragment);
  }

  function matchesLogSearch(entry) {
    if (!currentLogSearchTerm) {
      return true;
    }
    const haystack = [
      entry.referenceLabel,
      entry.productName,
      entry.status,
      entry.orderDateFormatted,
      entry.orderDate,
      entry.customerName,
      entry.changeType,
      entry.orderId ? `#\${entry.orderId}` : ''
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();
    return haystack.includes(currentLogSearchTerm);
  }

  function getStockChangeClass(changeValue) {
    if (changeValue > 0) {
      return 'stock-change-positive';
    }
    if (changeValue < 0) {
      return 'stock-change-negative';
    }
    return 'stock-change-zero';
  }

  function createStatusPill(statusText) {
    const status = (statusText || 'Pending').toString();
    const slug = status
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'pending';
    const pill = document.createElement('span');
    pill.className = `status-pill status-\${slug}`;
    pill.textContent = status;
    return pill;
  }

  function formatDateForDisplay(dateString) {
    if (!dateString) {
      return '—';
    }
    const isoMatch = /^([0-9]{4})-([0-9]{2})-([0-9]{2})$/.exec(dateString);
    if (isoMatch) {
      const [, year, month, day] = isoMatch;
      const monthIndex = Number(month) - 1;
      const dayNumber = Number(day);
      if (monthIndex >= 0 && monthIndex < 12 && !Number.isNaN(dayNumber)) {
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `\${monthNames[monthIndex]} \${dayNumber}, \${year}`;
      }
    }
    const parsed = new Date(dateString);
    if (!Number.isNaN(parsed.getTime())) {
      return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(parsed);
    }
    return dateString;
  }

  function normalizeInventoryLog(raw) {
    if (!Array.isArray(raw)) {
      return [];
    }

    return raw.map((entry) => {
      const orderId = Number(entry?.order_id ?? entry?.Order_ID ?? 0);
      const productId = Number(entry?.product_id ?? entry?.Product_ID ?? 0);
      const quantityRaw = entry?.quantity ?? entry?.Quantity;
      const parsedQuantity = parseInt(quantityRaw, 10);
      const quantity = Number.isNaN(parsedQuantity) ? 0 : Math.max(0, parsedQuantity);
      const changeRaw = entry?.change ?? entry?.Change;
      const parsedChange = parseInt(changeRaw, 10);
      const changeValue = !Number.isNaN(parsedChange)
        ? parsedChange
        : quantity > 0
          ? -quantity
          : 0;
      const orderCode = entry?.order_code ?? entry?.Order_Code ?? (orderId ? `#\${String(orderId).padStart(5, '0')}` : '—');
      const orderDate = entry?.order_date ?? entry?.Order_Date ?? '';
      const orderDateFormatted = entry?.order_date_formatted ?? entry?.Order_Date_Formatted ?? formatDateForDisplay(orderDate);

      return {
        id: Number(entry?.order_item_id ?? entry?.Order_Item_ID ?? 0),
        orderId,
        referenceLabel: orderCode,
        productId,
        productName: entry?.product_name ?? entry?.Product_Name ?? '',
        quantity,
        change: changeValue,
        status: entry?.status ?? entry?.Status ?? 'Pending',
        orderDate,
        orderDateFormatted,
        customerName: entry?.customer_name ?? entry?.Customer_Name ?? '',
        changeType: entry?.change_type ?? entry?.Change_Type ?? 'Order Placement'
      };
    });
  }

  inventoryData = normalizeInventoryData({$inventoryJson});
  inventoryLogEntries = normalizeInventoryLog({$inventoryLogJson});
  renderInventoryUI();
  renderInventoryLogTable();
});
</script>
JS;
include 'includes/footer.php';
