<?php
require_once '../PHP/db_connect.php';
require_once '../PHP/inventory_functions.php';
require_once '../PHP/transaction_functions.php';

$activePage = 'reports';
$pageTitle = "Reports - Cindy's Bakeshop";

$inventoryData = [];
$totalRevenue = 0;
$monthlyTotals = [];

if ($pdo) {
    $rows = getInventoryWithProducts($pdo);
    foreach ($rows as $row) {
        $category = $row['Category'] ?? 'Uncategorized';
        $inventoryData[$category][] = [
            'id' => $row['Product_ID'],
            'name' => $row['Name'],
            'stock' => $row['Stock_Quantity']
        ];
    }

    $stmtRevenue = $pdo->query("SELECT COALESCE(SUM(Amount_Paid),0) FROM transaction");
    $totalRevenue = (float)($stmtRevenue ? $stmtRevenue->fetchColumn() : 0);

    $stmtMonthly = $pdo->query("SELECT DATE_FORMAT(Payment_Date, '%Y-%m') AS period, COALESCE(SUM(Amount_Paid),0) AS total FROM transaction WHERE Payment_Date IS NOT NULL GROUP BY period ORDER BY period DESC LIMIT 6");
    if ($stmtMonthly) {
        $rows = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_reverse($rows);
        foreach ($rows as $row) {
            $monthlyTotals[] = [
                'label' => date('M Y', strtotime($row['period'] . '-01')),
                'value' => (float)$row['total']
            ];
        }
    }
}

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script><script src=\"https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js\"></script>';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Reports Overview</h1>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <button class="btn btn-primary" id="exportInventory">Export Inventory PDF</button>
    </div>
  </div>

  <section class="stats-grid columns-4" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
    <div class="stat-card">
      <h3>Total Revenue</h3>
      <div class="value">₱<?= number_format($totalRevenue, 2); ?></div>
      <div class="meta">Sum of all transactions</div>
    </div>
    <div class="stat-card">
      <h3>Inventory Categories</h3>
      <div class="value"><?= count($inventoryData); ?></div>
      <div class="meta">Grouped by product category</div>
    </div>
    <div class="stat-card">
      <h3>Tracked Products</h3>
      <div class="value"><?= array_sum(array_map('count', $inventoryData)); ?></div>
      <div class="meta">Items with recorded stock</div>
    </div>
    <div class="stat-card">
      <h3>Last Update</h3>
      <div class="value" style="font-size:20px;">
        <?= !empty($monthlyTotals) ? end($monthlyTotals)['label'] : date('M Y'); ?>
      </div>
      <div class="meta">Latest recorded sales month</div>
    </div>
  </section>

  <div class="stats-grid columns-4" style="margin-top:24px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Monthly Revenue</h2>
      <canvas id="monthlyChart" height="220"></canvas>
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
          <h2 style="margin-top:24px;"><?= htmlspecialchars($category); ?></h2>
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
                  <td><?= is_null($item['stock']) ? 'Pre-order' : number_format($item['stock']); ?></td>
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
$labelsJson = json_encode(array_column($monthlyTotals, 'label'));
$valuesJson = json_encode(array_map(function ($item) { return round($item['value'], 2); }, $monthlyTotals));

$extraScripts = <<<JS
<script>
  const labels = $labelsJson.length ? $labelsJson : ['No Data'];
  const values = $valuesJson.length ? $valuesJson : [0];
  if (document.getElementById('monthlyChart')) {
    new Chart(document.getElementById('monthlyChart'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Revenue (₱)',
          data: values,
          backgroundColor: '#e74c3c'
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { callback: value => `₱\${Number(value).toLocaleString()}` }
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

  document.getElementById('exportInventory').addEventListener('click', () => {
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
      doc.text(section.textContent, 14, y);
      y += 6;
      const rows = [];
      section.nextElementSibling.querySelectorAll('tbody tr').forEach(tr => {
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
    });
    doc.save('inventory-report.pdf');
  });
</script>
JS;
include 'includes/footer.php';
