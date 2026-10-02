<?php

/**
 * Aggregate image alt text from MySchool CMS (same API host / school id as includes/api.php).
 * Primary route follows kebab-case like other endpoints; snake_case is tried if the first misses.
 */
if (!function_exists('fetchApiData')) {
    require_once __DIR__ . '/api.php';
}

/** Same convention as meta-data/SCLID000001 */
define('IMAGE_ALT_ENDPOINT_PRIMARY', 'image-alt-text/SCLID000001');
define('IMAGE_ALT_ENDPOINT_FALLBACK', 'image_alt_text/SCLID000001');

/**
 * Maps SCRIPT_NAME-derived keys (see image_alt_script_key) to CMS webpage.name values.
 * Keep in sync with includes/meta.php where overlap exists; extend as CMS defines pages.
 */
function image_alt_page_web_map(): array
{
    return [
        'index' => 'Home Page',
        'contact-us' => 'Contact Us Page',
        'about-us' => 'About Us Page',
        'admission-procedure' => 'Admission - Admission procedure',
        'fee-structure' => 'Admission - Fee structure',
        'blog' => 'Blogs',
        'faq' => 'FAQ',
        'our-curriculum' => 'Our Curriculum',
    ];
}

function image_alt_script_key(): string
{
    $sn = $_SERVER['SCRIPT_NAME'] ?? '';
    $sn = str_replace('\\', '/', (string) $sn);
    $sn = trim($sn, '/');
    if ($sn === '') {
        return 'index';
    }

    return preg_replace('#\.php$#i', '', str_replace('/', '-', $sn));
}

function image_alt_current_webpage_name(): ?string
{
    static $resolved = false;
    static $name = null;
    if ($resolved) {
        return $name;
    }
    $resolved = true;
    $key = image_alt_script_key();
    $map = image_alt_page_web_map();
    $name = $map[$key] ?? null;

    return $name;
}

function image_alt_normalize_keys(?string $src): array
{
    $src = trim((string) $src);
    if ($src === '') {
        return [];
    }

    $keys = [];
    $keys[] = $src;

    $lower = strtolower($src);
    if ($lower !== $src) {
        $keys[] = $lower;
    }

    $slashPath = str_replace('\\', '/', $src);
    if ($slashPath !== $src) {
        $keys[] = strtolower($slashPath);
    }
    $bnRel = basename($slashPath);
    if ($bnRel !== '') {
        $keys[] = strtolower($bnRel);
    }

    $parsed = parse_url($src);
    if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
        $path = isset($parsed['path']) ? rawurldecode($parsed['path']) : '';
        $rebuilt = strtolower($parsed['scheme']) . '://' . strtolower($parsed['host'])
            . ($path !== '' ? $path : '');
        $keys[] = $rebuilt;
        $fq = $rebuilt . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        $keys[] = $fq;
    }

    if (!empty($parsed['path'])) {
        $bp = basename(rawurldecode($parsed['path']));
        if ($bp !== '') {
            $keys[] = strtolower($bp);
        }
        $keys[] = strtolower(str_replace('\\', '/', rawurldecode($parsed['path'])));
    }

    $unique = [];
    foreach ($keys as $k) {
        $k = trim((string) $k);
        if ($k === '') {
            continue;
        }
        $unique[$k] = true;
    }

    return array_keys($unique);
}

function image_alt_pick_alt_field(array $row): string
{
    foreach (['image_alt_text', 'alt_text', 'alt'] as $k) {
        if (!isset($row[$k])) {
            continue;
        }
        $v = $row[$k];
        if (is_string($v)) {
            $t = trim($v);

            return $t;
        }
    }

    return '';
}

function image_alt_collect_urls(array $row): array
{
    $urls = [];
    if (!empty($row['urls'])) {
        $urls[] = $row['urls'];
    }
    if (!empty($row['media']['urls'])) {
        $urls[] = $row['media']['urls'];
    }
    if (!empty($row['medias']) && is_array($row['medias'])) {
        foreach ($row['medias'] as $m) {
            if (is_array($m) && !empty($m['urls'])) {
                $urls[] = $m['urls'];
            }
        }
    }

    return array_values(array_filter(array_map('strval', $urls)));
}

