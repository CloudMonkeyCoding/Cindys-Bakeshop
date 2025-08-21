<?php
require_once '../PHP/db_connect.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sqlFile = __DIR__ . '/../Database/cindys_bakeshop.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
            $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)));
            foreach ($statements as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
            $message = 'Database reset successfully.';
        } catch (PDOException $e) {
            $message = 'Error resetting database: ' . $e->getMessage();
        }
    } else {
        $message = 'SQL file not found.';
    }
}

$activePage = 'reset';
$pageTitle = 'Reset Database';
$headerTitle = 'Reset Database';
$bodyClass = 'dashboard-page';
include 'header.php';
?>
<div class="flex min-h-screen">
  <?php include $prefix . 'sidebar.php'; ?>
  <main class="flex-1 p-6 overflow-y-auto">
    <?php include $prefix . 'topbar.php'; ?>
    <div class="mt-6">
      <?php if ($message): ?>
        <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('Are you sure you want to reset the database?');">
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Reset Database</button>
      </form>
    </div>
  </main>
</div>
</body>
</html>
