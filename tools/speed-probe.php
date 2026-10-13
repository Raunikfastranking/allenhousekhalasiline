<?php
// CLI probe: measures phase timings of a real page bootstrap.
// Run: php tools\speed-probe.php photo-gallery-page-khalasiline
declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['REQUEST_URI'] = '/photo-gallery';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../cms-page.php';
$_SERVER['SCRIPT_NAME'] = '/cms-page.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';

$GLOBALS['cms_track_network'] = ['count' => 0, 'urls' => []];

$t0 = microtime(true);
require_once dirname(__DIR__) . '/includes/apis.php';
echo sprintf("[bootstrap] %.3fs  (network calls: %d)\n", microtime(true) - $t0, $GLOBALS['cms_track_network']['count']);

$t = microtime(true);
$page = cmsFetchPageBySlug($argv[1] ?? 'photo-gallery-page-khalasiline');
echo sprintf("[page]      %.3fs  data=%s\n", microtime(true) - $t, empty($page['data']) ? 'EMPTY' : 'ok');

echo "URLs hit:\n";
foreach (array_slice($GLOBALS['cms_track_network']['urls'] ?? [], 0, 40) as $u) {
    echo "  - $u\n";
}
