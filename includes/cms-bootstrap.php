<?php

if (!function_exists('cmsSharedBasePath')) {
    function cmsSharedBasePath(): string
    {
        $siteShared = dirname(__DIR__) . '/shared/cms';
        if (is_file($siteShared . '/cms-core.php')) {
            return $siteShared;
        }

        throw new RuntimeException('shared/cms not found at site/shared/cms');
    }
}

$cmsShared = cmsSharedBasePath();
require_once dirname(__DIR__) . '/shared/includes/cms-page-content.php';
require_once $cmsShared . '/cms-core.php';
require_once $cmsShared . '/cms-section-renderer.php';
require_once $cmsShared . '/cms-gallery-dynamic.php';
require_once dirname(__DIR__) . '/shared/includes/cms-extra-sections.php';

cmsCoreConfigure([
    'branch_suffix' => 'khalasiline',
    'branch_hosts' => ['allenhousekhalasiline.com', 'www.allenhousekhalasiline.com'],
    'header_layout_slug' => 'header-aps-khalasiline',
    'footer_layout_slug' => 'footer-aps-khalasiline',
    'home_page_slug' => 'home-page-aps-khalasiline',
    'enquiry_form_include_board' => true,
    'path_slug_aliases' => [
        'campus-tour' => 'campus-tour-page-khalasiline',
        'our-campus' => 'campus-tour-page-khalasiline',
        'mandatory-public-disclosure' => 'mpd-page-khalasiline',
        'documents-and-information' => 'documents-information-page-khalasiline',
    ],
    'slug_from_path_fn' => 'cmsKhalasilineSlugFromScript',
    'page_has_content_fn' => 'cmsKhalasilineHasPageContent',
]);

require_once __DIR__ . '/cms-khalasiline-slugs.php';

if (!function_exists('cmsKhalasilineSyncPageCache')) {
    function cmsKhalasilineSyncPageCache(string $slug, array $page): void
    {
        if ($slug === '' || !isset($GLOBALS['api_cache']) || !is_array($GLOBALS['api_cache'])) {
            return;
        }

        if (!function_exists('cmsKhalasilinePageSlugs')) {
            require_once __DIR__ . '/cms-khalasiline-slugs.php';
        }

        foreach (cmsKhalasilinePageSlugs() as $cacheKey => $cachedSlug) {
            if ($cachedSlug === $slug) {
                $GLOBALS['api_cache'][$cacheKey] = $page;
                break;
            }
        }
    }
}

if (!function_exists('cmsKhalasilinePageRequestIsLive')) {
    function cmsKhalasilinePageRequestIsLive(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }

        $requested = trim((string) ($GLOBALS['cms_page_slug'] ?? ''));

        return $requested !== '' && $requested === $slug;
    }
}

if (!function_exists('cmsFetchKhalasilinePage')) {
    function cmsFetchKhalasilinePage(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return ['data' => []];
        }

        if (!function_exists('cmsFetchPageBySlug')) {
            return ['data' => []];
        }

        $forceLive = function_exists('cmsKhalasilinePageRequestIsLive')
            && cmsKhalasilinePageRequestIsLive($slug);

        $page = cmsFetchPageBySlug($slug, $forceLive);
        if (function_exists('cmsKhalasilineNormalizePageTitles')) {
            require_once __DIR__ . '/cms-page-helpers.php';
            $page = cmsKhalasilineNormalizePageTitles($page, $slug);
        }
        cmsKhalasilineSyncPageCache($slug, $page);

        return $page;
    }
}

