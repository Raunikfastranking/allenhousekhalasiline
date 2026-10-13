<?php

/**
 * CLI cache warmer — refreshes scoped CMS cache keys so web requests
 * always hit warm cache (stale-while-revalidate handles the rest).
 *
 * Run:        php scripts\warm-cache.php          (refresh everything)
 *             php scripts\warm-cache.php --quick  (refresh only expired keys)
 * Schedule via Windows Task Scheduler every 1-2 minutes
 * (see scripts\register-cache-warmer.ps1).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$root = dirname(__DIR__);
$quick = in_array('--quick', $argv ?? [], true);

putenv('APP_ENV=production');   // ensure file cache path is used even on localhost
putenv('CMS_FILE_CACHE=1');

require_once $root . '/includes/environment.php';
require_once $root . '/proxy/config.php';
require_once $root . '/includes/api.php';
require_once $root . '/includes/api-scoped-loader.php';

$startedAt = microtime(true);

// Prevent overlapping warmer runs.
$cacheDir = cmsApiScopedCacheDir();
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
$lockFile = $cacheDir . DIRECTORY_SEPARATOR . '.warm.lock';
$lock = fopen($lockFile, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Another warm-cache run is in progress\n");
}
@touch($lockFile); // freshness marker: web requests skip in-request refresh while warmer is active

// ---- Collect every cacheable endpoint: key => endpoint -------------------

$endpoints = [];

// 1. All bulk endpoints (core + home + blog + gallery + careers + landing).
$flags = ['core' => true, 'home' => true, 'blog' => true, 'gallery' => true, 'careers' => true];
$endpoints += cmsApiBuildEndpoints($flags, (int) BRANCH_ID);
$endpoints['landing_data'] = '/pages/landing-page-khalasiline';

// 2. Every CMS page -> page_<slug> (what cmsFetchPageBySlug reads).
$slugs = [];
$pagesIndex = cmsScopedFileCacheGetAny('pages_index');
if (!is_array($pagesIndex) && !$quick) {
    // Need the index to discover slugs on a cold cache.
    $pagesIndex = cmsApiCurlMultiFetch(['pages_index' => $endpoints['pages_index']])['pages_index'] ?? null;
}
foreach ((array) ($pagesIndex['data'] ?? []) as $item) {
    if (is_array($item)) {
        $slug = trim((string) ($item['slug'] ?? ''));
        if ($slug !== '') {
            $slugs[$slug] = true;
        }
    }
}
foreach (array_slice(array_keys($slugs), 0, 200) as $slug) {
    $endpoints['page_' . $slug] = '/pages/' . rawurlencode($slug);
}

// 3. Legacy endpoints used via cms_fetch_json_endpoint()/fetchApiData()
//    (enquiry forms, header/footer, SEO meta, detail pages).
$branchId = (string) BRANCH_ID;
$schoolId = defined('SCHOOL_ID') ? (string) SCHOOL_ID : '1';
foreach ([
    'boards/' . $schoolId . '/' . $branchId,
    'cities/' . $branchId,
    'cities/' . $schoolId,
    'grades/' . $branchId,
    'all-address-details/' . $branchId,
    'header-contact/' . $branchId,
    'erp-login/' . $branchId,
    'top-header-marquee-text/' . $branchId,
    'alumni-portal/' . $branchId,
    'meta-data/' . $branchId,
    'our-excellence/' . $branchId,
    'school-sessions',
] as $ep) {
    $endpoints['legacy_' . md5($ep)] = '/' . $ep;
}

// ---- Filter to expired keys in --quick mode --------------------------------

$now = time();
$pending = [];
foreach ($endpoints as $key => $endpoint) {
    if ($quick) {
        $entry = cmsApiReadKeyCache($key);
        $ttl = cmsApiEndpointTtl($key);
        if ($entry['time'] > 0 && ($now - $entry['time']) < $ttl && cmsApiResponseIsUsable($entry['data'])) {
            continue; // still fresh
        }
    }
    $pending[$key] = $endpoint;
}

// ---- Fetch in parallel batches ---------------------------------------------

$ok = 0;
$failed = [];
foreach (array_chunk($pending, 12, true) as $batch) {
    foreach (cmsApiCurlMultiFetch($batch) as $key => $value) {
        if (is_array($value) && cmsApiResponseIsUsable($value)) {
            cmsApiWriteKeyCache($key, $value);
            $ok++;
        } else {
            $failed[] = $key;
        }
    }
}

$elapsed = round(microtime(true) - $startedAt, 2);
echo sprintf(
    "[%s] Warmed %d/%d keys in %ss%s\n",
    date('Y-m-d H:i:s'),
    $ok,
    count($pending),
    $elapsed,
    $failed !== [] ? ' | failed: ' . implode(',', $failed) : ''
);

flock($lock, LOCK_UN);
fclose($lock);
