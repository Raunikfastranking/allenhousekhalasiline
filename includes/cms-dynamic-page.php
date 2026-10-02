<?php

/**
 * Full-page CMS renderer — content from APS CMS API (shared pattern).
 */

require_once __DIR__ . '/environment.php';
require_once dirname(__DIR__) . '/proxy/config.php';

$GLOBALS['cms_skip_published_guard'] = true;

if (!empty($cms_page_slug)) {
    $GLOBALS['cms_page_slug'] = trim((string) $cms_page_slug);
}

require_once __DIR__ . '/apis.php';
require_once __DIR__ . '/cms-khalasiline-slugs.php';
require_once __DIR__ . '/cms-page-helpers.php';
require_once __DIR__ . '/cms-khalasiline-special-pages.php';

if (trim((string) ($cms_page_slug ?? '')) === '' && !empty($GLOBALS['cms_page_slug'])) {
    $cms_page_slug = (string) $GLOBALS['cms_page_slug'];
}

$script = basename($_SERVER['SCRIPT_FILENAME'] ?? '', '.php');
// Prefer explicit slug from page stubs. Never overwrite it.
if (trim((string) ($cms_page_slug ?? '')) === '') {
    $fromScript = cmsKhalasilineSlugFromScript($script);
    $defaultGuess = str_replace('_', '-', $script) . '-page-khalasiline';
    if (in_array($script, ['cms-page', 'index', ''], true)) {
        $cms_page_slug = $fromScript;
    } else {
        $resolved = function_exists('cmsResolveSlugFromPath') ? cmsResolveSlugFromPath($script) : null;
        if ($fromScript !== $defaultGuess) {
            $cms_page_slug = $fromScript;
        } elseif (is_string($resolved) && $resolved !== '') {
            $cms_page_slug = $resolved;
        } else {
            $cms_page_slug = $fromScript;
        }
    }
}
$GLOBALS['cms_page_slug'] = trim((string) $cms_page_slug);

$cmsPedagogyLayout = ($cms_page_slug ?? '') === 'pedagogy-page-khalasiline';
$cmsAboutLayout = ($cms_page_slug ?? '') === 'about-us-page-khalasiline';
$cmsFacilitiesLayout = ($cms_page_slug ?? '') === 'facilities-page-khalasiline';
$cmsContactLayout = ($cms_page_slug ?? '') === 'contact-us-page-khalasiline';
$cmsCampusTourLayout = ($cms_page_slug ?? '') === 'campus-tour-page-khalasiline';
if ($cmsPedagogyLayout) {
    require_once __DIR__ . '/pedagogy-cms-page.php';
}
if ($cmsAboutLayout) {
    require_once __DIR__ . '/about-us-cms-page.php';
}
if ($cmsFacilitiesLayout) {
    require_once __DIR__ . '/facilities-cms-page.php';
}
if ($cmsContactLayout) {
    require_once __DIR__ . '/contact-us-cms-page.php';
}
if ($cmsCampusTourLayout) {
    require_once __DIR__ . '/campus-tour-cms-page.php';
}

require_once dirname(__DIR__) . '/shared/includes/cms-page-content.php';

$cms_after_sections = static function (array $page, string $slug): void {
    cmsKhalasilineAfterSections($page, $slug);
};

if (($cms_page_slug ?? '') === 'testimonials-page-khalasiline') {
    $cms_banner_only = true;
    $cms_page_title_fallback = 'Testimonials';
}

$GLOBALS['cms_dynamic_page_config'] = [
    'site_name' => 'Allenhouse Khalasi Line',
    'canonical_host' => 'https://allenhousekhalasiline.com',
    'includes_dir' => __DIR__,
    'slug_fn' => 'cmsKhalasilineSlugFromScript',
    'fetch_fn' => 'cmsFetchKhalasilinePage',
    'has_fn' => 'cmsKhalasilineHasPageContent',
    'use_meta_for_inner' => false,
    'render_fn' => $cmsPedagogyLayout
        ? 'cmsRenderPedagogyPage'
        : ($cmsAboutLayout
            ? 'cmsRenderAboutPage'
            : ($cmsFacilitiesLayout
                ? 'cmsRenderFacilitiesPage'
                : ($cmsContactLayout
                    ? 'cmsRenderContactUsPage'
                    : ($cmsCampusTourLayout ? 'cmsRenderCampusTourPage' : '')))),
];

require_once dirname(__DIR__) . '/shared/includes/cms-dynamic-page-runner.php';
