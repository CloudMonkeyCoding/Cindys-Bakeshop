<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require '../../PHP/db_connect.php';
require '../../PHP/product_functions.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $message = 'Invalid CSRF token.';
        error_log('CSRF token mismatch in AddNewProduct.php');
    } else {
        $name = filter_input(INPUT_POST, 'productName', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $price = $price !== false ? $price : 0;
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        $quantity = $quantity !== false ? $quantity : 0;
        $imageFile = $_FILES['image'] ?? null;

        if ($pdo) {
            addProduct($pdo, $name, $description, $price, $quantity, $category, $imageFile);
            $message = 'Product added successfully.';
        } else {
            $message = 'Database connection failed.';
            error_log('Database connection failed in AddNewProduct.php');
        }
    }
}

$activePage = 'products';
$pageTitle = 'Add New Product';
$headerTitle = 'Add New Product';
include '../header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>
  <main class="flex-1 overflow-y-auto">
    <?php include $prefix . 'topbar.php'; ?>
    <div class="p-6">
      <a href="ManageProduct.php" class="text-blue-600 hover:underline">&larr; Back to Products</a>
      <?php if ($message) { echo '<p class="text-green-600">' . htmlspecialchars($message) . '</p>'; } ?>
      <form class="mt-4 space-y-4" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div>
          <label for="productName" class="block font-semibold">Product Name</label>
          <input type="text" id="productName" name="productName" class="w-full border rounded px-2 py-1">
        </div>
        <div>
          <label for="category" class="block font-semibold">Category</label>
          <select id="category" name="category" class="w-full border rounded px-2 py-1">
            <option value="">Select Category</option>
            <option value="Bread">Bread</option>
            <option value="Cake">Cake</option>
            <option value="Pastry">Pastry</option>
          </select>
        </div>
        <div>
          <label for="description" class="block font-semibold">Description</label>
          <textarea id="description" name="description" rows="5" class="w-full border rounded px-2 py-1"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="price" class="block font-semibold">Price</label>
            <input type="number" id="price" name="price" class="w-full border rounded px-2 py-1">
          </div>
          <div>
            <label for="quantity" class="block font-semibold">Quantity</label>
            <input type="number" id="quantity" name="quantity" class="w-full border rounded px-2 py-1">
          </div>
        </div>
        <div>
          <label for="image" class="block font-semibold">Image</label>
          <input type="file" id="image" name="image" class="w-full border rounded px-2 py-1">
        </div>
        <div>
          <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
        </div>
      </form>
    </div>
  </main>
</div>
</body>
</html>
