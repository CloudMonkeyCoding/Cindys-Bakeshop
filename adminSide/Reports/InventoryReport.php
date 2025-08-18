<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inventory Report</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="inventory-report flex h-screen overflow-hidden">
  <?php
  $activePage = 'reports';
  include '../sidebar.php';
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
      $inventoryData[$category][] = [$row['Name'], (int)$row['Stock_Quantity']];
  }
  ?>

  <!-- Main -->
  <main class="main flex-1 overflow-y-auto">
    <div class="header-bar"><h1>Inventory Report</h1></div>
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

    const inventoryData = <?php echo json_encode($inventoryData); ?>;

    function buildInventoryTables() {
      const container = document.getElementById("inventoryContainer");
      container.innerHTML = "";
      for (let category in inventoryData) {
        const section = document.createElement("div");
        section.innerHTML = `<h2>${category}</h2><table class="inventory-table"><thead><tr><th>Item Name</th><th>Stock</th></tr></thead><tbody></tbody></table>`;
        const tbody = section.querySelector("tbody");
        inventoryData[category].forEach(([name, stock]) => {
          const tr = document.createElement("tr");
          const tdName = `<td>${name}</td>`;
          const tdStock = `<td class="stock"><input type="text" value="${stock ?? ''}" /></td>`;
          tr.innerHTML = tdName + tdStock;
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
          input.value = "Pre-order";
          td.classList.add('stock-preorder');
        } else {
          const stock = parseInt(value);
          if (stock === 0) td.classList.add('stock-low');
          else if (stock < 10) td.classList.add('stock-medium');
          else td.classList.add('stock-ok');
        }
      });
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
      document.querySelectorAll('.stock input').forEach(input => {
        input.addEventListener('input', updateStockColors);
      });
    };
  </script>
</body>
</html>
