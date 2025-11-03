<?php
require_once __DIR__ . '/includes/require_admin_login.php';
require_once '../PHP/db_connect.php';
require_once '../PHP/inventory_functions.php';
require_once '../PHP/product_functions.php';

$activePage = 'inventory-report';
$pageTitle = "Inventory Report - Cindy's Bakeshop";

$inventoryData = [];
$categoryTotals = [];
$totalTracked = 0;
$preOrderCount = 0;
$lowStockCount = 0;
$inventoryLogEntries = [];
$inventoryLogDateCounts = [];
$reportDateInput = isset($_GET['report_date']) ? trim((string)$_GET['report_date']) : '';
$reportDate = null;
if ($reportDateInput !== '') {
    $parsedDate = DateTime::createFromFormat('Y-m-d', $reportDateInput);
    if ($parsedDate !== false) {
        $reportDate = $parsedDate->format('Y-m-d');
    }
}
$reportDateLabel = $reportDate ? date('M j, Y', strtotime($reportDate)) : null;
$reportRangeDescription = $reportDateLabel ? 'Showing changes for ' . $reportDateLabel : 'Showing changes for all dates';
$inventorySnapshotDescription = $reportDateLabel
    ? 'Stock levels reflect end-of-day balances for ' . $reportDateLabel . '.'
    : 'Stock levels reflect current stock levels.';
$inventoryCalendarDescription = $reportDateLabel
    ? 'Currently viewing inventory activity for ' . $reportDateLabel . '.'
    : 'Select a date to view daily inventory history.';

$todayDate = date('Y-m-d');
$inventoryEditingLocked = $reportDate !== null && $reportDate < $todayDate;