function image_alt_walk_items(array $items, array &$flat): void
{
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $pageName = null;
        if (!empty($item['webpage']['name'])) {
            $pageName = trim((string) $item['webpage']['name']);
        } elseif (!empty($item['webpage_name'])) {
            $pageName = trim((string) $item['webpage_name']);
        }

        $alt = image_alt_pick_alt_field($item);
        foreach (image_alt_collect_urls($item) as $url) {
            if ($url === '') {
                continue;
            }
            $flat[] = ['url' => $url, 'alt' => $alt, 'page' => $pageName];
        }
    }
}

function image_alt_parse_payload(?array $payload): array
{
    $flat = [];
    if (!$payload || ($payload['status'] ?? '') !== 'success') {
        return $flat;
    }
    $data = $payload['data'] ?? null;
    if ($data === null) {
        return $flat;
    }
    if (!is_array($data)) {
        return $flat;
    }
    image_alt_walk_items($data, $flat);

    return $flat;
}

function image_alt_load_payload(): array
{
    $primary = fetchApiData(IMAGE_ALT_ENDPOINT_PRIMARY);
    if ($primary && ($primary['status'] ?? '') === 'success') {
        return $primary;
    }

    return fetchApiData(IMAGE_ALT_ENDPOINT_FALLBACK) ?: [];
}

function image_alt_bootstrap(): void
{
    static $boot = false;
    if ($boot) {
        return;
    }
    $boot = true;

    $GLOBALS['_IMAGE_ALT_LOOKUP_GLOBAL'] = [];
    $GLOBALS['_IMAGE_ALT_LOOKUP_PAGE'] = [];
    $GLOBALS['_IMAGE_ALT_JS_EXPORT'] = [];

    $payload = image_alt_load_payload();
    $currentPage = image_alt_current_webpage_name();

    foreach (image_alt_parse_payload($payload) as $row) {
        $alt = $row['alt'] ?? '';
        $page = $row['page'] ?? null;
        foreach (image_alt_normalize_keys($row['url'] ?? '') as $key) {
            if ($alt !== '') {
                $GLOBALS['_IMAGE_ALT_LOOKUP_GLOBAL'][$key] = $alt;
            }
            if ($page !== null && $page !== '' && $currentPage !== null && strcasecmp($page, $currentPage) === 0 && $alt !== '') {
                $GLOBALS['_IMAGE_ALT_LOOKUP_PAGE'][$key] = $alt;
            }
        }
    }

    foreach ($GLOBALS['_IMAGE_ALT_LOOKUP_PAGE'] as $k => $v) {
        $GLOBALS['_IMAGE_ALT_JS_EXPORT'][$k] = $v;
    }
    foreach ($GLOBALS['_IMAGE_ALT_LOOKUP_GLOBAL'] as $k => $v) {
        if (!isset($GLOBALS['_IMAGE_ALT_JS_EXPORT'][$k])) {
            $GLOBALS['_IMAGE_ALT_JS_EXPORT'][$k] = $v;
        }
    }
}

function image_alt_resolve(?string $src, string $fallback = ''): string
{
    image_alt_bootstrap();
    $src = trim((string) $src);

    foreach (image_alt_normalize_keys($src) as $key) {
        if ($key === '') {
            continue;
        }
        if (!empty($GLOBALS['_IMAGE_ALT_LOOKUP_PAGE'][$key])) {
            return $GLOBALS['_IMAGE_ALT_LOOKUP_PAGE'][$key];
        }
    }
    foreach (image_alt_normalize_keys($src) as $key) {
        if ($key === '') {
            continue;
        }
        if (!empty($GLOBALS['_IMAGE_ALT_LOOKUP_GLOBAL'][$key])) {
            return $GLOBALS['_IMAGE_ALT_LOOKUP_GLOBAL'][$key];
        }
    }

    return $fallback;
}

function image_alt_esc(?string $src, string $fallback = ''): string
{
    return htmlspecialchars(image_alt_resolve($src, $fallback), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function image_alt_export_js_json(): string
{
    image_alt_bootstrap();
    $map = $GLOBALS['_IMAGE_ALT_JS_EXPORT'] ?? [];
    if ($map === []) {
        return '{}';
    }

    return json_encode($map, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
}
