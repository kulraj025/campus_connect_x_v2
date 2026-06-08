<?php
// includes/ads.php
// Fetch active ads for a placement

function getAds(string $placement, int $limit = 10): array {
    $today = date('Y-m-d');
    $stmt  = db()->prepare("
        SELECT * FROM ads
        WHERE is_active = 1
          AND FIND_IN_SET(?, placement)
          AND (start_date IS NULL OR start_date <= ?)
          AND (end_date   IS NULL OR end_date   >= ?)
        ORDER BY sort_order ASC, id ASC
        LIMIT ?
    ");
    $stmt->execute([$placement, $today, $today, $limit]);
    return $stmt->fetchAll();
}

function trackAdView(int $adId): void {
    db()->prepare("UPDATE ads SET view_count=view_count+1 WHERE id=?")->execute([$adId]);
}

function trackAdClick(int $adId): void {
    db()->prepare("UPDATE ads SET click_count=click_count+1 WHERE id=?")->execute([$adId]);
}

// Ad type config
function adTypeConfig(string $type): array {
    return [
        'info'  => ['icon' => 'ℹ️',  'label' => 'Info',      'accent' => '#60A5FA'],
        'news'  => ['icon' => '📰', 'label' => 'News',      'accent' => '#34D399'],
        'promo' => ['icon' => '🎉', 'label' => 'Feature',   'accent' => '#F472B6'],
        'event' => ['icon' => '📅', 'label' => 'Event',     'accent' => '#FBBF24'],
        'tip'   => ['icon' => '💡', 'label' => 'Tip',       'accent' => '#A78BFA'],
        'alert' => ['icon' => '🔔', 'label' => 'Alert',     'accent' => '#F87171'],
    ][$type] ?? ['icon' => 'ℹ️', 'label' => 'Info', 'accent' => '#60A5FA'];
}
