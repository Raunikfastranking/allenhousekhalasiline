<?php
declare(strict_types=1);
/** CMS-driven page — APS CMS. */
$GLOBALS['cms_api_load_flags'] = ['gallery' => true];
$cms_page_slug = 'media-events-page-khalasiline';
$cms_page_title_fallback = 'Events';
$cms_canonical_path = '/event-gallery';
require __DIR__ . '/includes/cms-dynamic-page.php';
