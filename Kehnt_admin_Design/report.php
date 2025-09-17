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
}

$categoryLabelsJson = json_encode(array_keys($categoryTotals));
$categoryValuesJson = json_encode(array_values($categoryTotals));

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
      <div class="value"><?= number_format(count($inventoryData)); ?></div>
      <div class="meta">Grouped by product type</div>
    </div>
    <div class="stat-card">
      <h3>Tracked SKUs</h3>
      <div class="value"><?= number_format($totalTracked); ?></div>
      <div class="meta">Products with stock records</div>
    </div>
    <div class="stat-card">
      <h3>Pre-order Items</h3>
      <div class="value"><?= number_format($preOrderCount); ?></div>
      <div class="meta">Stock marked as TBD</div>
    </div>
    <div class="stat-card">
      <h3>Low Stock Alerts</h3>
      <div class="value"><?= number_format($lowStockCount); ?></div>
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
    <div id="inventoryContainer">
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
    </div>
  </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
  const categoryLabels = $categoryLabelsJson.length ? $categoryLabelsJson : ['No Data'];
  const categoryValues = $categoryValuesJson.length ? $categoryValuesJson : [0];

  if (document.getElementById('categoryChart')) {
    new Chart(document.getElementById('categoryChart'), {
      type: 'bar',
      data: {
        labels: categoryLabels,
        datasets: [{
          label: 'Units on Hand',
          data: categoryValues,
          backgroundColor: '#27ae60'
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }

  const searchInput = document.getElementById('inventorySearch');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.toLowerCase();
      document.querySelectorAll('#inventoryContainer table tbody tr').forEach(row => {
        const name = row.cells[0]?.textContent.toLowerCase() || '';
        row.style.display = name.includes(query) ? '' : 'none';
      });
    });
  }

  const exportBtn = document.getElementById('exportInventory');
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.setFontSize(16);
      doc.text('Inventory Report', 14, 20);

      let y = 30;
      document.querySelectorAll('#inventoryContainer h2').forEach(section => {
        if (y > 260) {
          doc.addPage();
          y = 20;
        }

        doc.setFontSize(14);
        doc.text(section.textContent.trim(), 14, y);
        y += 6;

        const table = section.nextElementSibling;
        if (table) {
          const rows = [];
          table.querySelectorAll('tbody tr').forEach(tr => {
            const cells = Array.from(tr.cells).map(td => td.textContent.trim());
            rows.push(cells);
          });

          doc.autoTable({
            startY: y,
            head: [['Item Name', 'Stock']],
            body: rows,
            theme: 'grid',
            styles: { fontSize: 10 }
          });

          y = doc.lastAutoTable.finalY + 10;
        }
      });

      doc.save('inventory-report.pdf');
    });
  }
</script>
JS;
include 'includes/footer.php';
