<?php
/**
 * Vehicle analytics widgets — most viewed & most clicked
 * Expects: $leadModel (Lead), $carModel (Car)
 */
$mostViewed = $leadModel->getMostViewedVehicles(10);
$mostClicked = $leadModel->getMostClickedVehicles(10);

$analyticsCarTitles = [];
foreach (array_merge($mostViewed, $mostClicked) as $row) {
    $cid = (int) ($row['car_id'] ?? 0);
    if ($cid && !isset($analyticsCarTitles[$cid])) {
        $c = $carModel->getById($cid, true);
        $analyticsCarTitles[$cid] = $c['title'] ?? 'Car #' . $cid;
    }
}
?>
<div class="crm-grid crm-grid--analytics">
    <div class="crm-panel">
        <h3>Most Viewed Vehicles</h3>
        <p class="crm-panel__hint">Detail page &amp; inquiry modal opens (once per session per car)</p>
        <?php if (empty($mostViewed)): ?>
            <p class="crm-empty">No view data yet.</p>
        <?php else: ?>
            <ul class="crm-source-list">
                <?php foreach ($mostViewed as $i => $row): ?>
                <li>
                    <span class="crm-rank"><?= $i + 1 ?></span>
                    <span class="crm-source-list__name"><?= sanitize($analyticsCarTitles[(int) $row['car_id']] ?? 'Unknown') ?></span>
                    <span class="crm-source-list__count"><?= (int) $row['total'] ?> views</span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="crm-panel">
        <h3>Most Clicked Vehicles</h3>
        <p class="crm-panel__hint">“Interested in this car?” + WhatsApp continue clicks</p>
        <?php if (empty($mostClicked)): ?>
            <p class="crm-empty">No click data yet.</p>
        <?php else: ?>
            <ul class="crm-source-list">
                <?php foreach ($mostClicked as $i => $row): ?>
                <li>
                    <span class="crm-rank"><?= $i + 1 ?></span>
                    <span class="crm-source-list__name"><?= sanitize($analyticsCarTitles[(int) $row['car_id']] ?? 'Unknown') ?></span>
                    <span class="crm-source-list__count"><?= (int) $row['total'] ?> clicks</span>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
