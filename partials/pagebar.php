<?php
// partials/pagebar.php
// Truyền vào các biến sau trước khi include:
// $page_title (bắt buộc), $page_subtitle (tuỳ chọn), $page_actions (mảng nút hành động tuỳ chọn)
// Ví dụ $page_actions = [
//   ['href'=>'register.php','text'=>'+ Add Student','class'=>'btn btn-success'],
//   ['href'=>'#','text'=>'Export CSV','class'=>'btn btn-outline-primary']
// ];
?>
<div class="pagebar py-3 mb-3">
  <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
    <div>
      <h1 class="title h4"><?= htmlspecialchars($page_title ?? 'Page') ?></h1>
      <?php if (!empty($page_subtitle)): ?>
        <p class="subtitle"><?= htmlspecialchars($page_subtitle) ?></p>
      <?php endif; ?>
    </div>
    <?php if (!empty($page_actions) && is_array($page_actions)): ?>
      <div class="actions d-flex gap-2">
        <?php foreach($page_actions as $btn): ?>
          <a href="<?= htmlspecialchars($btn['href'] ?? '#') ?>" class="<?= htmlspecialchars($btn['class'] ?? 'btn btn-primary') ?>">
            <?= htmlspecialchars($btn['text'] ?? 'Action') ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
