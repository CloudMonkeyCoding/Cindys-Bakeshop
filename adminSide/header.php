<?php
$activePage = $activePage ?? '';
$scriptDir = dirname($_SERVER['PHP_SELF']);
$adminDir = '/' . basename(__DIR__);
$subPath = trim(substr($scriptDir, strlen($adminDir)), '/');
$depth = $subPath === '' ? 0 : substr_count($subPath, '/') + 1;
$prefix = str_repeat('../', $depth);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle ?? 'Admin'); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $prefix ?>css/admin.css">
  <?= $extraHead ?? '' ?>
</head>
<body class="<?= htmlspecialchars($bodyClass ?? '') ?>">
