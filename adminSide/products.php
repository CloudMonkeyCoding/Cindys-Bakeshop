<?php
require_once '../PHP/db_connect.php';
require_once '../PHP/product_functions.php';

$activePage = 'products';
$pageTitle = "Products - Cindy's Bakeshop";

$products = [];
if ($pdo) {
    $products = getAllProducts($pdo);
}

function productImagePath($product) {
    if (!empty($product['Image_Path'])) {
        return '../adminSide/products/uploads/' . $product['Image_Path'];
    }
    return "https://via.placeholder.com/80x80?text=No+Image";
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Products</h1>
    <a href="edit-profile.php" class="user-info">
      <span>Admin</span>
      <img src="https://i.pravatar.cc/80" alt="Admin avatar">
    </a>
  </div>

  <div class="table-container">
    <div class="table-actions">
      <button class="btn btn-primary" id="openModal"><i class="fa fa-plus"></i> Add New Product</button>
      <input type="text" id="searchProduct" placeholder="🔍 Search product...">
      <select id="filterCategory">
        <option value="all">All Categories</option>
        <option value="Bread">Bread</option>
        <option value="Cake">Cake</option>
        <option value="Pastry">Pastry</option>
      </select>
    </div>
    <table id="productTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Product</th>
          <th>Stock</th>
          <th>Price</th>
          <th>Category</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr>
            <td colspan="7" class="table-empty">No products available.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($products as $index => $product): ?>
            <tr data-product-id="<?= $product['Product_ID']; ?>" data-category="<?= htmlspecialchars($product['Category'] ?? ''); ?>">
              <td><?= $index + 1; ?></td>
              <td><img src="<?= htmlspecialchars(productImagePath($product)); ?>" alt="<?= htmlspecialchars($product['Name']); ?>" style="width:60px;height:60px;border-radius:8px;object-fit:cover;"></td>
              <td><?= htmlspecialchars($product['Name']); ?></td>
              <td><?= number_format($product['Stock_Quantity'] ?? 0); ?></td>
              <td>₱<?= number_format($product['Price'], 2); ?></td>
              <td><?= htmlspecialchars($product['Category']); ?></td>
              <td style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn btn-secondary btn-edit" data-id="<?= $product['Product_ID']; ?>">Edit</button>
                <button class="btn btn-muted btn-delete" data-id="<?= $product['Product_ID']; ?>">Delete</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal" id="productModal">
  <div class="modal-content">
    <h2 id="modalTitle">Add New Product</h2>
    <form id="productForm" enctype="multipart/form-data">
      <input type="hidden" name="product_id" id="productId">
      <div class="form-group">
        <label for="productName">Product Name</label>
        <input type="text" name="name" id="productName" required>
      </div>
      <div class="form-group">
        <label for="productDescription">Description</label>
        <textarea name="description" id="productDescription" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label for="productCategory">Category</label>
        <select name="category" id="productCategory">
          <option value="Bread">Bread</option>
          <option value="Cake">Cake</option>
          <option value="Pastry">Pastry</option>
        </select>
      </div>
      <div class="form-group">
        <label for="productPrice">Price</label>
        <input type="number" step="0.01" min="0" name="price" id="productPrice" required>
      </div>
      <div class="form-group">
        <label for="productStock">Stock</label>
        <input type="number" min="0" name="stock_quantity" id="productStock" required>
      </div>
      <div class="form-group">
        <label for="productImage">Image</label>
        <input type="file" name="image" id="productImage" accept="image/*">
      </div>
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <button type="button" class="btn btn-muted" id="closeModal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<?php
$productsJson = json_encode(array_map(static function ($product) {
    return [
        'id' => (int)$product['Product_ID'],
        'name' => $product['Name'],
        'description' => $product['Description'],
        'price' => (float)$product['Price'],
        'stock' => (int)$product['Stock_Quantity'],
        'category' => $product['Category'],
        'image' => empty($product['Image_Path']) ? '' : '../adminSide/products/uploads/' . $product['Image_Path'],
    ];
}, $products));
$extraScripts = <<<JS
<script>
  let products = $productsJson;
  const modal = document.getElementById('productModal');
  const openModalBtn = document.getElementById('openModal');
  const closeModalBtn = document.getElementById('closeModal');
  const modalTitle = document.getElementById('modalTitle');
  const productForm = document.getElementById('productForm');
  const productIdField = document.getElementById('productId');
  const productName = document.getElementById('productName');
  const productDescription = document.getElementById('productDescription');
  const productCategory = document.getElementById('productCategory');
  const productPrice = document.getElementById('productPrice');
  const productStock = document.getElementById('productStock');
  const productImage = document.getElementById('productImage');
  const searchBox = document.getElementById('searchProduct');
  const filterCategory = document.getElementById('filterCategory');
  const productTableBody = document.querySelector('#productTable tbody');

  function renderProducts(list) {
    if (!productTableBody) return;
    productTableBody.innerHTML = '';
    if (!list.length) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = 7;
      cell.className = 'table-empty';
      cell.textContent = 'No products available.';
      row.appendChild(cell);
      productTableBody.appendChild(row);
      return;
    }

    list.forEach((product, index) => {
      const row = document.createElement('tr');
      row.dataset.productId = product.id;
      row.dataset.category = product.category || '';
      row.innerHTML = `
        <td>\${index + 1}</td>
        <td><img src="\${product.image || 'https://via.placeholder.com/80x80?text=No+Image'}" alt="\${product.name}" style="width:60px;height:60px;border-radius:8px;object-fit:cover;"></td>
        <td>\${product.name}</td>
        <td>\${product.stock}</td>
        <td>₱\${Number(product.price).toFixed(2)}</td>
        <td>\${product.category || ''}</td>
        <td style="display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn btn-secondary btn-edit" data-id="\${product.id}">Edit</button>
          <button class="btn btn-muted btn-delete" data-id="\${product.id}">Delete</button>
        </td>
      `;
      productTableBody.appendChild(row);
    });

    attachRowHandlers();
  }

  function openModal(isEdit = false, product = null) {
    modal.classList.add('active');
    modalTitle.textContent = isEdit ? 'Edit Product' : 'Add New Product';
    productForm.reset();
    productImage.value = '';
    if (isEdit && product) {
      productIdField.value = product.id;
      productName.value = product.name;
      productDescription.value = product.description || '';
      productCategory.value = product.category || 'Bread';
      productPrice.value = product.price;
      productStock.value = product.stock;
    } else {
      productIdField.value = '';
      productCategory.value = 'Bread';
    }
  }

  function closeModal() {
    modal.classList.remove('active');
    productForm.reset();
  }

  openModalBtn.addEventListener('click', () => openModal(false));
  closeModalBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });

  function attachRowHandlers() {
    document.querySelectorAll('.btn-edit').forEach(button => {
      button.addEventListener('click', () => {
        const id = Number(button.dataset.id);
        const product = products.find(p => p.id === id);
        if (product) openModal(true, product);
      });
    });

    document.querySelectorAll('.btn-delete').forEach(button => {
      button.addEventListener('click', async () => {
        const id = Number(button.dataset.id);
        if (!confirm('Delete this product?')) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('product_id', id);
        const response = await fetch('../PHP/product_functions.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!result.success) {
          alert('Failed to delete product');
          return;
        }
        await reloadProducts();
      });
    });
  }

  async function reloadProducts() {
    const formData = new FormData();
    formData.append('action', 'getAll');
    const response = await fetch('../PHP/product_functions.php', { method: 'POST', body: formData });
    const list = await response.json();
    products = list.map(item => ({
      id: Number(item.Product_ID),
      name: item.Name,
      description: item.Description,
      price: Number(item.Price),
      stock: Number(item.Stock_Quantity),
      category: item.Category,
      image: item.Image_Path ? '../adminSide/products/uploads/' + item.Image_Path : ''
    }));
    applyFilters();
  }

  productForm.addEventListener('submit', async event => {
    event.preventDefault();
    const isEdit = Boolean(productIdField.value);
    const formData = new FormData(productForm);
    formData.append('action', isEdit ? 'update' : 'add');
    if (isEdit) {
      formData.append('product_id', productIdField.value);
    }
    const response = await fetch('../PHP/product_functions.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    if (!result.success) {
      alert('Unable to save product. Please check your inputs.');
      return;
    }
    await reloadProducts();
    closeModal();
  });

  function applyFilters() {
    const query = searchBox.value.toLowerCase();
    const category = filterCategory.value;
    const filtered = products.filter(product => {
      const matchesSearch = product.name.toLowerCase().includes(query) || (product.description || '').toLowerCase().includes(query);
      const matchesCategory = category === 'all' || product.category === category;
      return matchesSearch && matchesCategory;
    });
    renderProducts(filtered);
  }

  searchBox.addEventListener('input', applyFilters);
  filterCategory.addEventListener('change', applyFilters);

  attachRowHandlers();
</script>
JS;
include 'includes/footer.php';