if (!function_exists('cmsKhalasilineCmsItemIsVisible')) {
    function cmsKhalasilineCmsItemIsVisible(array $item): bool
    {
        $status = strtolower(trim((string) ($item['status'] ?? 'active')));
        $publish = strtolower(trim((string) ($item['publish_status'] ?? 'published')));
        if ($status !== '' && !in_array($status, ['active', '1', 'enabled'], true)) {
            return false;
        }
        if ($publish !== '' && !in_array($publish, ['published', 'publish', 'active', '1'], true)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('cmsKhalasilineFlyersResponse')) {
    /** @return array<string, mixed>|list<array<string, mixed>>|null */
    function cmsKhalasilineFlyersResponse(): ?array
    {
        if (!defined('BRANCH_ID') || !defined('CMS_API_URL')) {
            return null;
        }

        $endpoint = 'flyers/branch/' . BRANCH_ID;
        $url = rtrim(CMS_API_URL, '/') . '/' . $endpoint;

        
        if (!function_exists('cmsBulkFlyersResponse')) {
            require_once dirname(__DIR__) . '/shared/includes/cms-bulk-reuse.php';
        }
        $bulk = cmsBulkFlyersResponse();
        if ($bulk !== null) {
            return $bulk;
        }

        if (function_exists('cmsCmsGetJson')) {
            $viaCore = cmsCmsGetJson($endpoint, 20);
            if (is_array($viaCore)) {
                return $viaCore;
            }
        }

        if (!function_exists('cms_curl_get_json')) {
            return null;
        }

        $response = cms_curl_get_json($url, 20);
        if (is_array($response)) {
            return $response;
        }
        $response = cms_curl_get_json($url, 25);
        if (is_array($response)) {
            return $response;
        }

        return null;
    }
}

if (!function_exists('cmsKhalasilineScrollingTextsResponse')) {
    function cmsKhalasilineScrollingTextsResponse(): ?array
    {
        if (!defined('BRANCH_ID') || !defined('CMS_API_URL')) {
            return null;
        }

        $endpoint = 'scrolling-texts/branch/' . BRANCH_ID;
        if (function_exists('cmsCmsGetJson')) {
            $viaCore = cmsCmsGetJson($endpoint, 20);
            if (is_array($viaCore)) {
                return $viaCore;
            }
        }

        if (!function_exists('cms_curl_get_json')) {
            return null;
        }

        $url = rtrim(CMS_API_URL, '/') . '/' . $endpoint;
        return cms_curl_get_json($url, 20);
    }
}

if (!function_exists('cmsKhalasilineScrollingText')) {
    function cmsKhalasilineScrollingText(): string
    {
        static $resolved = false;
        static $text = '';

        if ($resolved) {
            return $text;
        }
        $resolved = true;

        $response = cmsKhalasilineScrollingTextsResponse();
        $items = [];
        if (is_array($response)) {
            $items = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;
        }
        if (!is_array($items)) {
            return $text;
        }

        foreach ($items as $item) {
            if (!is_array($item) || !cmsKhalasilineCmsItemIsVisible($item)) {
                continue;
            }
            $content = trim((string) ($item['content'] ?? $item['contant'] ?? $item['title'] ?? ''));
            if (function_exists('cmsPlainText')) {
                $content = cmsPlainText($content);
            }
            if ($content !== '') {
                $text = $content;
                break;
            }
        }

        return $text;
    }
}

if (!function_exists('cmsKhalasilineHeaderMeta')) {
    function cmsKhalasilineHeaderMeta(): array
    {
        static $meta = null;
        if ($meta !== null) {
            return $meta;
        }

        $meta = [];
        if (!defined('BRANCH_ID')) {
            return $meta;
        }

        $slug = (string) cmsCoreGet('header_layout_slug', 'header-aps-khalasiline');
        $layoutResponse = cmsLoadFooterBootstrap()['layout'] ?? [];
        $items = [];
        if (isset($layoutResponse['data']) && is_array($layoutResponse['data'])) {
            $items = $layoutResponse['data'];
        } elseif (is_array($layoutResponse)) {
            $items = $layoutResponse;
        }

        $headerPart = null;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (($item['slug'] ?? null) === $slug || ($item['layout_slug'] ?? null) === $slug) {
                $headerPart = $item;
                break;
            }
        }
        if ($headerPart === null) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (($item['type'] ?? null) === 'header' || ($item['layout_type'] ?? null) === 'header') {
                    $headerPart = $item;
                    break;
                }
            }
        }

        return is_array($headerPart['meta_data'] ?? null) ? $headerPart['meta_data'] : [];
    }
}

if (!function_exists('cmsKhalasilineHasPageContent')) {
    require_once __DIR__ . '/cms-khalasiline-special-pages.php';
}

if (!defined('CMS_AUTO_GUARD_RAN')) {
    define('CMS_AUTO_GUARD_RAN', true);
    if (function_exists('cmsAutoGuardCurrentScriptPage')) {
        cmsAutoGuardCurrentScriptPage();
    }
}
