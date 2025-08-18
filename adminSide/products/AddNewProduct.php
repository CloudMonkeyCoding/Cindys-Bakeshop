<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New Product</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
  <body>
    <div class="flex h-screen overflow-hidden">
      <?php
      require '../../PHP/db_connect.php';
      require '../../PHP/product_functions.php';

      $message = '';
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          $name = $_POST['productName'] ?? '';
          $category = $_POST['category'] ?? '';
          $description = $_POST['description'] ?? '';
          $price = $_POST['price'] ?? 0;
          $quantity = $_POST['quantity'] ?? 0;
          $imageFile = $_FILES['image'] ?? null;

          if ($pdo) {
              addProduct($pdo, $name, $description, $price, $quantity, $category, $imageFile);
              $message = 'Product added successfully.';
          } else {
              $message = 'Database connection failed.';
              error_log('Database connection failed in AddNewProduct.php');
          }
      }

      $activePage = 'products';
      include '../sidebar.php';
      ?>
      <main class="flex-1 overflow-y-auto">
      <div class="header-bar">
        <h1>Add New Product</h1>
        <div class="flex gap-4 items-center">
          <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <img src="avatar.png" alt="User Avatar" class="h-10 w-10 rounded-full border border-gray-300" />
        </div>
      </div>
      <div class="p-6">
          <a href="ManageProduct.php" class="text-blue-600 hover:underline">&larr; Back to Products</a>
          <?php if ($message) { echo '<p class="text-green-600">' . htmlspecialchars($message) . '</p>'; } ?>
          <form class="mt-4 space-y-4" method="POST" enctype="multipart/form-data">
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
