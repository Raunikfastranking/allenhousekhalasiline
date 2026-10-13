<?php

declare(strict_types=1);

if (!function_exists('cmsApiUsesFileCache')) {
    /**
     * File cache keeps responses fast. CMS content keys use cmsApiContentCacheTtl() (default 5 min).
     * Set CMS_FILE_CACHE=0 to disable (not recommended on production).
     */
    function cmsApiUsesFileCache(): bool
    {
        if (isset($_GET['clear_cache']) || isset($_GET['clear-cache']) || isset($_GET['nocache'])) {
            static $cleared = false;
            if (!$cleared) {
                $cleared = true;
                if (function_exists('cmsApiScopedCacheClearAll')) {
                    cmsApiScopedCacheClearAll();
                } else {
                    $cacheFile = function_exists('cmsApiScopedCachePath') ? cmsApiScopedCachePath() : '';
                    if ($cacheFile !== '' && is_file($cacheFile)) {
                        @unlink($cacheFile);
                    }
                }
            }
            return false;
        }

        // Explicit env setting wins — keeps file cache on for local/staging testing.
        $env = getenv('CMS_FILE_CACHE');
        if ($env !== false) {
            $value = strtolower(trim((string) $env));

            if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }

            return in_array($value, ['1', 'true', 'yes', 'on'], true);
        }

        if (function_exists('is_app_local') && is_app_local()) {
            return false;
        }

        return true;
    }
}

if (!function_exists('cmsApiContentCacheTtl')) {
    /** Seconds before editable CMS page/home keys refresh from the API. */
    function cmsApiContentCacheTtl(): int
    {
        $env = getenv('CMS_CONTENT_CACHE_TTL');
        if ($env !== false && is_numeric($env)) {
            return max(60, (int) $env);
        }

        return 300;
    }
}

if (!function_exists('cmsApiStaleGraceTtl')) {
    /**
     * After TTL expires, keep serving cached JSON while refreshing in the background.
     * Avoids 5–10s API waits on cache expiry. Set CMS_STALE_GRACE=0 to disable.
     */
    function cmsApiStaleGraceTtl(): int
    {
        $env = getenv('CMS_STALE_GRACE');
        if ($env !== false && is_numeric($env)) {
            return max(0, (int) $env);
        }

        return 3600;
    }
}

if (!function_exists('cmsApiEndpointTtl')) {
    /**
     * Per-endpoint cache TTL (seconds). Structural data gets longer TTL for speed.
     */
    function cmsApiEndpointTtl(string $key): int
    {
        if (!cmsApiUsesFileCache()) {
            return 0;
        }

        $contentTtl = cmsApiContentCacheTtl();

        if (str_starts_with($key, 'page_')) {
            return $contentTtl;
        }
        if (str_starts_with($key, 'legacy_')) {
            return 1800;
        }

        return match ($key) {
            'home_data', 'landing_data' => $contentTtl,
            // Content admins edit these — keep close to content TTL so CMS
            // updates surface within ~a minute, not half an hour.
            'scroll_text_data', 'flyer_data', 'blogData' => max($contentTtl, 300),
            'statistics_data', 'statistic_data' => max($contentTtl, 300),
            'photo_gallery_data', 'achievement_gallery_data', 'gallery_data', 'jobs_data' => max($contentTtl, 300),
            // Menu/nav is also force-refetched (cmsApiEndpointRequiresLiveFetch) so CMS
            // add/remove is visible on the next page load — not stuck in cache.
            'menu_data' => $contentTtl,
            'header_footer_data' => max($contentTtl, 300),
            // Keep short so newly created CMS pages resolve without waiting hours.
            'pages_index' => max($contentTtl, 600),
            'footer_important_links', 'footer_quick_links', 'footer_hierarchical' => max($contentTtl, 300),
            'school_sessions_data', 'image_alt_data' => 3600,
            'grades_data', 'cities_data' => 24 * 3600,
            default => $contentTtl,
        };
    }
}
