<?php
$activePage = 'products';
require '../../PHP/db_connect.php';
require '../../PHP/product_functions.php';
$products = [];
if ($pdo) {
    $products = getAllProducts($pdo);
} else {
    error_log('Database connection failed in ManageProduct.php');
}
$pageTitle = 'Manage Products';
$headerTitle = 'Manage Products';
$bodyClass = 'manage-product flex h-screen overflow-hidden';
include '../header.php';
include $prefix . 'sidebar.php';
?>

<!-- Main -->
<main class="main flex-1 overflow-y-auto">
  <?php include $prefix . 'topbar.php'; ?>
  <div class="content">
    <div class="toolbar">
      <input type="text" id="search" placeholder="Search product name...">
      <select id="category">
        <option value="">All Categories</option>
        <option value="Cake">Cake</option>
        <option value="Bread">Bread</option>
        <option value="Pastry">Pastry</option>
      </select>
      <a href="AddNewProduct.php" class="btn-add">➕ Add New Product</a>
    </div>

    <div class="product-grid" id="productGrid"></div>
    <div class="pagination" id="paginationControls"></div>
  </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
  <form id="editForm" class="bg-white p-6 rounded space-y-2" enctype="multipart/form-data">
    <input type="hidden" name="product_id" id="editProductId">
    <div>
      <label class="block font-semibold">Name</label>
      <input type="text" name="name" id="editName" class="w-full border rounded px-2 py-1">
    </div>
    <div>
      <label class="block font-semibold">Category</label>
      <select name="category" id="editCategory" class="w-full border rounded px-2 py-1">
        <option value="Bread">Bread</option>
        <option value="Cake">Cake</option>
        <option value="Pastry">Pastry</option>
      </select>
    </div>
    <div>
      <label class="block font-semibold">Description</label>
      <textarea name="description" id="editDescription" rows="3" class="w-full border rounded px-2 py-1"></textarea>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block font-semibold">Price</label>
        <input type="number" name="price" id="editPrice" class="w-full border rounded px-2 py-1">
      </div>
      <div>
        <label class="block font-semibold">Stock</label>
        <input type="number" name="stock_quantity" id="editStock" class="w-full border rounded px-2 py-1">
      </div>
    </div>
    <div>
      <label class="block font-semibold">Image</label>
      <input type="file" name="image" id="editImage" class="w-full border rounded px-2 py-1">
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
      <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
    </div>
  </form>
</div>

<script>
  let products = <?php
    echo json_encode(array_map(function($p) {
        return [
            'id' => $p['Product_ID'],
            'name' => $p['Name'],
            'price' => $p['Price'],
            'category' => $p['Category'],
            'image' => $p['Image_Path'] ? 'uploads/' . $p['Image_Path'] : "../images/cindy's logo.png",
            'stock' => $p['Stock_Quantity'],
            'description' => $p['Description']
        ];
    }, $products));
  ?>;

  const itemsPerPage = 24;
  let currentPage = 1;

  function displayProducts() {
    const grid = document.getElementById("productGrid");
    grid.innerHTML = "";
    const search = document.getElementById("search").value.toLowerCase();
    const category = document.getElementById("category").value;

    const filtered = products.filter(p =>
      p.name.toLowerCase().includes(search) &&
      (category === "" || p.category === category)
    );

    const start = (currentPage - 1) * itemsPerPage;
    const paginated = filtered.slice(start, start + itemsPerPage);

    paginated.forEach(p => {
      const stockClass =
        p.stock === 0 ? 'low' :
        p.stock < 10 ? 'medium' : 'high';

      grid.innerHTML += `
        <div class="product-card">
          <img src="${p.image}" alt="${p.name}" loading="lazy">
          <div class="price">₱${p.price}</div>
          <div class="stock ${stockClass}">Stock: ${p.stock}</div>
          <div style="font-weight: bold;">${p.name}</div>
          <button onclick="editProduct(${p.id})">Edit</button>
          <button onclick="deleteProduct(${p.id})">Delete</button>
        </div>
      `;
    });

    setupPagination(filtered.length);
  }

  function setupPagination(totalItems) {
    const controls = document.getElementById("paginationControls");
    controls.innerHTML = "";
    const totalPages = Math.ceil(totalItems / itemsPerPage);

    for (let i = 1; i <= totalPages; i++) {
      controls.innerHTML += `<button onclick="goToPage(${i})">${i}</button>`;
    }
  }

  function goToPage(page) {
    currentPage = page;
    displayProducts();
  }

  function editProduct(id) {
    const product = products.find(p => p.id === id);
    if (!product) return;

    document.getElementById('editProductId').value = product.id;
    document.getElementById('editName').value = product.name;
    document.getElementById('editCategory').value = product.category;
    document.getElementById('editDescription').value = product.description;
    document.getElementById('editPrice').value = product.price;
    document.getElementById('editStock').value = product.stock;
    document.getElementById('editImage').value = '';
    document.getElementById('editModal').classList.remove('hidden');
  }

  function closeEditModal() {
    document.getElementById('editForm').reset();
    document.getElementById('editModal').classList.add('hidden');
  }

  document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'update');

    fetch('../../PHP/product_functions.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        closeEditModal();
        reloadProducts();
      } else {
        alert('Update failed');
      }
    });
  });

  function deleteProduct(id) {
    if (!confirm(`Are you sure you want to delete product #${id}?`)) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('product_id', id);
    fetch('../../PHP/product_functions.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          reloadProducts();
        } else {
          alert('Deletion failed');
        }
      });
  }

  function reloadProducts() {
    const fd = new FormData();
    fd.append('action', 'getAll');
    fetch('../../PHP/product_functions.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        products = data.map(p => ({
          id: p.Product_ID,
          name: p.Name,
          price: p.Price,
          category: p.Category,
          image: p.Image_Path ? 'uploads/' + p.Image_Path : "../images/cindy's logo.png",
          stock: p.Stock_Quantity,
          description: p.Description
        }));
        displayProducts();
      });
  }

  document.getElementById("search").addEventListener("input", () => {
    currentPage = 1;
    displayProducts();
  });

  document.getElementById("category").addEventListener("change", () => {
    currentPage = 1;
    displayProducts();
  });

  displayProducts();
</script>
</body>
</html>