if ($pdo) {
    $rows = getInventoryWithProducts($pdo, $reportDate);
    foreach ($rows as $row) {
        $categoryRaw = $row['Category'] ?? '';
        $normalizedCategory = normalizeProductCategoryValue($categoryRaw);
        $category = $normalizedCategory === '' ? 'Uncategorized' : $normalizedCategory;
        $stockRaw = $row['Stock_Quantity'];
        $stockValue = max(0, (int)$stockRaw);

        if (!array_key_exists($category, $inventoryData)) {
            $inventoryData[$category] = [];
        }
        if (!array_key_exists($category, $categoryTotals)) {
            $categoryTotals[$category] = 0;
        }

        $inventoryData[$category][] = [
            'id' => $row['Product_ID'],
            'name' => $row['Name'],
            'stock' => $stockValue
        ];

        $totalTracked++;

        $categoryTotals[$category] += $stockValue;
        if ($stockValue <= 10) {
            $lowStockCount++;
        }
    }

    ksort($inventoryData);
    ksort($categoryTotals);

    $logRows = getInventoryChangeLog($pdo, $reportDate, $reportDate);
    foreach ($logRows as $logRow) {
        $orderId = isset($logRow['Order_ID']) ? (int)$logRow['Order_ID'] : 0;
        $rawDate = $logRow['Created_At'] ?? null;
        $logDateRaw = $logRow['Log_Date'] ?? null;
        $normalizedLogDate = null;

        if ($logDateRaw) {
            $logDateTimestamp = strtotime((string)$logDateRaw);
            if ($logDateTimestamp !== false) {
                $normalizedLogDate = date('Y-m-d', $logDateTimestamp);
            }
        }

        $rawDateTimestamp = false;
        if ($rawDate) {
            $rawDateTimestamp = strtotime((string)$rawDate);
            if ($rawDateTimestamp !== false && $normalizedLogDate === null) {
                $normalizedLogDate = date('Y-m-d', $rawDateTimestamp);
            }
        }

        if ($normalizedLogDate === null && !empty($logRow['Order_Date'])) {
            $orderDateTimestamp = strtotime((string)$logRow['Order_Date']);
            if ($orderDateTimestamp !== false) {
                $normalizedLogDate = date('Y-m-d', $orderDateTimestamp);
            }
        }

        if ($reportDate !== null && $normalizedLogDate !== $reportDate) {
            continue;
        }

        if (($rawDate === null || $rawDate === '') && $normalizedLogDate !== null) {
            $rawDate = $normalizedLogDate . ' 00:00:00';
            $rawDateTimestamp = strtotime($rawDate);
        }

        if (($rawDate === null || $rawDate === '') && !empty($logRow['Order_Date'])) {
            $rawDate = (string)$logRow['Order_Date'];
            $rawDateTimestamp = strtotime($rawDate);
        }

        $formattedDate = null;
        if ($rawDateTimestamp !== false) {
            $formattedDate = date('M j, Y g:i A', $rawDateTimestamp);
        } elseif ($normalizedLogDate !== null) {
            $dateOnlyTimestamp = strtotime($normalizedLogDate);
            if ($dateOnlyTimestamp !== false) {
                $formattedDate = date('M j, Y', $dateOnlyTimestamp);
            }
        }

        $source = $logRow['Change_Source'] ?? '';
        $note = $logRow['Note'] ?? '';
        $referenceType = $logRow['Reference_Type'] ?? '';
        $referenceLabel = null;

        if ($referenceType === 'order' && $orderId > 0) {
            $referenceLabel = '#' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
        }

        if ($referenceLabel === null || $referenceLabel === '') {
            $referenceLabel = $note !== '' ? $note : ucwords(str_replace('_', ' ', $source !== '' ? $source : 'Stock Update'));
        }

        $changeTypeMap = [
            'order' => 'Order Placement',
            'manual_adjustment' => 'Manual Adjustment',
            'initial_entry' => 'Inventory Seed',
            'adjustment' => 'Stock Adjustment',
        ];
        $changeType = $changeTypeMap[$source] ?? 'Stock Update';

        $previousQuantityRaw = $logRow['Previous_Quantity'] ?? null;
        $previousQuantity = $previousQuantityRaw !== null ? (int)$previousQuantityRaw : null;
        $newQuantityRaw = $logRow['New_Quantity'] ?? null;
        $newQuantity = $newQuantityRaw !== null ? (int)$newQuantityRaw : null;
        $changeValueRaw = $logRow['Change_Amount'] ?? null;
        $changeValue = $changeValueRaw !== null ? (int)$changeValueRaw : 0;

        $inventoryLogEntries[] = [
            'log_id' => (int)($logRow['Log_ID'] ?? 0),
            'order_id' => $orderId,
            'order_code' => $referenceLabel,
            'product_id' => (int)($logRow['Product_ID'] ?? 0),
            'product_name' => $logRow['Product_Name'] ?? '',
            'change' => $changeValue,
            'change_type' => $changeType,
            'created_at' => $rawDate,
            'created_at_formatted' => $formattedDate,
            'customer_name' => $logRow['Customer_Name'] ?? '',
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'note' => $note,
            'reference_label' => $referenceLabel,
            'change_source' => $source,
            'log_date' => $normalizedLogDate,
        ];
    }

    $logDateCountRows = getInventoryLogDateCounts($pdo);
    foreach ($logDateCountRows as $countRow) {
        $rawDate = $countRow['Report_Date'] ?? null;
        $changes = isset($countRow['Change_Count']) ? (int)$countRow['Change_Count'] : 0;
        if ($rawDate !== null) {
            $normalizedDate = date('Y-m-d', strtotime((string)$rawDate));
            $inventoryLogDateCounts[$normalizedDate] = (
                ($inventoryLogDateCounts[$normalizedDate] ?? 0) + $changes
            );
        }
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

$inventoryLogDateCountsJson = json_encode($inventoryLogDateCounts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($inventoryLogDateCountsJson === 'null') {
    $inventoryLogDateCountsJson = '{}';
}

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Inventory Report</h1>
  </div>

  <div class="card inventory-date-card">
    <div class="inventory-log-header">
      <div class="inventory-log-heading">
        <h2 style="margin:0;font-size:18px;font-weight:600;">Select Report Date</h2>
        <p class="inventory-log-meta"><?= htmlspecialchars($inventoryCalendarDescription); ?></p>
      </div>
      <form method="get" action="report.php" class="inventory-log-filter" autocomplete="off">
        <label for="reportDate">Report date</label>
        <input type="date" id="reportDate" name="report_date" value="<?= htmlspecialchars($reportDate ?? ''); ?>">
        <button type="submit" class="btn btn-primary">Apply</button>
        <?php if ($reportDate !== null): ?>
          <a href="report.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
      </form>
    </div>
    <div class="inventory-calendar-wrapper">
      <div id="inventoryCalendar" class="inventory-calendar" data-selected-date="<?= htmlspecialchars($reportDate ?? ''); ?>">
        <div class="calendar-loading">Loading calendar…</div>
      </div>
      <noscript>
        <p class="calendar-noscript">Enable JavaScript to browse daily inventory activity using the calendar. You can still pick a date using the field above.</p>
      </noscript>
    </div>
  </div>

  <?php if ($inventorySnapshotDescription !== ''): ?>
    <p class="inventory-snapshot-note"><?= htmlspecialchars($inventorySnapshotDescription); ?></p>
  <?php endif; ?>

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
      <div class="value" id="inventoryPreorderCount">0</div>
      <div class="meta">Pre-ordering disabled</div>
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
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <button class="btn btn-secondary" type="button" id="toggleInventoryEdit" aria-pressed="false" data-editing-locked="<?= $inventoryEditingLocked ? '1' : '0'; ?>"<?php if ($inventoryEditingLocked): ?> disabled aria-disabled="true" title="Editing historical inventory snapshots is disabled."<?php endif; ?>>Enable Editing</button>
        <button class="btn btn-primary" type="button" id="exportInventory">Export Inventory PDF</button>
      </div>
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
                    <td><?= number_format(max(0, (int)$item['stock'])); ?></td>
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
      <div class="inventory-log-header">
        <div class="inventory-log-heading">
          <h2 style="margin:0;font-size:18px;font-weight:600;">Inventory Change Log</h2>
          <p class="inventory-log-meta"><?= htmlspecialchars($reportRangeDescription); ?></p>
        </div>
      </div>
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
        </tr>
      </thead>
      <tbody id="inventoryLogBody">
        <?php if (empty($inventoryLogEntries)): ?>
          <tr>
            <td colspan="5" class="table-empty">No inventory changes recorded.</td>
          </tr>
        <?php else: ?>
            <?php foreach ($inventoryLogEntries as $entry): ?>
              <?php
                $changeValue = (int)($entry['change'] ?? 0);
                $changeLabel = ($changeValue > 0 ? '+' : '') . number_format($changeValue) . ' pcs';
                $changeClass = $changeValue > 0 ? 'stock-change-positive' : ($changeValue < 0 ? 'stock-change-negative' : 'stock-change-zero');
                $customerName = $entry['customer_name'] ?? '';
                $referenceLabel = $entry['reference_label'] ?? $entry['order_code'] ?? ($entry['order_id'] ?? 0 ? '#' . str_pad((string)($entry['order_id'] ?? 0), 5, '0', STR_PAD_LEFT) : '—');
              ?>
              <tr data-order-id="<?= (int)($entry['order_id'] ?? 0); ?>">
                <td><?= htmlspecialchars($entry['created_at_formatted'] ?? $entry['created_at'] ?? '—'); ?></td>
                <td class="log-order"><?= htmlspecialchars($referenceLabel); ?></td>
                <td><?= htmlspecialchars($customerName !== '' ? $customerName : '—'); ?></td>
                <td class="log-product"><?= htmlspecialchars($entry['product_name'] ?? ''); ?></td>
                <td><span class="<?= $changeClass; ?>"><?= htmlspecialchars($changeLabel); ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
  </div>
</div>
<?php
ob_start();
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const inventoryEndpoint = '../PHP/inventory_functions.php';
  const inventoryContainer = document.getElementById('inventoryContainer');
  const searchInput = document.getElementById('inventorySearch');
  const exportBtn = document.getElementById('exportInventory');
  const editToggleBtn = document.getElementById('toggleInventoryEdit');
  const editingLockedForDate = editToggleBtn?.dataset?.editingLocked === '1';
  const logSearchInput = document.getElementById('inventoryLogSearch');
  const logTableBody = document.getElementById('inventoryLogBody');
  const reportForm = document.querySelector('.inventory-log-filter');
  const reportDateInput = document.getElementById('reportDate');
  const logCalendarContainer = document.getElementById('inventoryCalendar');
  const statsEls = {
    categoryCount: document.getElementById('inventoryCategoryCount'),
    skuCount: document.getElementById('inventorySkuCount'),
    preOrderCount: document.getElementById('inventoryPreorderCount'),
    lowStockCount: document.getElementById('inventoryLowStockCount')
  };

  const numberFormatter = new Intl.NumberFormat();
  const parseNullableInteger = (value) => {
    if (value === null || typeof value === 'undefined' || value === '') {
      return null;
    }
    const parsed = parseInt(value, 10);
    return Number.isNaN(parsed) ? null : parsed;
  };
  const normalizeInventoryCategory = (value) => {
    if (typeof value !== 'string') {
      return '';
    }
    const trimmed = value.trim();
    if (trimmed === '') {
      return '';
    }
    return trimmed.toLowerCase().includes('pastry') ? 'Bread' : trimmed;
  };
  let currentSearchTerm = '';
  let currentLogSearchTerm = '';
  let latestUpdateToken = 0;
  let categoryChartInstance = null;
  let inventoryIndex = new Map();
  let inventoryData = {};
  let inventoryLogEntries = [];
  let logDateCountsByDate = new Map();
  let calendarFocusYear = null;
  let calendarFocusMonth = null;
  let inventoryEditingEnabled = false;

  if (searchInput) {
    currentSearchTerm = searchInput.value.toLowerCase();
    searchInput.addEventListener('input', () => {
      currentSearchTerm = searchInput.value.toLowerCase();
      applySearchFilter();
    });
  }

  if (inventoryContainer) {
    inventoryContainer.addEventListener('click', (event) => {
      if (!inventoryEditingEnabled || editingLockedForDate) {
        return;
      }
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
      if (!inventoryEditingEnabled || editingLockedForDate) {
        return;
      }
      const input = event.target.closest('.inventory-stock-input');
      if (!input) {
        return;
      }
      const control = input.closest('.inventory-stock-control');
      updateStockStatus(control);
    });

    inventoryContainer.addEventListener('change', (event) => {
      if (!inventoryEditingEnabled || editingLockedForDate) {
        return;
      }
      const input = event.target.closest('.inventory-stock-input');
      if (!input) {
        return;
      }
      const control = input.closest('.inventory-stock-control');
      persistStock(control);
    });

    inventoryContainer.addEventListener('keydown', (event) => {
      if (!inventoryEditingEnabled || editingLockedForDate) {
        return;
      }
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
      if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') {
        window.alert('PDF generator is not ready yet. Please try again in a moment.');
        return;
      }

      const doc = buildInventoryPDFDocument();
      if (!doc) {
        window.alert('Unable to build the inventory report right now. Please try again.');
        return;
      }

      showInventoryPdfPreview(doc, 'inventory-report.pdf');
    });
  }

  if (logSearchInput) {
    currentLogSearchTerm = logSearchInput.value.trim().toLowerCase();
    logSearchInput.addEventListener('input', () => {
      currentLogSearchTerm = logSearchInput.value.trim().toLowerCase();
      renderInventoryLogTable();
    });
  }

  if (editToggleBtn) {
    if (editingLockedForDate) {
      editToggleBtn.disabled = true;
      editToggleBtn.setAttribute('aria-disabled', 'true');
      editToggleBtn.setAttribute('aria-pressed', 'false');
      editToggleBtn.setAttribute('title', 'Editing historical inventory snapshots is disabled.');
    } else {
      editToggleBtn.addEventListener('click', () => {
        inventoryEditingEnabled = !inventoryEditingEnabled;
        applyEditingState();
        if (inventoryEditingEnabled) {
          const firstInput = inventoryContainer?.querySelector('.inventory-stock-input');
          if (firstInput) {
            firstInput.focus();
            if (typeof firstInput.select === 'function') {
              firstInput.select();
            }
          }
        }
      });
    }
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
        stockInput.placeholder = '0';
        const parsedStock = parseInt(item?.stock, 10);
        const safeStock = Number.isNaN(parsedStock) ? 0 : Math.max(0, parsedStock);
        stockInput.value = safeStock;

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

    applyEditingState();

    applySearchFilter();
  }

  function applyEditingState() {
    const effectiveEditingEnabled = !editingLockedForDate && inventoryEditingEnabled;

    if (editToggleBtn) {
      if (editingLockedForDate) {
        editToggleBtn.disabled = true;
        editToggleBtn.setAttribute('aria-disabled', 'true');
        editToggleBtn.setAttribute('aria-pressed', 'false');
        editToggleBtn.setAttribute('title', 'Editing historical inventory snapshots is disabled.');
        editToggleBtn.textContent = 'Enable Editing';
        editToggleBtn.classList.remove('is-active');
        editToggleBtn.classList.remove('btn-primary');
        editToggleBtn.classList.add('btn-secondary');
      } else {
        editToggleBtn.disabled = false;
        editToggleBtn.removeAttribute('aria-disabled');
        editToggleBtn.removeAttribute('title');
        editToggleBtn.textContent = effectiveEditingEnabled ? 'Disable Editing' : 'Enable Editing';
        editToggleBtn.setAttribute('aria-pressed', effectiveEditingEnabled ? 'true' : 'false');
        editToggleBtn.classList.toggle('is-active', effectiveEditingEnabled);
        editToggleBtn.classList.toggle('btn-primary', effectiveEditingEnabled);
        editToggleBtn.classList.toggle('btn-secondary', !effectiveEditingEnabled);
      }
    }

    if (!inventoryContainer) {
      return;
    }

    inventoryContainer.classList.toggle('is-editing', effectiveEditingEnabled);

    inventoryContainer.querySelectorAll('.inventory-stock-control').forEach((control) => {
      control.classList.toggle('is-readonly', !effectiveEditingEnabled);
      const minusButton = control.querySelector('.inventory-minus');
      const plusButton = control.querySelector('.inventory-plus');
      const stockInput = control.querySelector('.inventory-stock-input');

      [minusButton, plusButton].forEach((button) => {
        if (button) {
          button.disabled = !effectiveEditingEnabled;
        }
      });

      if (stockInput) {
        stockInput.disabled = !effectiveEditingEnabled;
        stockInput.readOnly = !effectiveEditingEnabled;
        if (!effectiveEditingEnabled) {
          stockInput.blur();
        }
      }
    });
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
    if (!inventoryEditingEnabled || editingLockedForDate) {
      return;
    }
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

    let numeric = parseInt(raw, 10);
    if (Number.isNaN(numeric) || numeric < 0) {
      numeric = 0;
    }
    if (input) {
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
    if (!inventoryEditingEnabled || editingLockedForDate) {
      return;
    }
    if (!control) {
      return;
    }
    const input = control.querySelector('.inventory-stock-input');
    if (!input) {
      return;
    }
    const productId = Number(control.dataset.productId);
    const sanitized = sanitizeStockValue(input.value);
    input.value = sanitized;
    updateStockStatus(control);

    const itemMeta = inventoryIndex.get(productId);
    const previous = itemMeta ? inventoryData[itemMeta.category]?.[itemMeta.position]?.stock ?? 0 : 0;
    const sanitizedNumber = parseInt(sanitized, 10);

    if (!Number.isNaN(sanitizedNumber) && previous === sanitizedNumber) {
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
    let numeric = parseInt(trimmed, 10);
    if (Number.isNaN(numeric) || numeric < 0) {
      numeric = 0;
    }
    return String(numeric);
  }

  function normalizeInventoryData(raw) {
    const normalized = {};
    if (!raw || typeof raw !== 'object') {
      return normalized;
    }

    Object.entries(raw).forEach(([category, items]) => {
      const normalizedCategory = normalizeInventoryCategory(category);
      const targetCategory = normalizedCategory === '' ? 'Uncategorized' : normalizedCategory;

      if (!Object.prototype.hasOwnProperty.call(normalized, targetCategory)) {
        normalized[targetCategory] = [];
      }

      if (!Array.isArray(items)) {
        return;
      }

      items.forEach((item) => {
        const stockValue = item?.stock;
        const parsed = parseInt(stockValue, 10);
        const normalizedStock = Number.isNaN(parsed) ? 0 : Math.max(0, parsed);
        const idValue = Number(item?.id ?? item?.Product_ID ?? 0);
        const normalizedId = Number.isFinite(idValue) ? idValue : 0;
        const nameValue = item?.name ?? item?.Name ?? '';

        normalized[targetCategory].push({
          id: normalizedId,
          name: nameValue,
          stock: normalizedStock
        });
      });
    });

    Object.keys(normalized).forEach((categoryKey) => {
      const seenIds = new Set();
      normalized[categoryKey] = normalized[categoryKey].filter((item) => {
        const id = Number(item.id);
        if (!Number.isFinite(id) || id <= 0) {
          return true;
        }
        if (seenIds.has(id)) {
          return false;
        }
        seenIds.add(id);
        return true;
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
        const value = Math.max(0, parseInt(item.stock, 10) || 0);
        categoryTotal += value;
        if (value <= 10) {
          lowStockCount += 1;
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

  function buildInventoryPDFDocument() {
    if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') {
      return null;
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
      return doc;
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
        const stock = Math.max(0, parseInt(item.stock, 10) || 0);
        return [item.name || 'Unnamed Item', String(stock)];
      });

      if (!body.length) {
        doc.setFontSize(12);
        doc.text('No items in this category.', 18, y);
        y += 8;
        return;
      }

      if (typeof doc.autoTable === 'function') {
        doc.autoTable({
          startY: y,
          head: [['Item Name', 'Stock']],
          body,
          theme: 'grid',
          styles: { fontSize: 10 }
        });

        if (doc.lastAutoTable) {
          y = doc.lastAutoTable.finalY + 10;
        } else {
          y += 10;
        }
      } else {
        doc.setFontSize(12);
        body.forEach((row) => {
          if (y > 280) {
            doc.addPage();
            y = 20;
          }
          doc.text(`${row[0]} - ${row[1]}`, 18, y);
          y += 6;
        });
        y += 4;
      }
    });

    return doc;
  }

  function showInventoryPdfPreview(pdfDoc, filename) {
    if (!pdfDoc || typeof pdfDoc.output !== 'function') {
      if (pdfDoc && typeof pdfDoc.save === 'function') {
        pdfDoc.save(filename);
      }
      return;
    }

    const supportsObjectUrl = typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function';
    let blobUrl = null;

    if (supportsObjectUrl) {
      try {
        const blob = pdfDoc.output('blob');
        if (blob instanceof Blob) {
          blobUrl = URL.createObjectURL(blob);
        }
      } catch (error) {
        console.error('Failed to build PDF blob for preview:', error);
      }
    }

    if (!blobUrl) {
      try {
        const dataUrl = pdfDoc.output('dataurlstring');
        if (dataUrl) {
          const previewWindow = window.open(dataUrl, '_blank', 'noopener');
          if (!previewWindow) {
            window.alert('Unable to open preview window. The PDF will be downloaded instead.');
            pdfDoc.save(filename);
          }
        } else {
          pdfDoc.save(filename);
        }
      } catch (error) {
        console.error('Failed to open PDF preview window:', error);
        pdfDoc.save(filename);
      }
      return;
    }

    const existingOverlay = document.getElementById('inventoryPdfPreviewOverlay');
    if (existingOverlay) {
      existingOverlay.remove();
    }

    if (!document.body) {
      pdfDoc.save(filename);
      return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'inventoryPdfPreviewOverlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Inventory report PDF preview');
    overlay.style.cssText = [
      'position:fixed',
      'inset:0',
      'background:rgba(0,0,0,0.65)',
      'display:flex',
      'align-items:center',
      'justify-content:center',
      'padding:24px',
      'z-index:9999'
    ].join(';');

    const modal = document.createElement('div');
    modal.style.cssText = [
      'background:#ffffff',
      'max-width:960px',
      'width:100%',
      'height:85vh',
      'display:flex',
      'flex-direction:column',
      'border-radius:8px',
      'box-shadow:0 12px 40px rgba(0,0,0,0.25)',
      'overflow:hidden'
    ].join(';');

    const frame = document.createElement('iframe');
    frame.src = blobUrl;
    frame.title = 'Inventory report preview';
    frame.style.cssText = ['flex:1', 'border:0'].join(';');

    const actions = document.createElement('div');
    actions.style.cssText = [
      'display:flex',
      'justify-content:flex-end',
      'gap:12px',
      'padding:12px 16px',
      'background:#f5f6fa',
      'border-top:1px solid #dcdde1'
    ].join(';');

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.textContent = 'Close';
    closeBtn.className = 'btn btn-secondary';
    closeBtn.style.cssText = [
      'padding:10px 18px',
      'border:none',
      'border-radius:4px',
      'background:#7f8c8d',
      'color:#fff',
      'cursor:pointer',
      'font-size:14px'
    ].join(';');

    const downloadBtn = document.createElement('button');
    downloadBtn.type = 'button';
    downloadBtn.textContent = 'Download PDF';
    downloadBtn.className = 'btn btn-primary';
    downloadBtn.style.cssText = [
      'padding:10px 18px',
      'border:none',
      'border-radius:4px',
      'background:#e67e22',
      'color:#fff',
      'cursor:pointer',
      'font-size:14px'
    ].join(';');

    actions.append(closeBtn, downloadBtn);
    modal.append(frame, actions);
    overlay.appendChild(modal);

    function cleanup() {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
      if (blobUrl) {
        URL.revokeObjectURL(blobUrl);
      }
      document.removeEventListener('keydown', handleKeyDown);
    }

    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        cleanup();
      }
    }

    document.addEventListener('keydown', handleKeyDown);

    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) {
        cleanup();
      }
    });

    closeBtn.addEventListener('click', cleanup);

    downloadBtn.addEventListener('click', () => {
      pdfDoc.save(filename);
      cleanup();
    });

    document.body.appendChild(overlay);
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
      emptyCell.colSpan = 5;
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
      if (entry.changeType) {
        row.dataset.changeType = entry.changeType;
      }
      if (entry.note) {
        row.title = entry.note;
      }

      const dateCell = document.createElement('td');
      dateCell.textContent = entry.createdAtFormatted || entry.createdAt || '—';
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
      const changeValue = typeof entry.change === 'number' && !Number.isNaN(entry.change) ? entry.change : 0;
      const changeClass = getStockChangeClass(changeValue);
      if (changeClass) {
        changeSpan.className = changeClass;
      }
      const formattedChange = `${changeValue > 0 ? '+' : ''}${numberFormatter.format(changeValue)} pcs`;
      changeSpan.textContent = formattedChange;
      changeCell.appendChild(changeSpan);
      row.appendChild(changeCell);

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
      entry.createdAtFormatted,
      entry.createdAt,
      entry.logDate,
      entry.customerName,
      entry.changeType,
      entry.changeSource,
      entry.note,
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
      const logId = Number(
        entry?.log_id
        ?? entry?.Log_ID
        ?? entry?.order_item_id
        ?? entry?.Order_Item_ID
        ?? 0
      );
      const orderId = Number(
        entry?.order_id
        ?? entry?.Order_ID
        ?? entry?.reference_id
        ?? entry?.Reference_ID
        ?? 0
      );
      const referenceFallback = orderId ? `#${String(orderId).padStart(5, '0')}` : '';
      const referenceLabel = entry?.reference_label
        ?? entry?.Reference_Label
        ?? entry?.order_code
        ?? entry?.Order_Code
        ?? referenceFallback;
      const changeRaw = entry?.change
        ?? entry?.Change
        ?? entry?.change_amount
        ?? entry?.Change_Amount
        ?? 0;
      const parsedChange = parseInt(changeRaw, 10);
      const changeValue = Number.isNaN(parsedChange) ? 0 : parsedChange;
      const createdAt = entry?.created_at
        ?? entry?.Created_At
        ?? entry?.order_date
        ?? entry?.Order_Date
        ?? '';
      const createdAtFormatted = entry?.created_at_formatted
        ?? entry?.Created_At_Formatted
        ?? entry?.order_date_formatted
        ?? entry?.Order_Date_Formatted
        ?? formatDateForDisplay(createdAt);
      const logDateRaw = entry?.log_date
        ?? entry?.Log_Date
        ?? (createdAt ? String(createdAt).split(' ')[0] : '');
      const logDate = sanitizeIsoDate(logDateRaw);

      return {
        id: logId,
        orderId,
        referenceLabel,
        productId: Number(entry?.product_id ?? entry?.Product_ID ?? 0),
        productName: entry?.product_name ?? entry?.Product_Name ?? '',
        change: changeValue,
        createdAt,
        createdAtFormatted,
        logDate,
        customerName: entry?.customer_name ?? entry?.Customer_Name ?? '',
        changeType: entry?.change_type ?? entry?.Change_Type ?? 'Stock Update',
        changeSource: entry?.change_source ?? entry?.Change_Source ?? '',
        note: entry?.note ?? entry?.Note ?? '',
        previousQuantity: parseNullableInteger(entry?.previous_quantity ?? entry?.Previous_Quantity),
        newQuantity: parseNullableInteger(entry?.new_quantity ?? entry?.New_Quantity)
      };
    });
  }

  function normalizeLogDateCounts(raw) {
    const counts = new Map();

    if (!raw) {
      return counts;
    }

    const assignCount = (dateKey, countValue) => {
      if (!dateKey) {
        return;
      }
      const normalizedDate = sanitizeIsoDate(dateKey);
      if (!normalizedDate) {
        return;
      }
      const numericCount = Number.parseInt(countValue, 10);
      if (Number.isNaN(numericCount)) {
        return;
      }
      counts.set(normalizedDate, (counts.get(normalizedDate) || 0) + Math.max(0, numericCount));
    };

    if (Array.isArray(raw)) {
      raw.forEach((entry) => {
        assignCount(
          entry?.report_date
            ?? entry?.Report_Date
            ?? entry?.date
            ?? entry?.Date,
          entry?.change_count
            ?? entry?.Change_Count
            ?? entry?.count
            ?? entry?.Count
            ?? 0,
        );
      });
    } else if (typeof raw === 'object') {
      Object.entries(raw).forEach(([dateKey, countValue]) => {
        assignCount(dateKey, countValue);
      });
    }

    return counts;
  }

  function sanitizeIsoDate(raw) {
    if (typeof raw !== 'string') {
      return '';
    }
    const trimmed = raw.trim();
    const isoMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(trimmed);
    if (isoMatch) {
      const [, year, month, day] = isoMatch;
      return `${year}-${month}-${day}`;
    }
    const parsed = createDateFromISO(trimmed);
    return parsed ? formatDateToISO(parsed) : '';
  }

  function createDateFromISO(value) {
    if (typeof value !== 'string') {
      return null;
    }
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
    if (!match) {
      return null;
    }
    const [, yearStr, monthStr, dayStr] = match;
    const year = Number.parseInt(yearStr, 10);
    const monthIndex = Number.parseInt(monthStr, 10) - 1;
    const day = Number.parseInt(dayStr, 10);
    if (Number.isNaN(year) || Number.isNaN(monthIndex) || Number.isNaN(day)) {
      return null;
    }
    const date = new Date(Date.UTC(year, monthIndex, day));
    if (Number.isNaN(date.getTime())) {
      return null;
    }
    return date;
  }

  function formatDateToISO(date) {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
      return '';
    }
    const year = date.getUTCFullYear();
    const month = String(date.getUTCMonth() + 1).padStart(2, '0');
    const day = String(date.getUTCDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function getTodayIso() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function initializeInventoryCalendar() {
    if (!logCalendarContainer) {
      return;
    }

    const selectedDateIso = sanitizeIsoDate(
      reportDateInput?.value
        ?? logCalendarContainer.dataset.selectedDate
        ?? '',
    );

    const selectedDate = createDateFromISO(selectedDateIso);
    let focusDate = selectedDate;

    if (!focusDate) {
      const sortedDates = Array.from(logDateCountsByDate.keys()).sort();
      if (sortedDates.length) {
        focusDate = createDateFromISO(sortedDates[sortedDates.length - 1]);
      }
    }

    if (!focusDate) {
      focusDate = new Date();
    }

    calendarFocusYear = focusDate.getUTCFullYear();
    calendarFocusMonth = focusDate.getUTCMonth();
    logCalendarContainer.dataset.selectedDate = selectedDateIso;

    renderInventoryCalendar();
  }

  function shiftCalendarMonth(delta) {
    if (typeof delta !== 'number' || Number.isNaN(delta)) {
      return;
    }
    if (calendarFocusYear === null || calendarFocusMonth === null) {
      return;
    }
    const target = new Date(Date.UTC(calendarFocusYear, calendarFocusMonth + delta, 1));
    if (Number.isNaN(target.getTime())) {
      return;
    }
    calendarFocusYear = target.getUTCFullYear();
    calendarFocusMonth = target.getUTCMonth();
  }

  function renderInventoryCalendar() {
    if (!logCalendarContainer) {
      return;
    }
    if (calendarFocusYear === null || calendarFocusMonth === null) {
      return;
    }

    const focusDate = new Date(Date.UTC(calendarFocusYear, calendarFocusMonth, 1));
    if (Number.isNaN(focusDate.getTime())) {
      return;
    }

    const monthLabel = new Intl.DateTimeFormat(undefined, {
      month: 'long',
      year: 'numeric',
    }).format(focusDate);

    const selectedDateIso = sanitizeIsoDate(
      reportDateInput?.value
        ?? logCalendarContainer.dataset.selectedDate
        ?? '',
    );
    logCalendarContainer.dataset.selectedDate = selectedDateIso;

    const todayIso = getTodayIso();

    logCalendarContainer.innerHTML = '';

    const header = document.createElement('div');
    header.className = 'calendar-header';

    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'calendar-nav calendar-prev';
    prevBtn.setAttribute('aria-label', 'Previous month');
    prevBtn.innerHTML = '&lsaquo;';
    prevBtn.addEventListener('click', () => {
      shiftCalendarMonth(-1);
      renderInventoryCalendar();
    });

    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'calendar-nav calendar-next';
    nextBtn.setAttribute('aria-label', 'Next month');
    nextBtn.innerHTML = '&rsaquo;';
    nextBtn.addEventListener('click', () => {
      shiftCalendarMonth(1);
      renderInventoryCalendar();
    });

    const title = document.createElement('div');
    title.className = 'calendar-title';
    title.textContent = monthLabel;

    header.append(prevBtn, title, nextBtn);
    logCalendarContainer.appendChild(header);

    const weekdayRow = document.createElement('div');
    weekdayRow.className = 'calendar-weekdays';
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    weekdays.forEach((weekday) => {
      const span = document.createElement('span');
      span.textContent = weekday;
      weekdayRow.appendChild(span);
    });
    logCalendarContainer.appendChild(weekdayRow);

    const grid = document.createElement('div');
    grid.className = 'calendar-grid';

    const firstOfMonth = new Date(Date.UTC(calendarFocusYear, calendarFocusMonth, 1));
    const startDay = firstOfMonth.getUTCDay();
    const daysInMonth = new Date(Date.UTC(calendarFocusYear, calendarFocusMonth + 1, 0)).getUTCDate();
    const daysInPrevMonth = new Date(Date.UTC(calendarFocusYear, calendarFocusMonth, 0)).getUTCDate();
    const totalCells = Math.ceil((startDay + daysInMonth) / 7) * 7;

    for (let index = 0; index < totalCells; index += 1) {
      const dayButton = document.createElement('button');
      dayButton.type = 'button';
      dayButton.className = 'calendar-day';

      let displayDay;
      let cellDate;
      let isOutside = false;

      if (index < startDay) {
        displayDay = daysInPrevMonth - (startDay - index) + 1;
        cellDate = new Date(Date.UTC(calendarFocusYear, calendarFocusMonth - 1, displayDay));
        isOutside = true;
      } else if (index >= startDay + daysInMonth) {
        displayDay = index - (startDay + daysInMonth) + 1;
        cellDate = new Date(Date.UTC(calendarFocusYear, calendarFocusMonth + 1, displayDay));
        isOutside = true;
      } else {
        displayDay = index - startDay + 1;
        cellDate = new Date(Date.UTC(calendarFocusYear, calendarFocusMonth, displayDay));
      }

      const isoValue = formatDateToISO(cellDate);
      if (!isoValue) {
        dayButton.disabled = true;
        grid.appendChild(dayButton);
        continue;
      }

      dayButton.textContent = String(displayDay);
      dayButton.setAttribute(
        'aria-label',
        new Intl.DateTimeFormat(undefined, {
          weekday: 'long',
          month: 'long',
          day: 'numeric',
          year: 'numeric',
        }).format(cellDate),
      );

      const changeCount = logDateCountsByDate.get(isoValue) ?? 0;
      if (changeCount > 0) {
        dayButton.classList.add('has-entries');
        const badge = document.createElement('span');
        badge.className = 'calendar-count';
        badge.textContent = String(changeCount);
        dayButton.appendChild(badge);
      }

      if (isoValue === selectedDateIso) {
        dayButton.classList.add('is-selected');
      }

      if (isoValue === todayIso) {
        dayButton.classList.add('is-today');
      }

      if (isOutside) {
        dayButton.classList.add('is-outside');
        dayButton.addEventListener('click', () => {
          handleCalendarDaySelection(isoValue, true);
        });
      } else {
        dayButton.addEventListener('click', () => {
          handleCalendarDaySelection(isoValue, false);
        });
      }

      grid.appendChild(dayButton);
    }

    logCalendarContainer.appendChild(grid);
  }

  function handleCalendarDaySelection(isoValue, adjustFocus) {
    if (!isoValue) {
      return;
    }

    if (adjustFocus) {
      const targetDate = createDateFromISO(isoValue);
      if (targetDate) {
        calendarFocusYear = targetDate.getUTCFullYear();
        calendarFocusMonth = targetDate.getUTCMonth();
        renderInventoryCalendar();
      }
    } else if (logCalendarContainer) {
      logCalendarContainer.dataset.selectedDate = isoValue;
      renderInventoryCalendar();
    }

    if (reportDateInput) {
      reportDateInput.value = isoValue;
    }

    if (reportForm) {
      reportForm.submit();
    }
  }

  inventoryData = normalizeInventoryData(<?= $inventoryJson; ?>);
  inventoryLogEntries = normalizeInventoryLog(<?= $inventoryLogJson; ?>);
  const selectedLogDate = sanitizeIsoDate(
    reportDateInput?.value
      ?? logCalendarContainer?.dataset.selectedDate
      ?? ''
  );
  if (selectedLogDate) {
    inventoryLogEntries = inventoryLogEntries.filter((entry) => {
      if (entry.logDate) {
        return entry.logDate === selectedLogDate;
      }
      if (entry.createdAt) {
        const entryDate = sanitizeIsoDate(String(entry.createdAt).split(' ')[0]);
        return entryDate === selectedLogDate;
      }
      return false;
    });
  }
  logDateCountsByDate = normalizeLogDateCounts(<?= $inventoryLogDateCountsJson; ?>);
  renderInventoryUI();
  renderInventoryLogTable();
  initializeInventoryCalendar();
});
</script>
<?php
$extraScripts = ob_get_clean();
include 'includes/footer.php';
