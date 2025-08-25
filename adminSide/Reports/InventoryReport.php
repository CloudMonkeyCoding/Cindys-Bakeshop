<?php
$activePage = 'reports';
require_once '../../PHP/db_connect.php';
require_once '../../PHP/inventory_functions.php';

$inventoryRows = [];
if ($pdo) {
    $inventoryRows = getInventoryWithProducts($pdo);
} else {
    error_log('Database connection failed in InventoryReport.php');
}
$inventoryData = [];
foreach ($inventoryRows as $row) {
    $category = $row['Category'] ?? 'Uncategorized';
$inventoryData[$category][] = [
    'id' => $row['Product_ID'],
    'name' => $row['Name'],
    'stock' => $row['Stock_Quantity']
];
}

$pageTitle = 'Inventory Report';
$headerTitle = 'Inventory Report';
$bodyClass = 'inventory-report';
include '../header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>

  <!-- Main -->
  <main class="main flex-1 overflow-y-auto">
    <?php include $prefix . 'topbar.php'; ?>
    <div class="content">
      <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search product..." onkeyup="filterInventory()">
        <button onclick="exportToPDF()">📄 Export to PDF</button>
      </div>

      <div id="inventoryContainer">
        <!-- Tables inserted here by script -->
      </div>
    </div>
  </main>

  <!-- Script -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script>

      let inventoryData = <?php echo json_encode($inventoryData); ?>;

      function buildInventoryTables() {
        const container = document.getElementById("inventoryContainer");
        container.innerHTML = "";
        for (let category in inventoryData) {
          const section = document.createElement("div");
          section.innerHTML = `<h2>${category}</h2><table class=\"inventory-table\"><thead><tr><th>Item Name</th><th>Stock</th><th>Actions</th></tr></thead><tbody></tbody></table>`;
          const tbody = section.querySelector("tbody");
          inventoryData[category].forEach(item => {
            const { id, name, stock } = item;
            const tr = document.createElement("tr");
            const tdName = `<td>${name}</td>`;
            const tdStock = `<td class=\"stock\"><input type=\"text\" value=\"${stock ?? ''}\" data-id=\"${id}\" /></td>`;
            const tdAction = `<td><button class=\"minus-btn\">-</button><button class=\"plus-btn\">+</button></td>`;
            tr.innerHTML = tdName + tdStock + tdAction;
            tbody.appendChild(tr);
          });
          container.appendChild(section);
        }
      }

      function updateStockColors() {
        document.querySelectorAll('.stock input').forEach(input => {
          const td = input.parentElement;
          td.className = 'stock';
          const value = input.value.trim();

          if (value === "") {
            td.classList.add('stock-preorder');
          } else {
            const stock = parseInt(value);
            if (stock === 0) td.classList.add('stock-low');
            else if (stock < 10) td.classList.add('stock-medium');
            else td.classList.add('stock-ok');
          }
        });
      }

      function addInputListeners() {
        document.querySelectorAll('.stock input').forEach(input => {
          input.addEventListener('input', updateStockColors);
          input.addEventListener('change', () => {
            saveStock(input.dataset.id, input.value.trim());
          });
        });
      }

      function adjustStock(btn, delta) {
        const tr = btn.closest('tr');
        const input = tr.querySelector('.stock input');
        let value = parseInt(input.value) || 0;
        value += delta;
        if (value < 0) value = 0;
        input.value = value;
        updateStockColors();
        saveStock(input.dataset.id, input.value.trim());
      }

      function addAdjustListeners() {
        document.querySelectorAll('.minus-btn').forEach(btn => {
          btn.addEventListener('click', () => adjustStock(btn, -1));
        });
        document.querySelectorAll('.plus-btn').forEach(btn => {
          btn.addEventListener('click', () => adjustStock(btn, 1));
        });
      }

      async function saveStock(id, stock) {
        const formData = new FormData();
        formData.append('product_id', id);
        formData.append('stock_quantity', stock);
        try {
          const response = await fetch('../../PHP/inventory_functions.php', {
            method: 'POST',
            body: formData
          });
          const result = await response.json();
          if (result.success) {
            inventoryData = result.data;
            buildInventoryTables();
            updateStockColors();
            addInputListeners();
            addAdjustListeners();
          }
        } catch (error) {
          console.error('Error updating stock', error);
        }
      }

      function filterInventory() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#inventoryContainer table tbody tr');
        rows.forEach(row => {
          const item = row.cells[0].innerText.toLowerCase();
          row.style.display = item.includes(input) ? '' : 'none';
        });
      }

    async function exportToPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.text("Inventory Report", 20, 20);
      let y = 30;

      document.querySelectorAll("#inventoryContainer h2").forEach(section => {
        doc.setFontSize(14);
        doc.text(section.innerText, 20, y);
        y += 10;

        const rows = section.nextElementSibling.querySelectorAll("tbody tr");
        rows.forEach(row => {
          const name = row.cells[0].innerText;
          const stock = row.cells[1].querySelector("input").value;
          doc.setFontSize(12);
          doc.text(`- ${name}: ${stock}`, 25, y);
          y += 7;
        });
        y += 5;
      });

      doc.save("Inventory_Report.pdf");
    }

      window.onload = () => {
        buildInventoryTables();
        updateStockColors();
        addInputListeners();
        addAdjustListeners();
      };
  </script>
</body>
</html>
