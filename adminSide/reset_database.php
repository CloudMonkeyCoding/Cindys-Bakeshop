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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Database</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body class="dashboard-page">
  <div class="flex min-h-screen">
    <?php
    $activePage = 'reset';
    include 'sidebar.php';
    ?>
    <main class="flex-1 p-6">
      <h1 class="text-2xl font-bold mb-4">Reset Database</h1>
      <?php if ($message): ?>
        <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('Are you sure you want to reset the database?');">
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Reset Database</button>
      </form>
    </main>
  </div>
</body>
</html>
