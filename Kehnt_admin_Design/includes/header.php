<?php
$pageTitle = $pageTitle ?? "Cindy's Bakeshop Admin";
$bodyClass = $bodyClass ?? '';
$extraHead = $extraHead ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle); ?></title>
  <link rel="stylesheet" href="assets/css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Zl7U6apORdzdrIW6DCeA44y7NysGEKMluSleqU9jwELyhl725LLJoPLD114F8CbnMD4PlzyBbs6k8ZZr3SuF2g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <?= $extraHead ?>
</head>
<body class="<?= htmlspecialchars($bodyClass); ?>">
