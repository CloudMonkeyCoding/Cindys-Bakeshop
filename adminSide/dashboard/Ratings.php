<?php
require '../../PHP/db_connect.php';
require '../../PHP/product_ratings_functions.php';

function renderStars($rating) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5;
    $empty = 5 - $full - ($half ? 1 : 0);
    return str_repeat('★', $full) . ($half ? '½' : '') . str_repeat('☆', $empty);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rating_id']) && $pdo) {
    deleteProductRatingById($pdo, $_POST['delete_rating_id']);
    header('Location: Ratings.php');
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ! $pdo) {
    error_log('Database connection failed in Ratings.php');
}

$ratings = $pdo ? getAllProductRatings($pdo) : [];
$activePage = 'ratings';
$pageTitle = 'Product Ratings - Admin';
$headerTitle = 'Product Ratings';
include '../header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>
      <main class="flex-1 overflow-y-auto">
        <?php include $prefix . 'topbar.php'; ?>

        <div class="p-6">
          <table class="table w-full text-sm text-left bg-white rounded shadow">
        <thead>
          <tr>
            <th>Product</th>
            <th>Average Rating</th>
            <th>Total Review</th>
            <th>Comments</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ratings as $rating): ?>
          <tr>
            <td><?= htmlspecialchars($rating['Product_Name']) ?></td>
            <td>
              <div class="stars"><?= renderStars($rating['Average_Rating']) ?></div>
              <small><?= htmlspecialchars($rating['Average_Rating']) ?> / 5</small>
            </td>
            <td><?= htmlspecialchars($rating['Total_Review']) ?></td>
            <td><?= htmlspecialchars($rating['Comments']) ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Delete rating for <?= htmlspecialchars($rating['Product_Name']) ?>?');">
                <input type="hidden" name="delete_rating_id" value="<?= $rating['Rating_ID'] ?>">
                <button type="submit" class="btn-delete">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
      </main>
    </div>


</body>
</html>
