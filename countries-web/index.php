<?php
declare(strict_types=1);

$svgPath = __DIR__ . '/assets/svg/world-map.svg';
$svg = is_file($svgPath) ? file_get_contents($svgPath) : null;
if (is_string($svg)) {
    // Inline SVG inside HTML: remove XML declaration / DOCTYPE if present.
    $svg = preg_replace('/<\?xml[^>]*>\s*/i', '', $svg) ?? $svg;
    $svg = preg_replace('/<!DOCTYPE[^>]*>\s*/i', '', $svg) ?? $svg;
}

require __DIR__ . '/templates/header.php';
?>

<div class="card mapCard">
  <div class="hd">
    <h2>World Map</h2>
    <div class="status">Click quốc gia để xem thông tin chi tiết</div>
  </div>
  <div class="bd">
    <div class="mapWrap" id="mapRoot">
      <?php if ($svg): ?>
        <?= $svg ?>
      <?php else: ?>
        <div class="countryCard">
          <h3>Missing SVG</h3>
          <div class="status">Không tìm thấy file: <code>assets/svg/world-map.svg</code></div>
        </div>
      <?php endif; ?>

      <div id="countryInfoPanel" class="infoPanel hidden">
        <button id="closeInfoPanelBtn" class="panelCloseBtn" type="button" aria-label="Close panel">×</button>
        <?php require __DIR__ . '/templates/country-card.php'; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>

