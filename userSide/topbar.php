<?php
$backLink = $backLink ?? 'MENU.html';
$backText = $backText ?? 'Back';
$searchPlaceholder = $searchPlaceholder ?? 'Search...';
?>
<div class="top-bar">
  <a href="<?= htmlspecialchars($backLink) ?>">&larr; <?= htmlspecialchars($backText) ?></a>
  <div class="search-box">
    <input id="searchInput" type="text" placeholder="<?= htmlspecialchars($searchPlaceholder) ?>">
  </div>
</div>
